<?php

namespace App\Filament\Admin\Resources\ProductCategories\Pages;

use App\Filament\Admin\Resources\ProductCategories\ProductCategoryResource;
use App\Support\MediaUploadRules;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProductCategory extends CreateRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->guardCompletedUploads($data);

        return $data;
    }

    /**
     * Server/action guard: refuse creation without a completed valid upload.
     * Tile image is required; banner is optional but must be valid when present.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function guardCompletedUploads(array $data): void
    {
        $failures = [];

        $tileError = MediaUploadRules::validateStoredPath(
            $data['image'] ?? null,
            MediaUploadRules::DIRECTORY_CATEGORY,
            true
        );
        if ($tileError !== null) {
            $failures['image'] = $tileError;
        }

        $bannerError = MediaUploadRules::validateStoredPath(
            $data['banner_image'] ?? null,
            MediaUploadRules::DIRECTORY_CATEGORY_BANNER,
            false
        );
        if ($bannerError !== null) {
            $failures['banner_image'] = $bannerError;
        }

        if ($failures !== []) {
            throw ValidationException::withMessages($failures);
        }
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->extraAttributes([
            // Disable Create while a FileUpload is in flight (Livewire upload state).
            'wire:loading.attr' => 'disabled',
            'wire:target' => 'data',
        ]);
    }
}
