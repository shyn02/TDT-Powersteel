<?php

namespace App\Filament\Admin\Resources\Referrals\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReferralForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('referrer_name')
                    ->required(),
                TextInput::make('referrer_company'),
                TextInput::make('referrer_phone')
                    ->tel()
                    ->required(),
                TextInput::make('referrer_email')
                    ->email()
                    ->required(),
                TextInput::make('contact_person')
                    ->required(),
                TextInput::make('referred_company')
                    ->required(),
                TextInput::make('project_type')
                    ->required(),
                TextInput::make('project_scale')
                    ->required(),
                TextInput::make('region')
                    ->required(),
                Textarea::make('remarks')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'rewarded' => 'Rewarded / Closed',
                    ])
                    ->required()
                    ->default('new'),
                Toggle::make('is_seen')
                    ->label('Seen'),
            ]);
    }
}
