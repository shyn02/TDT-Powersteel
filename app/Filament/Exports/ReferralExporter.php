<?php

namespace App\Filament\Exports;

use App\Exports\CsvExportDefinition;
use Illuminate\Database\Eloquent\Model;

class ReferralExporter
{
    public static function definition(): CsvExportDefinition
    {
        return new CsvExportDefinition(
            resource: 'referrals',
            headers: [
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
            ],
            row: static fn (Model $record): array => [
                $record->referrer_name,
                $record->referrer_company,
                $record->referrer_phone,
                $record->referrer_email,
                $record->contact_person,
                $record->referred_company,
                $record->project_type,
                $record->project_scale,
                $record->region,
                $record->remarks,
                ucfirst((string) $record->status),
                $record->created_at?->format('Y-m-d H:i'),
            ],
        );
    }
}
