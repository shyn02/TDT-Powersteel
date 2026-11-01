<?php

namespace App\Filament\Admin\Resources\BlogPosts\Pages;

use App\Filament\Admin\Resources\BlogPosts\BlogPostResource;
use App\Support\MediaUploadRules;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->guardCompletedUploads($data);

        return $data;
    }

    /**
     * Server/action guard: refuse creation without a completed valid cover.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function guardCompletedUploads(array $data): void
    {
        $error = MediaUploadRules::validateStoredPath(
            $data['cover_image'] ?? null,
            MediaUploadRules::DIRECTORY_BLOG,
            true
        );

        if ($error !== null) {
            throw ValidationException::withMessages(['cover_image' => $error]);
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
