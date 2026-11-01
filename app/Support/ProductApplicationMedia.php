<?php

namespace App\Support;

/**
 * Validation for the two media slots a Product Application owns.
 *
 * Kept out of the Filament pages so the create and edit screens cannot drift:
 * create validates whatever it was given, edit validates only the keys the
 * form actually submitted (so untouched media is never re-checked).
 */
class ProductApplicationMedia
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string> field => message
     */
    public static function errors(array $data, bool $onlyPresentKeys): array
    {
        $errors = [];

        if (! $onlyPresentKeys || array_key_exists('image', $data)) {
            $error = MediaUploadRules::validateStoredPath(
                $data['image'] ?? null,
                MediaUploadRules::DIRECTORY_PRODUCT_APPLICATION,
                false
            );

            if ($error !== null) {
                $errors['image'] = $error;
            }
        }

        if (! $onlyPresentKeys || array_key_exists('video_file', $data)) {
            $error = MediaUploadRules::validateStoredVideoPath(
                $data['video_file'] ?? null,
                MediaUploadRules::DIRECTORY_PRODUCT_APPLICATION,
                false
            );

            if ($error !== null) {
                $errors['video_file'] = $error;
            }
        }

        return $errors;
    }
}
