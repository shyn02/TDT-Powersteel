<?php

namespace App\Filament\Admin\Resources\SocialHighlights;

use App\Filament\Admin\Resources\SocialHighlights\Pages\CreateSocialHighlight;
use App\Filament\Admin\Resources\SocialHighlights\Pages\EditSocialHighlight;
use App\Filament\Admin\Resources\SocialHighlights\Pages\ListSocialHighlights;
use App\Filament\Admin\Resources\SocialHighlights\Schemas\SocialHighlightForm;
use App\Filament\Admin\Resources\SocialHighlights\Schemas\SocialHighlightInfolist;
use App\Filament\Admin\Resources\SocialHighlights\Tables\SocialHighlightsTable;
use App\Models\SocialHighlight;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SocialHighlightResource extends Resource
{
    protected static ?string $model = SocialHighlight::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Content Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return SocialHighlightForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SocialHighlightInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SocialHighlightsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialHighlights::route('/'),
            'create' => CreateSocialHighlight::route('/create'),
            'edit' => EditSocialHighlight::route('/{record}/edit'),
        ];
    }
}