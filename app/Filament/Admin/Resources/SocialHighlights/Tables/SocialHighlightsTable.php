<?php

namespace App\Filament\Admin\Resources\SocialHighlights\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
