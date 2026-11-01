<?php

namespace App\Filament\Admin\Resources\ProductApplications\Pages;

use App\Filament\Admin\Resources\ProductApplications\ProductApplicationResource;
use App\Support\ProductApplicationMedia;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProductApplication extends CreateRecord
{
    protected static string $resource = ProductApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->guardCompletedUploads($data);

        return $data;
    }

    /**
     * Server/action guard: a thumbnail or video that is still a Livewire
     * temporary upload (or missing after a failed move) must never be saved
     * as the record's media path.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function guardCompletedUploads(array $data): void
    {
        $errors = ProductApplicationMedia::errors($data, false);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
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
