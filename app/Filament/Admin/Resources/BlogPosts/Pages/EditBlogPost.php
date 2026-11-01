<?php

namespace App\Filament\Admin\Resources\BlogPosts\Pages;

use App\Filament\Admin\Resources\BlogPosts\BlogPostResource;
use App\Support\MediaUploadRules;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('cover_image', $data)) {
            $error = MediaUploadRules::validateStoredPath(
                $data['cover_image'] ?? null,
                MediaUploadRules::DIRECTORY_BLOG,
                false
            );

            if ($error !== null) {
                throw ValidationException::withMessages(['cover_image' => $error]);
            }
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldCover = $record->getOriginal('cover_image');

        $record->update($data);

        // Defer old-file deletion until the record transaction commits,
        // so a rollback never orphans the prior cover.
        \Illuminate\Support\Facades\DB::afterCommit(function () use ($oldCover, $record): void {
            MediaUploadRules::scheduleOldFileDeletion($oldCover, $record->cover_image);
        });

        return $record;
    }
}
