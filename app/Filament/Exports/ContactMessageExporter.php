<?php

namespace App\Filament\Exports;

use App\Models\ContactMessage;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ContactMessageExporter extends Exporter
{
    protected static ?string $model = ContactMessage::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('full_name')->label('Full Name'),
            ExportColumn::make('company_name')->label('Company'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('landline'),
            ExportColumn::make('address'),
            ExportColumn::make('how_heard')->label('How They Heard About Us'),
            ExportColumn::make('message'),
            ExportColumn::make('status')->formatStateUsing(fn (string $state) => ucfirst($state)),
            ExportColumn::make('created_at')
                ->label('Date Submitted')
                ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your contact messages export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
