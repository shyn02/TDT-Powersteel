<?php

namespace App\Filament\Admin\Resources\PasswordResetRequests;

use App\Filament\Admin\Resources\PasswordResetRequests\Pages\CreatePasswordResetRequest;
use App\Filament\Admin\Resources\PasswordResetRequests\Pages\EditPasswordResetRequest;
use App\Filament\Admin\Resources\PasswordResetRequests\Pages\ListPasswordResetRequests;
use App\Filament\Admin\Resources\PasswordResetRequests\Schemas\PasswordResetRequestForm;
use App\Filament\Admin\Resources\PasswordResetRequests\Tables\PasswordResetRequestsTable;
use App\Models\PasswordResetRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PasswordResetRequestResource extends Resource
{
    protected static ?string $model = PasswordResetRequest::class;

    protected static string|\UnitEnum|null $navigationGroup = 'User Access & Accounts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'php artisan make:filament-resource Project --generate';

    public static function form(Schema $schema): Schema
    {
        return PasswordResetRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PasswordResetRequestsTable::configure($table);
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
            'index' => ListPasswordResetRequests::route('/'),
            'create' => CreatePasswordResetRequest::route('/create'),
            'edit' => EditPasswordResetRequest::route('/{record}/edit'),
        ];
    }
}
