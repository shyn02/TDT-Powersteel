<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ActivityLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('actor_id')
                    ->numeric(),
                TextInput::make('action')
                    ->required(),
            ]);
    }
}
