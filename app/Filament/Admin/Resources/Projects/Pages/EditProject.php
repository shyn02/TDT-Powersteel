<?php

namespace App\Filament\Admin\Resources\Projects\Pages;

use App\Filament\Admin\Resources\Projects\ProjectResource;
use App\Support\MediaUploadRules;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // migration_key is immutable: never update it from form input.
        unset($data['migration_key']);

        if (array_key_exists('image', $data)) {
            $error = MediaUploadRules::validateStoredPath(
                $data['image'] ?? null,
                MediaUploadRules::DIRECTORY_PROJECT,
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
        DB::afterCommit(function () use ($oldImage, $record): void {
            MediaUploadRules::scheduleOldFileDeletion($oldImage, $record->image);
        });

        return $record;
    }
}
