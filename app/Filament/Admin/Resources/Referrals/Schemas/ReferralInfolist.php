<?php

namespace App\Filament\Admin\Resources\Referrals\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ReferralInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('referrer_name')->label('Referrer Name'),
                TextEntry::make('referrer_company')->label('Referrer Company')->placeholder('-'),
                TextEntry::make('referrer_phone')->label('Referrer Phone'),
                TextEntry::make('referrer_email')->label('Referrer Email'),
                TextEntry::make('contact_person')->label('Contact Person'),
                TextEntry::make('referred_company')->label('Referred Company'),
                TextEntry::make('project_type')->label('Project Type'),
                TextEntry::make('project_scale')->label('Project Scale'),
                TextEntry::make('region'),
                TextEntry::make('remarks')->columnSpanFull()->placeholder('-'),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'rewarded' ? 'Rewarded' : ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'new' => 'warning',
                        'contacted' => 'info',
                        'rewarded' => 'success',
                        default => 'gray',
                    }),
                IconEntry::make('is_seen')->boolean(),
                TextEntry::make('created_at')->dateTime(),
            ]);
    }
}
