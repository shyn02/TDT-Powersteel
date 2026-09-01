<?php

namespace App\Filament\Admin\Resources\ChatMessages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
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
                TrashedFilter::make()->label('Archived (30-day)'),
                SelectFilter::make('sender')
                    ->options([
                        'client' => 'Client',
                        'staff' => 'Staff',
                    ]),
                TernaryFilter::make('is_read'),
            ])
            ->recordActions([
                EditAction::make(),
                RestoreAction::make()->label('Restore')->color('success'),
                ForceDeleteAction::make()->label('Delete permanently'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Archive (30 days)'),
                    RestoreBulkAction::make()->label('Restore'),
                    ForceDeleteBulkAction::make()->label('Delete permanently'),
                ]),
            ]);
    }
}
