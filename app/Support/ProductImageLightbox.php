<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Shared click-to-enlarge behavior for product photos in /admin.
 *
 * One contract reused by all three product-image spots:
 * - ProductsTable ImageColumn (list)
 * - ProductInfolist ImageEntry (view)
 * - ProductForm current-image preview (edit)
 *
 * The overlay itself lives in `filament.admin.product-image-lightbox`
 * (mounted once via PanelsRenderHook::BODY_END in AdminPanelProvider).
 * These trigger attributes mark thumbnails as enlargeable; a delegated
 * listener in that view opens the overlay using the thumbnail's own
 * `src` — which Filament already resolves host-aware via
 * alignPublicDiskUrlWithRequest() (see MediaUploadRules) — so no URL
 * logic is duplicated here.
 */
class ProductImageLightbox
{
    /**
     * Marker attribute identifying enlargeable thumbnails for the
     * delegated click/keyboard listener in the shared overlay view.
     */
    public const TRIGGER_ATTRIBUTE = 'data-tdt-lightbox';

    /**
     * Extra attributes marking a thumbnail as click-to-enlarge.
     *
     * Values are static/safe (never user input) because Filament renders
     * extra attributes unescaped.
     *
     * @return array<string, string>
     */
    public static function triggerAttributes(?string $label = null): array
    {
        $label = trim((string) $label);
        if ($label === '') {
            $label = 'Product image';
        }

        return [
            self::TRIGGER_ATTRIBUTE => 'true',
            'tabindex' => '0',
            'role' => 'button',
            'title' => 'Click to enlarge',
            'aria-label' => $label.' — click to enlarge',
            'style' => 'cursor:zoom-in',
        ];
    }

    /**
     * Full-size URL for the overlay. Delegates to the shared media
     * contract so storage URLs never drift from upload logic.
     */
    public static function url(?string $path): ?string
    {
        return MediaUploadRules::url($path);
    }

    /**
     * Read the stored image path off a form/infolist record that may be
     * a Model, a state array, or null (e.g. the create page).
     */
    public static function recordImage(mixed $record): ?string
    {
        if (is_array($record)) {
            $image = $record['image'] ?? null;

            return is_string($image) && trim($image) !== '' ? $image : null;
        }

        if ($record instanceof Model) {
            $image = $record->getAttribute('image');

            return is_string($image) && trim($image) !== '' ? $image : null;
        }

        return null;
    }
}
