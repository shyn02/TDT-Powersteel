<?php

namespace App\Filament\Admin\Resources\ContactMessages\Tables;

use App\Filament\Exports\ContactMessageExporter;
use App\Models\ContactMessage;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByRaw(
                "CASE status WHEN 'unread' THEN 0 WHEN 'read' THEN 1 WHEN 'responded' THEN 2 ELSE 3 END asc, created_at desc"
            ))
            ->columns([
                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable()
                    ->weight(fn ($record) => $record->is_seen ? FontWeight::Normal : FontWeight::Bold),
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->message)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'unread' => 'warning',
                        'read' => 'info',
                        'responded' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()->label('Archived (30-day)'),
                SelectFilter::make('status')
                    ->options([
                        'unread' => 'Unread',
                        'read' => 'Read',
                        'responded' => 'Responded',
                    ]),
                SelectFilter::make('how_heard')
                    ->label('How they heard about us')
                    ->options(fn () => ContactMessage::query()
                        ->whereNotNull('how_heard')
                        ->distinct()
                        ->pluck('how_heard', 'how_heard')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make()->label('Restore')->color('success'),
                ForceDeleteAction::make()->label('Delete permanently'),
            ])
            ->headerActions([
                ExportAction::make()->exporter(ContactMessageExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Archive (30 days)'),
                    RestoreBulkAction::make()->label('Restore'),
                    ForceDeleteBulkAction::make()->label('Delete permanently'),
                    ExportBulkAction::make()->exporter(ContactMessageExporter::class),

                    BulkAction::make('mark_as_unread')
                        ->label("Mark as 'Unread'")
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'unread', 'is_seen' => false]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_read')
                        ->label("Mark as 'Read'")
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'read', 'is_seen' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_responded')
                        ->label("Mark as 'Responded'")
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'responded', 'is_seen' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_seen')
                        ->label('Mark as Seen')
                        ->action(fn (Collection $records) => $records->where('is_seen', false)->each->update(['is_seen' => true]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
