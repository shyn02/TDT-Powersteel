<?php

namespace App\Filament\Admin\Resources\UserProfiles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('position')
                    ->required()
                    ->default('sales_rep'),
                TextInput::make('contact_number'),
            ]);
    }
}
