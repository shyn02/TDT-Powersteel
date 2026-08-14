<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('actor_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('action'),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
