<?php

namespace App\Filament\Exports;

use App\Models\Referral;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ReferralExporter extends Exporter
{
    protected static ?string $model = Referral::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('referrer_name')->label('Referrer Name'),
            ExportColumn::make('referrer_company')->label('Referrer Company'),
            ExportColumn::make('referrer_phone')->label('Referrer Phone'),
            ExportColumn::make('referrer_email')->label('Referrer Email'),
            ExportColumn::make('contact_person')->label('Contact Person'),
            ExportColumn::make('referred_company')->label('Referred Company'),
            ExportColumn::make('project_type')->label('Project Type'),
            ExportColumn::make('project_scale')->label('Project Scale'),
            ExportColumn::make('region'),
            ExportColumn::make('remarks'),
            ExportColumn::make('status')->formatStateUsing(fn (string $state) => ucfirst($state)),
            ExportColumn::make('created_at')
                ->label('Date Submitted')
                ->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your referrals export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
