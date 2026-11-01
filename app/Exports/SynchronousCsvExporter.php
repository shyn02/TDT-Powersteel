<?php

namespace App\Exports;

use BackedEnum;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Stringable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final class SynchronousCsvExporter
{
    private const CHUNK_SIZE = 500;

    public function __construct(
        private readonly ?string $temporaryDirectory = null,
        private readonly ?Closure $chmod = null,
        private readonly ?Closure $unlink = null,
        private readonly ?bool $permissionsMeaningful = null,
    ) {}

    public function download(
        Builder $query,
        CsvExportDefinition $definition,
        CarbonInterface $at,
    ): BinaryFileResponse {
        $correlationId = (string) Str::uuid();
        $directory = $this->temporaryDirectory ?? storage_path('app/tmp/exports');
        $filename = "{$definition->resource}-{$at->format('Ymd-His')}.csv";
        $path = $directory.DIRECTORY_SEPARATOR.Str::uuid().'.csv';
        $handle = null;

        try {
            $this->ensurePrivateDirectory($directory);

            $handle = fopen($path, 'xb');
            if ($handle === false) {
                throw new RuntimeException('Unable to create the temporary CSV file.');
            }
            $this->setPrivatePermissions($path, 0600);

            $this->writeRow($handle, $definition->headers);

            $exportQuery = clone $query;
            $key = $exportQuery->getModel()->getKeyName();
            $exportQuery
                ->reorder()
                ->orderBy($key)
                ->chunkById(
                    self::CHUNK_SIZE,
                    function (Collection $records) use ($definition, $handle): void {
                        foreach ($records as $record) {
                            $this->writeRow($handle, ($definition->row)($record));
                        }
                    },
                    $key,
                    $key,
                );

            if (! fflush($handle)) {
                throw new RuntimeException('Unable to flush the temporary CSV file.');
            }
            fclose($handle);
            $handle = null;

            $response = response()->download($path, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, private',
            ])->setPrivate()->deleteFileAfterSend(true);

            return $response;
        } catch (Throwable $exception) {
            if (is_resource($handle)) {
                fclose($handle);
                $handle = null;
            }

            $cleanupException = null;
            try {
                $this->removeTemporaryFile($path);
            } catch (Throwable $cleanupFailure) {
                $cleanupException = $cleanupFailure;
            }

            $context = [
                'correlation_id' => $correlationId,
                'resource' => $definition->resource,
                'exception' => $exception::class,
            ];
            if ($cleanupException !== null) {
                $context['cleanup_exception'] = $cleanupException::class;
            }

            Log::error('Synchronous CSV export failed.', $context);

            throw new CsvExportFailure($correlationId, $definition->resource, $exception);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }

        }
    }

    private function ensurePrivateDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the temporary CSV directory.');
        }

        $this->setPrivatePermissions($directory, 0700);
    }

    /**
     * @param  resource  $handle
     * @param  array<int, mixed>  $values
     */
    private function writeRow($handle, array $values): void
    {
        $cells = array_map(fn (mixed $value): string => $this->sanitizeCell($value), $values);

        if (fputcsv($handle, $cells, ',', '"', '') === false) {
            throw new RuntimeException('Unable to write the temporary CSV file.');
        }
    }

    private function sanitizeCell(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        } elseif ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if ($value === null) {
            return '';
        }

        if (! is_scalar($value)) {
            throw new RuntimeException('CSV row values must be scalar.');
        }

        $cell = (string) $value;

        if (is_string($value) && ! mb_check_encoding($value, 'UTF-8')) {
            throw new RuntimeException('CSV row contains invalid UTF-8.');
        }

        if (is_string($value) && $cell !== '' && str_contains('=+-@', $cell[0])) {
            return "'{$cell}";
        }

        return $cell;
    }

    private function setPrivatePermissions(string $path, int $mode): void
    {
        if (! $this->permissionsAreMeaningful()) {
            return;
        }

        $chmod = $this->chmod ?? static fn (string $path, int $mode): bool => chmod($path, $mode);
        if (! $chmod($path, $mode)) {
            throw new RuntimeException('Unable to establish private temporary-file permissions.');
        }

        $permissions = fileperms($path);
        if ($permissions === false || ($permissions & 0777) !== $mode) {
            throw new RuntimeException('Temporary-file permissions are not private.');
        }
    }

    private function permissionsAreMeaningful(): bool
    {
        return $this->permissionsMeaningful ?? PHP_OS_FAMILY !== 'Windows';
    }

    private function removeTemporaryFile(string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        $unlink = $this->unlink ?? static fn (string $path): bool => unlink($path);
        if (! $unlink($path) && is_file($path)) {
            throw new RuntimeException('Unable to remove the temporary CSV file.');
        }
    }
}
