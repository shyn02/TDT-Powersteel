<?php

namespace App\Filament\Admin\Resources\ProductApplications\Pages;

use App\Filament\Admin\Resources\ProductApplications\ProductApplicationResource;
use App\Support\MediaUploadRules;
use App\Support\ProductApplicationMedia;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditProductApplication extends EditRecord
{
    protected static string $resource = ProductApplicationResource::class;

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

        $errors = ProductApplicationMedia::errors($data, true);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldImage = $record->getOriginal('image');
        $oldVideo = $record->getOriginal('video_file');

        $record->update($data);

        // Defer old-file deletion until the record transaction commits, so a
        // rollback never orphans the previous media.
        DB::afterCommit(function () use ($oldImage, $record): void {
            MediaUploadRules::scheduleOldFileDeletion($oldImage, $record->image);
        });
        DB::afterCommit(function () use ($oldVideo, $record): void {
            MediaUploadRules::scheduleOldFileDeletion($oldVideo, $record->video_file);
        });

        return $record;
    }
}
