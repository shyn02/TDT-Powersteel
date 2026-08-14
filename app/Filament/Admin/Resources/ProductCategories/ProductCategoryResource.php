<?php

namespace App\Filament\Admin\Resources\ProductCategories;

use App\Filament\Admin\Resources\ProductCategories\Pages\CreateProductCategory;
use App\Filament\Admin\Resources\ProductCategories\Pages\EditProductCategory;
use App\Filament\Admin\Resources\ProductCategories\Pages\ListProductCategories;
use App\Filament\Admin\Resources\ProductCategories\Schemas\ProductCategoryForm;
use App\Filament\Admin\Resources\ProductCategories\Tables\ProductCategoriesTable;
use App\Models\ProductCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Store Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    // Matches Django's can_view_product_categories() — admin position only.
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdminPosition() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdminPosition() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ProductCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\ProductCategories\RelationManagers\ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductCategories::route('/'),
            'create' => CreateProductCategory::route('/create'),
            'edit' => EditProductCategory::route('/{record}/edit'),
        ];
    }
}
