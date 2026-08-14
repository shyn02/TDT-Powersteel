<?php

namespace App\Filament\Admin\Resources\ChatMessages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ChatMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('session.client_name')
                    ->label('Session')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sender')
                    ->badge()
                    ->color(fn (string $state) => $state === 'client' ? 'info' : 'success')
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(70)
                    ->tooltip(fn ($record) => $record->message)
                    ->searchable(),
                IconColumn::make('is_read')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sender')
                    ->options([
                        'client' => 'Client',
                        'staff' => 'Staff',
                    ]),
                TernaryFilter::make('is_read'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
