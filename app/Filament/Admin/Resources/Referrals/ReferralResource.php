<?php

namespace App\Filament\Admin\Resources\Referrals;

use App\Filament\Admin\Resources\Referrals\Pages\CreateReferral;
use App\Filament\Admin\Resources\Referrals\Pages\EditReferral;
use App\Filament\Admin\Resources\Referrals\Pages\ListReferrals;
use App\Filament\Admin\Resources\Referrals\Pages\ViewReferral;
use App\Filament\Admin\Resources\Referrals\Schemas\ReferralForm;
use App\Filament\Admin\Resources\Referrals\Schemas\ReferralInfolist;
use App\Filament\Admin\Resources\Referrals\Tables\ReferralsTable;
use App\Models\Referral;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Store Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'referrer_name';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    // Sidebar badge — mirrors Django's referrals_badge().
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
        return ReferralForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReferralInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReferralsTable::configure($table);
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
            'index' => ListReferrals::route('/'),
            'create' => CreateReferral::route('/create'),
            'view' => ViewReferral::route('/{record}'),
            'edit' => EditReferral::route('/{record}/edit'),
        ];
    }
}
