<?php

namespace App\Filament\Admin\Resources\UserProfiles;

use App\Filament\Admin\Resources\UserProfiles\Pages\CreateUserProfile;
use App\Filament\Admin\Resources\UserProfiles\Pages\EditUserProfile;
use App\Filament\Admin\Resources\UserProfiles\Pages\ListUserProfiles;
use App\Filament\Admin\Resources\UserProfiles\Schemas\UserProfileForm;
use App\Filament\Admin\Resources\UserProfiles\Tables\UserProfilesTable;
use App\Models\UserProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserProfileResource extends Resource
{
    protected static ?string $model = UserProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    // Django never registers UserProfile as its own admin screen — it's
    // edit-only via the inline on UserAdmin. We keep the resource (handy
    // for artisan/debugging) but hide it from the sidebar; manage
    // position/contact number from the User's own edit page instead.
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return UserProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserProfilesTable::configure($table);
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
            'index' => ListUserProfiles::route('/'),
            'create' => CreateUserProfile::route('/create'),
            'edit' => EditUserProfile::route('/{record}/edit'),
        ];
    }
}
