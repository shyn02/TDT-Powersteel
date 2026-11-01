<?php

namespace App\Exports;

use RuntimeException;
use Throwable;

final class CsvExportFailure extends RuntimeException
{
    public function __construct(
        private readonly string $correlationId,
        private readonly string $resource,
        Throwable $previous,
    ) {
        parent::__construct("CSV export failed. Reference {$correlationId}.", 0, $previous);
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function resource(): string
    {
        return $this->resource;
    }
}
