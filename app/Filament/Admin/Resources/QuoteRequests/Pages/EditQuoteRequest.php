<?php

namespace App\Filament\Admin\Resources\QuoteRequests\Pages;

use App\Filament\Admin\Resources\QuoteRequests\QuoteRequestResource;
use App\Filament\Concerns\MarksSeenOnView;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditQuoteRequest extends EditRecord
{
    use MarksSeenOnView;

    protected static string $resource = QuoteRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
