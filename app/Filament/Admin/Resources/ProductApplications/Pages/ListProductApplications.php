<?php

namespace App\Filament\Admin\Resources\ProductApplications\Pages;

use App\Filament\Admin\Resources\ProductApplications\ProductApplicationResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListProductApplications extends ListRecords
{
    protected static string $resource = ProductApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('massMove')
                ->label('Mass move')
                ->icon(Heroicon::OutlinedArrowsPointingOut)
                ->color('gray')
                ->url(EditProductApplicationPositions::getUrl()),
            CreateAction::make(),
        ];
    }
}
