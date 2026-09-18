<?php

namespace App\Filament\Exports;

use App\Exports\CsvExportDefinition;
use Illuminate\Database\Eloquent\Model;

class ContactMessageExporter
{
    public static function definition(): CsvExportDefinition
    {
        return new CsvExportDefinition(
            resource: 'contact-messages',
            headers: [
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
            ],
            row: static fn (Model $record): array => [
                $record->full_name,
                $record->company_name,
                $record->email,
                $record->phone,
                $record->landline,
                $record->address,
                $record->how_heard,
                $record->message,
                ucfirst((string) $record->status),
                $record->created_at?->format('Y-m-d H:i'),
            ],
        );
    }
}
