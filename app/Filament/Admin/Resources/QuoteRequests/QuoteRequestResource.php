<?php

namespace App\Filament\Admin\Resources\QuoteRequests;

use App\Filament\Admin\Resources\QuoteRequests\Pages\CreateQuoteRequest;
use App\Filament\Admin\Resources\QuoteRequests\Pages\EditQuoteRequest;
use App\Filament\Admin\Resources\QuoteRequests\Pages\ListQuoteRequests;
use App\Filament\Admin\Resources\QuoteRequests\Pages\ViewQuoteRequest;
use App\Filament\Admin\Resources\QuoteRequests\Schemas\QuoteRequestForm;
use App\Filament\Admin\Resources\QuoteRequests\Schemas\QuoteRequestInfolist;
use App\Filament\Admin\Resources\QuoteRequests\Tables\QuoteRequestsTable;
use App\Models\QuoteRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuoteRequestResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Store Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'full_name';

    // Matches Django's can_view_quote_requests() — any staff with the
    // core.view_quoterequest permission. Laravel doesn't have Django's
    // per-model permission table, so this checks the same admin-position
    // gate for now (see app/Models/User::isAdminPosition()); swap for a
    // real permission/policy check once the roles system is ported.
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    // Sidebar badge — mirrors Django's quote_requests_badge().
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_seen', false)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return QuoteRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuoteRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuoteRequestsTable::configure($table);
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
            'index' => ListQuoteRequests::route('/'),
            'create' => CreateQuoteRequest::route('/create'),
            'view' => ViewQuoteRequest::route('/{record}'),
            'edit' => EditQuoteRequest::route('/{record}/edit'),
        ];
    }
}
