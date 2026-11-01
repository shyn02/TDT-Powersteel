<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Support\MediaUploadRules;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('image', $data)) {
            $error = MediaUploadRules::validateStoredPath(
                $data['image'] ?? null,
                MediaUploadRules::DIRECTORY_PRODUCT,
                false
            );

            if ($error !== null) {
                throw ValidationException::withMessages(['image' => $error]);
            }
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldImage = $record->getOriginal('image');

        $record->update($data);

        // Defer old-file deletion until the record transaction commits,
        // so a rollback never orphans the prior image.
        \Illuminate\Support\Facades\DB::afterCommit(function () use ($oldImage, $record): void {
            MediaUploadRules::scheduleOldFileDeletion($oldImage, $record->image);
        });

        return $record;
    }
}
