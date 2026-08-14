<?php

namespace App\Filament\Exports;

use App\Models\QuoteRequest;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

// Replaces Django's export_as_csv action + export_excel_view. Attached as a
// header ExportAction (exports whatever the current filtered table view is
// showing) and a toolbar ExportBulkAction (exports just the selected rows).
class QuoteRequestExporter extends Exporter
{
    protected static ?string $model = QuoteRequest::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('full_name')->label('Full Name'),
            ExportColumn::make('company_name')->label('Company'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('address'),
            ExportColumn::make('how_heard')->label('How They Heard About Us'),
            ExportColumn::make('estimated_qty')->label('Estimated Quantity'),
            ExportColumn::make('category.name')->label('Category'),
            ExportColumn::make('status')->formatStateUsing(fn (string $state) => ucfirst($state)),
            ExportColumn::make('created_at')
                ->label('Date Submitted')
                ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your quote requests export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
