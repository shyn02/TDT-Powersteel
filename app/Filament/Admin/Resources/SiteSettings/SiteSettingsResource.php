<?php

namespace App\Filament\Admin\Resources\SiteSettings;

use App\Filament\Admin\Resources\SiteSettings\Pages\CreateSiteSettings;
use App\Filament\Admin\Resources\SiteSettings\Pages\EditSiteSettings;
use App\Filament\Admin\Resources\SiteSettings\Pages\ListSiteSettings;
use App\Filament\Admin\Resources\SiteSettings\Pages\ViewSiteSettings;
use App\Filament\Admin\Resources\SiteSettings\Schemas\SiteSettingsForm;
use App\Filament\Admin\Resources\SiteSettings\Schemas\SiteSettingsInfolist;
use App\Filament\Admin\Resources\SiteSettings\Tables\SiteSettingsTable;
use App\Models\SiteSettings;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SiteSettingsResource extends Resource
{
    protected static ?string $model = SiteSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    // Superseded by App\Filament\Admin\Pages\Settings (the tabbed
    // General/Security/Notifications/Regional/System Info/Tools page
    // that mirrors Django's settings_view()). Kept registered so the
    // model/table/artisan commands still work, just hidden from the
    // sidebar to avoid two competing "Settings" entries.
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return SiteSettingsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SiteSettingsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteSettingsTable::configure($table);
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
            'index' => ListSiteSettings::route('/'),
            'create' => CreateSiteSettings::route('/create'),
            'view' => ViewSiteSettings::route('/{record}'),
            'edit' => EditSiteSettings::route('/{record}/edit'),
        ];
    }
}
