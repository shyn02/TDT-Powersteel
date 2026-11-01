<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Support\MediaUploadRules;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->guardCompletedUploads($data);

        return $data;
    }

    /**
     * Server/action guard: refuse creation with an incomplete or invalid upload.
     * Product image is optional (legacy rows have NULL), but when present it
     * must be a completed permanent path under the products directory.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function guardCompletedUploads(array $data): void
    {
        $error = MediaUploadRules::validateStoredPath(
            $data['image'] ?? null,
            MediaUploadRules::DIRECTORY_PRODUCT,
            false
        );

        if ($error !== null) {
            throw ValidationException::withMessages(['image' => $error]);
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
