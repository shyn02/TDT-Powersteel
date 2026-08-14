<?php

namespace App\Filament\Admin\Resources\Referrals\Pages;

use App\Filament\Admin\Resources\Referrals\ReferralResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReferrals extends ListRecords
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
