<?php

namespace App\Filament\Admin\Resources\Projects\Pages;

use App\Filament\Admin\Resources\Projects\ProjectResource;
use App\Support\MediaUploadRules;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->guardCompletedUploads($data);

        return $data;
    }

    /**
     * Server/action guard: refuse creation without a completed valid image.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function guardCompletedUploads(array $data): void
    {
        $error = MediaUploadRules::validateStoredPath(
            $data['image'] ?? null,
            MediaUploadRules::DIRECTORY_PROJECT,
            true
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
