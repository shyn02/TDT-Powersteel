<?php

namespace App\Exports;

use Closure;
use Illuminate\Database\Eloquent\Model;

final readonly class CsvExportDefinition
{
    /**
     * @param  array<int, string>  $headers
     * @param  Closure(Model): array<int, mixed>  $row
     */
    public function __construct(
        public string $resource,
        public array $headers,
        public Closure $row,
    ) {}
}
