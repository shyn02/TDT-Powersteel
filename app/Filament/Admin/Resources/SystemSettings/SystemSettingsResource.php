<?php

namespace App\Filament\Admin\Resources\SystemSettings;

use App\Filament\Admin\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\SystemSettings\Pages\CreateSystemSettings;
use App\Filament\Admin\Resources\SystemSettings\Pages\EditSystemSettings;
use App\Filament\Admin\Resources\SystemSettings\Pages\ListSystemSettings;
use App\Filament\Admin\Resources\SystemSettings\Pages\ViewSystemSettings;
use App\Filament\Admin\Resources\SystemSettings\Schemas\SystemSettingsForm;
use App\Filament\Admin\Resources\SystemSettings\Schemas\SystemSettingsInfolist;
use App\Filament\Admin\Resources\SystemSettings\Tables\SystemSettingsTable;
use App\Models\SystemSettings;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SystemSettingsResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = SystemSettings::class;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Chat Settings';

    // Superadmin/admin-only, same gate as Django's superadmin_required
    // (approximated here via isAdminPosition() — see app/Models/User.php).
    // SECURITY: canEdit() previously had no override at all, so any
    // authenticated staff could reach /admin/system-settings/1/edit
    // directly even though it was hidden from their sidebar.
    // canCreate/canDelete stay hardcoded false below (singleton row,
    // blocked for admins too) — these two override the trait on purpose.

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return SystemSettingsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SystemSettingsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SystemSettingsTable::configure($table);
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
            'index' => ListSystemSettings::route('/'),
            'create' => CreateSystemSettings::route('/create'),
            'view' => ViewSystemSettings::route('/{record}'),
            'edit' => EditSystemSettings::route('/{record}/edit'),
        ];
    }
}
