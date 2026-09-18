<?php

namespace Tests\Feature;

use App\Exports\CsvExportDefinition;
use App\Exports\CsvExportFailure;
use App\Exports\SynchronousCsvExporter;
use App\Filament\Admin\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Admin\Resources\QuoteRequests\Pages\ListQuoteRequests;
use App\Filament\Admin\Resources\Referrals\Pages\ListReferrals;
use App\Models\ContactMessage;
use App\Models\ProductCategory;
use App\Models\QuoteRequest;
use App\Models\Referral;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class SynchronousExportTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->temporaryDirectory = storage_path('framework/testing/synchronous-exports/'.Str::uuid());
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Filament::setCurrentPanel(null);
        File::deleteDirectory($this->temporaryDirectory);

        parent::tearDown();
    }

    public function test_quote_header_export_preserves_filter_scope_columns_and_long_values(): void
    {
        $this->signInAsAuthorizedStaff();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 9, 18, 14, 30, 45));

        $category = ProductCategory::query()->create([
            'name' => 'Structural Steel',
            'slug' => 'structural-steel',
        ]);

        QuoteRequest::query()->insert([
            [
                'id' => 1,
                'category_id' => null,
                'full_name' => 'Hidden Quote',
                'company_name' => 'Hidden Co',
                'email' => 'hidden@example.test',
                'phone' => '09170000001',
                'address' => 'Hidden address',
                'how_heard' => 'Search',
                'estimated_qty' => 'Hidden quantity',
                'status' => 'new',
                'is_seen' => false,
                'source' => 'home',
                'created_at' => '2026-09-18 14:29:00',
            ],
            [
                'id' => 2,
                'category_id' => $category->id,
                'full_name' => 'Visible Quote',
                'company_name' => 'Visible Co',
                'email' => 'visible@example.test',
                'phone' => '09170000002',
                'address' => 'Visible address',
                'how_heard' => 'Referral',
                'estimated_qty' => str_repeat('Long quantity ', 20),
                'status' => 'contacted',
                'is_seen' => true,
                'source' => 'product',
                'created_at' => '2026-09-18 14:30:00',
            ],
            [
                'id' => 3,
                'category_id' => null,
                'full_name' => 'Closed Quote',
                'company_name' => 'Closed Co',
                'email' => 'closed@example.test',
                'phone' => '09170000003',
                'address' => 'Closed address',
                'how_heard' => 'Search',
                'estimated_qty' => 'Closed quantity',
                'status' => 'closed',
                'is_seen' => true,
                'source' => 'home',
                'created_at' => '2026-09-18 14:31:00',
            ],
        ]);

        $component = Livewire::test(ListQuoteRequests::class)
            ->set('tableFilters', ['status' => ['value' => 'contacted']])
            ->callAction(['name' => 'export', 'context' => ['table' => true]]);

        $component->assertFileDownloaded(
            'quote-requests-20260918-143045.csv',
            null,
            'text/csv; charset=UTF-8',
        );

        $rows = $this->downloadedRows($component);

        $this->assertSame([
            'Full Name',
            'Company',
            'Email',
            'Phone',
            'Address',
            'How They Heard About Us',
            'Estimated Quantity',
            'Category',
            'Status',
            'Date Submitted',
        ], $rows[0]);
        $this->assertCount(2, $rows);
        $this->assertSame('Visible Quote', $rows[1][0]);
        $this->assertSame('Structural Steel', $rows[1][7]);
        $this->assertSame('Contacted', $rows[1][8]);
        $this->assertSame('2026-09-18 14:30', $rows[1][9]);
        $this->assertSame(str_repeat('Long quantity ', 20), $rows[1][6]);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('exports', 0);
    }

    public function test_contact_header_export_preserves_filter_scope_and_current_columns(): void
    {
        $this->signInAsAuthorizedStaff();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 9, 18, 14, 30, 45));

        ContactMessage::query()->insert([
            [
                'id' => 1,
                'full_name' => 'Unread Contact',
                'company_name' => 'Unread Co',
                'email' => 'unread@example.test',
                'phone' => '09170000001',
                'landline' => '0220000001',
                'address' => 'Unread address',
                'how_heard' => 'Search',
                'message' => 'Do not export this row.',
                'status' => 'unread',
                'is_seen' => false,
                'created_at' => '2026-09-18 14:29:00',
            ],
            [
                'id' => 2,
                'full_name' => 'Visible Contact',
                'company_name' => 'Visible Co',
                'email' => 'visible@example.test',
                'phone' => '09170000002',
                'landline' => '0220000002',
                'address' => 'Visible address',
                'how_heard' => 'Referral',
                'message' => 'Visible contact message.',
                'status' => 'read',
                'is_seen' => true,
                'created_at' => '2026-09-18 14:30:00',
            ],
        ]);

        $component = Livewire::test(ListContactMessages::class)
            ->set('tableFilters', ['status' => ['value' => 'read']])
            ->callAction(['name' => 'export', 'context' => ['table' => true]]);

        $component->assertFileDownloaded(
            'contact-messages-20260918-143045.csv',
            null,
            'text/csv; charset=UTF-8',
        );

        $rows = $this->downloadedRows($component);

        $this->assertSame([
            'Full Name',
            'Company',
            'Email',
            'Phone',
            'Landline',
            'Address',
            'How They Heard About Us',
            'Message',
            'Status',
            'Date Submitted',
        ], $rows[0]);
        $this->assertCount(2, $rows);
        $this->assertSame('Visible Contact', $rows[1][0]);
        $this->assertSame('Visible contact message.', $rows[1][7]);
        $this->assertSame('Read', $rows[1][8]);
        $this->assertSame('2026-09-18 14:30', $rows[1][9]);
    }

    public function test_referral_header_export_preserves_filter_scope_and_current_columns(): void
    {
        $this->signInAsAuthorizedStaff();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 9, 18, 14, 30, 45));

        Referral::query()->insert([
            [
                'id' => 1,
                'referrer_name' => 'New Referrer',
                'referrer_company' => 'New Co',
                'referrer_phone' => '09170000001',
                'referrer_email' => 'new@example.test',
                'contact_person' => 'New Contact',
                'referred_company' => 'New Referred Co',
                'project_type' => 'Bridge',
                'project_scale' => 'Small',
                'region' => 'North',
                'remarks' => 'Do not export this row.',
                'status' => 'new',
                'is_seen' => false,
                'created_at' => '2026-09-18 14:29:00',
            ],
            [
                'id' => 2,
                'referrer_name' => 'Visible Referrer',
                'referrer_company' => 'Visible Co',
                'referrer_phone' => '09170000002',
                'referrer_email' => 'visible@example.test',
                'contact_person' => 'Visible Contact',
                'referred_company' => 'Visible Referred Co',
                'project_type' => 'Warehouse',
                'project_scale' => 'Large',
                'region' => 'South',
                'remarks' => 'Visible referral remarks.',
                'status' => 'contacted',
                'is_seen' => true,
                'created_at' => '2026-09-18 14:30:00',
            ],
        ]);

        $component = Livewire::test(ListReferrals::class)
            ->set('tableFilters', ['status' => ['value' => 'contacted']])
            ->callAction(['name' => 'export', 'context' => ['table' => true]]);

        $component->assertFileDownloaded(
            'referrals-20260918-143045.csv',
            null,
            'text/csv; charset=UTF-8',
        );

        $rows = $this->downloadedRows($component);

        $this->assertSame([
            'Referrer Name',
            'Referrer Company',
            'Referrer Phone',
            'Referrer Email',
            'Contact Person',
            'Referred Company',
            'Project Type',
            'Project Scale',
            'Region',
            'Remarks',
            'Status',
            'Date Submitted',
        ], $rows[0]);
        $this->assertCount(2, $rows);
        $this->assertSame('Visible Referrer', $rows[1][0]);
        $this->assertSame('Visible referral remarks.', $rows[1][9]);
        $this->assertSame('Contacted', $rows[1][10]);
        $this->assertSame('2026-09-18 14:30', $rows[1][11]);
    }

    public function test_bulk_exports_use_only_selected_noncontiguous_quote_ids(): void
    {
        $this->signInAsAuthorizedStaff();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 9, 18, 14, 30, 45));

        QuoteRequest::query()->insert([
            $this->quoteRow(1, 'Selected First', 'new'),
            $this->quoteRow(2, 'Not Selected', 'contacted'),
            $this->quoteRow(3, 'Selected Third', 'closed'),
        ]);

        $component = Livewire::test(ListQuoteRequests::class)
            ->set('selectedTableRecords', [1, 3])
            ->callAction([
                'name' => 'export',
                'context' => ['table' => true, 'bulk' => true],
            ]);

        $component->assertFileDownloaded('quote-requests-20260918-143045.csv');
        $rows = $this->downloadedRows($component);

        $this->assertSame(['Selected First', 'Selected Third'], [$rows[1][0], $rows[2][0]]);
        $this->assertNotContains('Not Selected', array_column($rows, 0));
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('exports', 0);
    }

    public function test_bulk_exports_use_only_selected_noncontiguous_contact_and_referral_ids(): void
    {
        $this->signInAsAuthorizedStaff();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 9, 18, 14, 30, 45));

        ContactMessage::query()->insert([
            $this->contactRow(1, 'Selected Contact First', 'unread'),
            $this->contactRow(2, 'Not Selected Contact', 'read'),
            $this->contactRow(3, 'Selected Contact Third', 'responded'),
        ]);
        $contactComponent = Livewire::test(ListContactMessages::class)
            ->set('selectedTableRecords', [1, 3])
            ->callAction([
                'name' => 'export',
                'context' => ['table' => true, 'bulk' => true],
            ]);

        $contactComponent->assertFileDownloaded('contact-messages-20260918-143045.csv');
        $contactRows = $this->downloadedRows($contactComponent);
        $this->assertSame(['Selected Contact First', 'Selected Contact Third'], [$contactRows[1][0], $contactRows[2][0]]);
        $this->assertNotContains('Not Selected Contact', array_column($contactRows, 0));

        Referral::query()->insert([
            $this->referralRow(1, 'Selected Referrer First', 'new'),
            $this->referralRow(2, 'Not Selected Referrer', 'contacted'),
            $this->referralRow(3, 'Selected Referrer Third', 'rewarded'),
        ]);
        $referralComponent = Livewire::test(ListReferrals::class)
            ->set('selectedTableRecords', [1, 3])
            ->callAction([
                'name' => 'export',
                'context' => ['table' => true, 'bulk' => true],
            ]);

        $referralComponent->assertFileDownloaded('referrals-20260918-143045.csv');
        $referralRows = $this->downloadedRows($referralComponent);
        $this->assertSame(['Selected Referrer First', 'Selected Referrer Third'], [$referralRows[1][0], $referralRows[2][0]]);
        $this->assertNotContains('Not Selected Referrer', array_column($referralRows, 0));
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('exports', 0);
    }

    public function test_inactive_user_is_forbidden_from_all_export_resources(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);

        $this->actingAs($inactive)->get('/admin/quote-requests')->assertForbidden();
        $this->actingAs($inactive)->get('/admin/contact-messages')->assertForbidden();
        $this->actingAs($inactive)->get('/admin/referrals')->assertForbidden();
    }

    public function test_unauthorized_direct_action_call_returns_no_file(): void
    {
        $this->signInAsAuthorizedStaff();

        $component = Livewire::test(ListQuoteRequests::class);
        $action = collect($component->instance()->getTable()->getHeaderActions())
            ->first(fn ($action): bool => $action->getName() === 'export');
        $this->assertNotNull($action);
        $action->authorize(static fn (): bool => false);
        $action->clearVisibilityCache();
        $action->livewire($component->instance());

        $response = $action->call();

        if ($response instanceof BinaryFileResponse) {
            @unlink($response->getFile()->getPathname());
        }

        $this->assertNull($response);
    }

    public function test_export_failure_notification_uses_the_service_correlation_id(): void
    {
        $this->signInAsAuthorizedStaff();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 9, 18, 14, 30, 45));

        QuoteRequest::query()->insert([
            [
                ...$this->quoteRow(1, 'Invalid UTF-8', 'new'),
                'estimated_qty' => "invalid \xC3\x28 bytes",
            ],
        ]);

        $loggedContext = null;
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use (&$loggedContext): bool {
                $loggedContext = $context;

                return $message === 'Synchronous CSV export failed.'
                    && isset($context['correlation_id'])
                    && $context['resource'] === 'quote-requests';
            });

        Livewire::test(ListQuoteRequests::class)
            ->callAction(['name' => 'export', 'context' => ['table' => true]])
            ->assertNoFileDownloaded();

        $notifications = session('filament.claimed_notifications', []);
        $this->assertSame('Export failed', data_get($notifications, '0.title'));
        $this->assertSame(
            "The export could not be created (reference {$loggedContext['correlation_id']}). Please retry.",
            data_get($notifications, '0.body'),
        );
        $this->assertSame('danger', data_get($notifications, '0.status'));
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('exports', 0);
    }

    private function signInAsAuthorizedStaff(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->profile()->update(['position' => 'admin']);

        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function quoteRow(int $id, string $name, string $status): array
    {
        return [
            'id' => $id,
            'category_id' => null,
            'full_name' => $name,
            'company_name' => "{$name} Co",
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
            'phone' => '0917000000'.($id % 10),
            'address' => "{$name} address",
            'how_heard' => 'Search',
            'estimated_qty' => "Quantity for {$name}",
            'status' => $status,
            'is_seen' => $status !== 'new',
            'source' => 'home',
            'created_at' => '2026-09-18 14:30:00',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contactRow(int $id, string $name, string $status): array
    {
        return [
            'id' => $id,
            'full_name' => $name,
            'company_name' => "{$name} Co",
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
            'phone' => '0917000000'.($id % 10),
            'landline' => '022000000'.($id % 10),
            'address' => "{$name} address",
            'how_heard' => 'Search',
            'message' => "Message for {$name}",
            'status' => $status,
            'is_seen' => $status !== 'unread',
            'created_at' => '2026-09-18 14:30:00',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function referralRow(int $id, string $name, string $status): array
    {
        return [
            'id' => $id,
            'referrer_name' => $name,
            'referrer_company' => "{$name} Co",
            'referrer_phone' => '0917000000'.($id % 10),
            'referrer_email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
            'contact_person' => "Contact for {$name}",
            'referred_company' => "Referred {$name} Co",
            'project_type' => 'Warehouse',
            'project_scale' => 'Large',
            'region' => 'South',
            'remarks' => "Remarks for {$name}",
            'status' => $status,
            'is_seen' => $status !== 'new',
            'created_at' => '2026-09-18 14:30:00',
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function downloadedRows(object $component): array
    {
        $content = data_get($component->effects, 'download.content');
        $this->assertIsString($content);

        $stream = fopen('php://memory', 'rb+');
        $this->assertIsResource($stream);
        fwrite($stream, base64_decode($content, true));
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
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
