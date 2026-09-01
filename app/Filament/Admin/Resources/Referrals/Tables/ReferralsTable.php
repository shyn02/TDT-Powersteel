<?php

namespace App\Filament\Admin\Resources\Referrals\Tables;

use App\Filament\Exports\ReferralExporter;
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

class ReferralsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByRaw(
                "CASE status WHEN 'new' THEN 0 WHEN 'contacted' THEN 1 WHEN 'rewarded' THEN 2 ELSE 3 END asc, created_at desc"
            ))
            ->columns([
                TextColumn::make('referrer_name')
                    ->label('Referrer Name')
                    ->searchable()
                    ->sortable()
                    ->weight(fn ($record) => $record->is_seen ? FontWeight::Normal : FontWeight::Bold),
                TextColumn::make('referrer_email')
                    ->label('Referrer Email')
                    ->searchable(),
                TextColumn::make('referred_company')
                    ->label('Referred Company')
                    ->searchable(),
                TextColumn::make('contact_person')
                    ->label('Contact Person')
                    ->searchable(),
                TextColumn::make('project_type')
                    ->label('Project Type'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'rewarded' ? 'Rewarded' : ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'new' => 'warning',
                        'contacted' => 'info',
                        'rewarded' => 'success',
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
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'rewarded' => 'Rewarded / Closed',
                    ]),
                SelectFilter::make('project_type')
                    ->options(fn () => \App\Models\Referral::query()
                        ->whereNotNull('project_type')
                        ->distinct()
                        ->pluck('project_type', 'project_type')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make()->label('Restore')->color('success'),
                ForceDeleteAction::make()->label('Delete permanently'),
            ])
            ->headerActions([
                ExportAction::make()->exporter(ReferralExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Archive (30 days)'),
                    RestoreBulkAction::make()->label('Restore'),
                    ForceDeleteBulkAction::make()->label('Delete permanently'),
                    ExportBulkAction::make()->exporter(ReferralExporter::class),

                    BulkAction::make('mark_as_new')
                        ->label("Mark as 'New'")
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'new', 'is_seen' => false]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_contacted')
                        ->label("Mark as 'Contacted'")
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'contacted', 'is_seen' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_rewarded')
                        ->label("Mark as 'Rewarded / Closed'")
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'rewarded', 'is_seen' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_as_seen')
                        ->label('Mark as Seen')
                        ->action(fn (Collection $records) => $records->where('is_seen', false)->each->update(['is_seen' => true]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
