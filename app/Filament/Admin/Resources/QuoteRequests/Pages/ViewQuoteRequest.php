<?php

namespace App\Filament\Admin\Resources\QuoteRequests\Pages;

use App\Filament\Admin\Resources\QuoteRequests\QuoteRequestResource;
use App\Filament\Concerns\MarksSeenOnView;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuoteRequest extends ViewRecord
{
    use MarksSeenOnView;

    protected static string $resource = QuoteRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
