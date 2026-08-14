<?php

namespace App\Filament\Admin\Resources\SystemSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SystemSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('max_active_chats_per_rep')
                    ->label('Max active chats per rep')
                    ->helperText('Controls the Live Chat claim-queue capacity for each Sales Rep.')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(5),
            ]);
    }
}
