<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = User::class;

    protected static string|\UnitEnum|null $navigationGroup = 'User Access & Accounts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $recordTitleAttribute = 'name';

    // User management stays Admin-only, same as Product/ProductCategory.
    // SECURITY: previously only shouldRegisterNavigation()/canViewAny()
    // were gated here — canCreate()/canEdit()/canDelete() had no override,
    // so a non-admin could still reach /admin/users/create or
    // /admin/users/{id}/edit directly and make themselves (or anyone) an
    // admin account. AdminOnlyResource now covers all five checks.

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
