<?php

namespace App\Filament\Admin\Resources\ProductCategories\Pages;

use App\Filament\Admin\Resources\ProductCategories\ProductCategoryResource;
use App\Support\MediaUploadRules;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditProductCategory extends EditRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $failures = [];

        if (array_key_exists('image', $data)) {
            $error = MediaUploadRules::validateStoredPath(
                $data['image'] ?? null,
                MediaUploadRules::DIRECTORY_CATEGORY,
                false
            );
            if ($error !== null) {
                $failures['image'] = $error;
            }
        }

        if (array_key_exists('banner_image', $data)) {
            $error = MediaUploadRules::validateStoredPath(
                $data['banner_image'] ?? null,
                MediaUploadRules::DIRECTORY_CATEGORY_BANNER,
                false
            );
            if ($error !== null) {
                $failures['banner_image'] = $error;
            }
        }

        if ($failures !== []) {
            throw ValidationException::withMessages($failures);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldImage = $record->getOriginal('image');
        $oldBanner = $record->getOriginal('banner_image');

        $record->update($data);

        // Defer old-file deletion until the record transaction commits,
        // so a rollback never orphans the prior image.
        \Illuminate\Support\Facades\DB::afterCommit(function () use ($oldImage, $record): void {
            MediaUploadRules::scheduleOldFileDeletion($oldImage, $record->image);
        });
        \Illuminate\Support\Facades\DB::afterCommit(function () use ($oldBanner, $record): void {
            MediaUploadRules::scheduleOldFileDeletion($oldBanner, $record->banner_image);
        });

        return $record;
    }
}
