<?php

namespace App\Filament\Exports;

use App\Exports\CsvExportDefinition;
use Illuminate\Database\Eloquent\Model;

class QuoteRequestExporter
{
    public static function definition(): CsvExportDefinition
    {
        return new CsvExportDefinition(
            resource: 'quote-requests',
            headers: [
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
            ],
            row: static fn (Model $record): array => [
                $record->full_name,
                $record->company_name,
                $record->email,
                $record->phone,
                $record->address,
                $record->how_heard,
                $record->estimated_qty,
                $record->category?->name,
                ucfirst((string) $record->status),
                $record->created_at?->format('Y-m-d H:i'),
            ],
        );
    }
}
