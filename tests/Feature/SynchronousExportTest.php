<?php

namespace Tests\Feature;

use App\Exports\CsvExportDefinition;
use App\Exports\CsvExportFailure;
use App\Exports\SynchronousCsvExporter;
use App\Models\QuoteRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class SynchronousExportTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = storage_path('framework/testing/synchronous-exports/'.Str::uuid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryDirectory);

        parent::tearDown();
    }

    public function test_it_writes_a_private_ordered_csv_in_500_id_chunks_and_deletes_it_after_send(): void
    {
        $this->insertQuoteRequests(1201);
        $chunkQueries = [];

        DB::listen(function ($query) use (&$chunkQueries): void {
            if (str_contains($query->sql, 'from "quote_requests"')) {
                $chunkQueries[] = $query;
            }
        });

        $definition = new CsvExportDefinition(
            resource: 'quotes',
            headers: ['ID', 'Full Name', 'Estimated Quantity'],
            row: static fn (Model $record): array => [
                $record->getKey(),
                $record->full_name,
                $record->estimated_qty,
            ],
        );

        $response = (new SynchronousCsvExporter($this->temporaryDirectory))->download(
            QuoteRequest::query()->orderByDesc('full_name'),
            $definition,
            CarbonImmutable::create(2026, 9, 18, 14, 30, 45),
        );

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('no-store, private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString(
            'attachment; filename=quotes-20260918-143045.csv',
            $response->headers->get('Content-Disposition'),
        );
        $this->assertTrue($response->shouldDeleteFileAfterSend());
        $this->assertCount(3, $chunkQueries);
        $this->assertSame([], $chunkQueries[0]->bindings);
        $this->assertSame([500], $chunkQueries[1]->bindings);
        $this->assertSame([1000], $chunkQueries[2]->bindings);
        $this->assertStringNotContainsString('full_name', $chunkQueries[0]->sql);

        $path = $response->getFile()->getPathname();
        $this->assertFileExists($path);
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->assertSame(0600, fileperms($path) & 0777);
            $this->assertSame(0700, fileperms($this->temporaryDirectory) & 0777);
        }
        $csv = file_get_contents($path);
        $this->assertIsString($csv);
        $this->assertTrue(mb_check_encoding($csv, 'UTF-8'));

        $stream = fopen($path, 'rb');
        $this->assertIsResource($stream);
        $rows = [];
        while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        $this->assertSame(['ID', 'Full Name', 'Estimated Quantity'], $rows[0]);
        $this->assertCount(1202, $rows);
        $ids = array_map(static fn (array $row): string => $row[0], array_slice($rows, 1));
        $this->assertCount(1201, array_unique($ids));
        $this->assertSame(array_map('strval', range(1, 1201)), $ids);
        $this->assertSame("'=SUM(A1:A2)", $rows[1][1]);
        $this->assertSame(str_repeat('鋼', 151), $rows[1][2]);
        $this->assertSame('Name, with comma', $rows[500][1]);
        $this->assertSame('quote "inside"', $rows[500][2]);
        $this->assertSame("Line\nBreak", $rows[501][1]);
        $this->assertSame('鋼材', $rows[501][2]);
        $this->assertSame("'+plus", $rows[1000][1]);
        $this->assertSame("'@mention", $rows[1001][1]);

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('exports', 0);

        ob_start();
        $response->sendContent();
        $sentContent = ob_get_clean();

        $this->assertSame($csv, $sentContent);
        $this->assertFileDoesNotExist($path);
        $this->assertSame([], File::allFiles($this->temporaryDirectory));
    }

    public function test_it_cleans_up_and_exposes_one_safe_correlation_id_when_row_mapping_fails(): void
    {
        $this->insertQuoteRequests(501);
        $loggedContext = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use (&$loggedContext): bool {
                $loggedContext = $context;

                return $message === 'Synchronous CSV export failed.'
                    && isset($context['correlation_id'], $context['resource'])
                    && ! isset($context['row']);
            });

        $definition = new CsvExportDefinition(
            resource: 'failed-quotes',
            headers: ['ID', 'Full Name'],
            row: static function (Model $record): array {
                if ((int) $record->getKey() === 501) {
                    throw new RuntimeException('private row payload must not be exposed');
                }

                return [$record->getKey(), $record->full_name];
            },
        );

        $failure = null;
        try {
            (new SynchronousCsvExporter($this->temporaryDirectory))->download(
                QuoteRequest::query(),
                $definition,
                CarbonImmutable::create(2026, 9, 18, 14, 30, 45),
            );
            $this->fail('Expected the export to fail when row 501 cannot be mapped.');
        } catch (CsvExportFailure $exception) {
            $failure = $exception;
        }

        $this->assertNotNull($failure);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $failure->correlationId(),
        );
        $this->assertSame($failure->correlationId(), $loggedContext['correlation_id']);
        $this->assertSame('failed-quotes', $failure->resource());
        $this->assertSame('failed-quotes', $loggedContext['resource']);
        $this->assertStringNotContainsString('private row payload', $failure->getMessage());
        $this->assertSame(1, preg_match_all(
            '/[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}/i',
            $failure->getMessage(),
        ));
        $this->assertSame([], File::allFiles($this->temporaryDirectory));
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('exports', 0);
    }

    public function test_invalid_utf8_fails_with_the_logged_correlation_id_and_removes_the_temp_file(): void
    {
        QuoteRequest::query()->insert([
            'id' => 1,
            'full_name' => 'Invalid UTF-8 Customer',
            'estimated_qty' => "invalid \xC3\x28 bytes",
            'status' => 'new',
            'is_seen' => false,
            'source' => 'home',
        ]);
        $loggedContext = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use (&$loggedContext): bool {
                $loggedContext = $context;

                return $message === 'Synchronous CSV export failed.'
                    && isset($context['correlation_id'], $context['resource']);
            });

        $definition = new CsvExportDefinition(
            resource: 'invalid-utf8',
            headers: ['ID', 'Estimated Quantity'],
            row: static fn (Model $record): array => [$record->getKey(), $record->estimated_qty],
        );

        try {
            (new SynchronousCsvExporter($this->temporaryDirectory))->download(
                QuoteRequest::query(),
                $definition,
                CarbonImmutable::create(2026, 9, 18, 14, 30, 45),
            );
            $this->fail('Expected invalid UTF-8 to abort the export.');
        } catch (CsvExportFailure $failure) {
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $failure->correlationId(),
            );
            $this->assertSame($failure->correlationId(), $loggedContext['correlation_id']);
            $this->assertSame('invalid-utf8', $failure->resource());
        }

        $this->assertSame([], File::allFiles($this->temporaryDirectory));
    }

    public function test_it_fails_before_mapping_rows_when_private_directory_permissions_cannot_be_established(): void
    {
        $this->insertQuoteRequests(1);
        $mappedRows = 0;
        $loggedContext = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use (&$loggedContext): bool {
                $loggedContext = $context;

                return $message === 'Synchronous CSV export failed.'
                    && isset($context['correlation_id'], $context['resource'])
                    && ! isset($context['row']);
            });

        $definition = new CsvExportDefinition(
            resource: 'permission-failure',
            headers: ['ID', 'Full Name'],
            row: static function (Model $record) use (&$mappedRows): array {
                $mappedRows++;

                return [$record->getKey(), $record->full_name];
            },
        );

        try {
            (new SynchronousCsvExporter(
                temporaryDirectory: $this->temporaryDirectory,
                chmod: static fn (string $path, int $mode): bool => false,
                permissionsMeaningful: true,
            ))->download(
                QuoteRequest::query(),
                $definition,
                CarbonImmutable::create(2026, 9, 18, 14, 30, 45),
            );
            $this->fail('Expected the export to abort when private directory permissions fail.');
        } catch (CsvExportFailure $failure) {
            $this->assertSame($failure->correlationId(), $loggedContext['correlation_id']);
            $this->assertSame('permission-failure', $failure->resource());
        }

        $this->assertSame(0, $mappedRows);
        $this->assertSame([], File::allFiles($this->temporaryDirectory));
    }

    public function test_failed_cleanup_remains_typed_and_correlated_without_logging_row_data(): void
    {
        $this->insertQuoteRequests(1);
        $loggedContext = null;
        $temporaryPath = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use (&$loggedContext): bool {
                $loggedContext = $context;

                return $message === 'Synchronous CSV export failed.'
                    && isset(
                        $context['correlation_id'],
                        $context['resource'],
                        $context['exception'],
                        $context['cleanup_exception'],
                    )
                    && ! isset($context['row'])
                    && ! str_contains(json_encode($context), 'private row payload');
            });

        $definition = new CsvExportDefinition(
            resource: 'cleanup-failure',
            headers: ['ID', 'Full Name'],
            row: static function (): array {
                throw new \LogicException('private row payload must not be logged');
            },
        );

        try {
            (new SynchronousCsvExporter(
                temporaryDirectory: $this->temporaryDirectory,
                unlink: static function (string $path) use (&$temporaryPath): bool {
                    $temporaryPath = $path;

                    return false;
                },
            ))->download(
                QuoteRequest::query(),
                $definition,
                CarbonImmutable::create(2026, 9, 18, 14, 30, 45),
            );
            $this->fail('Expected row mapping and cleanup failure to abort the export.');
        } catch (CsvExportFailure $failure) {
            $this->assertSame($failure->correlationId(), $loggedContext['correlation_id']);
            $this->assertSame('cleanup-failure', $failure->resource());
        }

        $this->assertNotNull($temporaryPath);
        $this->assertFileExists($temporaryPath);
        $this->assertSame(RuntimeException::class, $loggedContext['cleanup_exception']);
    }

    private function insertQuoteRequests(int $count): void
    {
        $rows = [];

        for ($id = 1; $id <= $count; $id++) {
            $rows[] = [
                'id' => $id,
                'full_name' => "Customer {$id}",
                'estimated_qty' => "Quantity {$id}",
                'status' => 'new',
                'is_seen' => false,
                'source' => 'home',
            ];

            if ($id === 1) {
                $rows[array_key_last($rows)]['full_name'] = '=SUM(A1:A2)';
                $rows[array_key_last($rows)]['estimated_qty'] = str_repeat('鋼', 151);
            } elseif ($id === 500) {
                $rows[array_key_last($rows)]['full_name'] = 'Name, with comma';
                $rows[array_key_last($rows)]['estimated_qty'] = 'quote "inside"';
            } elseif ($id === 501) {
                $rows[array_key_last($rows)]['full_name'] = "Line\nBreak";
                $rows[array_key_last($rows)]['estimated_qty'] = '鋼材';
            } elseif ($id === 1000) {
                $rows[array_key_last($rows)]['full_name'] = '+plus';
            } elseif ($id === 1001) {
                $rows[array_key_last($rows)]['full_name'] = '@mention';
            }

            if (count($rows) === 100 || $id === $count) {
                QuoteRequest::query()->insert($rows);
                $rows = [];
            }
        }
    }
}
