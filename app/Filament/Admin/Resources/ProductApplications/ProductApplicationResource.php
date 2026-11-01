<?php

namespace App\Filament\Admin\Resources\ProductApplications;

use App\Filament\Admin\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\ProductApplications\Pages\CreateProductApplication;
use App\Filament\Admin\Resources\ProductApplications\Pages\EditProductApplication;
use App\Filament\Admin\Resources\ProductApplications\Pages\EditProductApplicationPositions;
use App\Filament\Admin\Resources\ProductApplications\Pages\ListProductApplications;
use App\Filament\Admin\Resources\ProductApplications\Schemas\ProductApplicationForm;
use App\Filament\Admin\Resources\ProductApplications\Tables\ProductApplicationsTable;
use App\Models\ProductApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductApplicationResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = ProductApplication::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog Management';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $modelLabel = 'Product Application';

    protected static ?string $pluralModelLabel = 'Product Applications';

    public static function form(Schema $schema): Schema
    {
        return ProductApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductApplications::route('/'),
            'create' => CreateProductApplication::route('/create'),
            'edit' => EditProductApplication::route('/{record}/edit'),
            'positions' => EditProductApplicationPositions::route('/positions'),
        ];
    }
}
