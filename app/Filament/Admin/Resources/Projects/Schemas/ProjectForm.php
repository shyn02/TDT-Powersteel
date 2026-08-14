<?php

namespace App\Filament\Admin\Resources\Projects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date_added')
                    ->required(),
                TextInput::make('contractor')
                    ->required(),
                Textarea::make('project_name')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->default('for_bidding'),
                Toggle::make('is_priority')
                    ->required(),
                TextInput::make('encoded_by')
                    ->numeric(),
            ]);
    }
}
