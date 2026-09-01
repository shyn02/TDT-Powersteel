<?php

namespace App\Filament\Admin\Resources\SocialHighlights\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SocialHighlightsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform')
                    ->searchable(),
                TextColumn::make('tag_label')
                    ->searchable(),
                TextColumn::make('badge_label')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('link_url')
                    ->searchable(),
                TextColumn::make('embed_permalink')
                    ->searchable(),
                TextColumn::make('handle')
                    ->searchable(),
                TextColumn::make('video_file')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()->label('Archived (30-day)'),
            ])
            ->recordActions([
                ViewAction::make(),
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
