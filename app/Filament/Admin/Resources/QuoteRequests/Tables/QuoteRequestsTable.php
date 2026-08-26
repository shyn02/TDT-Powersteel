<?php

namespace App\Filament\Admin\Resources\QuoteRequests\Tables;

use App\Filament\Exports\QuoteRequestExporter;
use App\Models\ActivityLog;
use App\Models\QuoteRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class QuoteRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Same "New" > "Contacted" > "Closed" priority ordering as
            // Django's get_ordering() (raw Case/When) — CASE WHEN works
            // the same way across MySQL/SQLite/Postgres, unlike FIELD().
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByRaw(
                "CASE status WHEN 'new' THEN 0 WHEN 'contacted' THEN 1 WHEN 'closed' THEN 2 ELSE 3 END asc, created_at desc"
            ))
            ->columns([
                // Messenger-style bold name until is_seen flips to true (see ViewQuoteRequest::mount()).
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
                TextColumn::make('estimated_qty')
                    ->label('Estimated Quantity')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->estimated_qty)
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'new' => 'warning',
                        'contacted' => 'info',
                        'closed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'closed' => 'Closed',
                    ]),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
                SelectFilter::make('how_heard')
                    ->label('How they heard about us')
                    ->options(fn () => \App\Models\QuoteRequest::query()
                        ->whereNotNull('how_heard')
                        ->distinct()
                        ->pluck('how_heard', 'how_heard')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()->exporter(QuoteRequestExporter::class),

                Action::make('clear_all_data')
                    ->label('Clear All Data')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clear all Quote Requests?')
                    ->modalDescription('This will permanently delete ALL quote requests (home / product / quote). This cannot be undone. Please export first if you need a backup.')
                    ->modalSubmitActionLabel('Yes, clear all')
                    ->visible(fn () => auth()->user()?->isAdminPosition() ?? false)
                    ->action(function () {
                        $count = QuoteRequest::count();
                        if ($count === 0) {
                            Notification::make()->title('No quote requests to clear.')->warning()->send();
                            return;
                        }
                        QuoteRequest::query()->delete();
                        ActivityLog::log(Auth::user(), "Cleared all Quote Requests ({$count} records) via Quote Requests page");
                        Notification::make()->title("Successfully cleared {$count} quote request(s).")->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make()->exporter(QuoteRequestExporter::class),

                    BulkAction::make('mark_as_new')
                        ->label("Mark as 'New'")
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'new', 'is_seen' => false]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_contacted')
                        ->label("Mark as 'Contacted'")
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'contacted', 'is_seen' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_closed')
                        ->label("Mark as 'Closed'")
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'closed', 'is_seen' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_seen')
                        ->label('Mark as Seen')
                        ->action(fn (Collection $records) => $records->where('is_seen', false)->each->update(['is_seen' => true]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
