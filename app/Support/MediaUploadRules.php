<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Single contract for permanent feature-image uploads (local-only).
 *
 * Local operation:
 * - Requires `php artisan storage:link` so `public/storage` resolves into
 *   `storage/app/public`. Without the link, `Storage::disk('public')->url()`
 *   still builds the URL but the file will 404 over HTTP.
 * - Local PHP default `upload_max_filesize=2M` is BELOW the 5 MB app cap, so
 *   files between 2 MB and 5 MB fail at the PHP layer locally until the ini
 *   value is raised. Files under 2 MB exercise the full Filament path.
 */
class MediaUploadRules
{
    /** @var list<string> */
    public const MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /** @var list<string> */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const MAX_KILOBYTES = 5120;

    public const MESSAGE = 'Image must be a JPG, PNG, or WebP file no larger than 5 MB.';

    public const DIRECTORY_CATEGORY = 'categories';

    public const DIRECTORY_CATEGORY_BANNER = 'categories/banners';

    public const DIRECTORY_BLOG = 'blog';

    public const DIRECTORY_PROJECT = 'projects';

    public const DIRECTORY_PRODUCT = 'products';

    /**
     * Shared validation rules for a permanent image input.
     *
     * @return list<string>
     */
    public static function rules(bool $required): array
    {
        $base = [
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:'.self::MAX_KILOBYTES,
        ];

        return $required ? array_merge(['required'], $base) : array_merge(['nullable'], $base);
    }

    /**
     * Validate a freshly uploaded file (Livewire temporary upload).
     * Returns null when valid, otherwise the shared message.
     */
    public static function validateUploadedFile(?UploadedFile $file): ?string
    {
        if (! $file instanceof UploadedFile) {
            return self::MESSAGE;
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === '') {
            $extension = strtolower(pathinfo((string) $file->getClientOriginalName(), PATHINFO_EXTENSION));
        }

        if (! in_array($extension, self::EXTENSIONS, true)) {
            return self::MESSAGE;
        }

        $mime = strtolower((string) $file->getMimeType());
        if (! in_array($mime, self::MIMES, true)) {
            return self::MESSAGE;
        }

        $size = $file->getSize();
        if ($size === false || $size === null || $size > self::MAX_KILOBYTES * 1024) {
            return self::MESSAGE;
        }

        return null;
    }

    /**
     * Validate a completed permanent path on the public disk.
     * Returns null when valid, otherwise the shared message.
     */
    public static function validateStoredPath(?string $path, string $directory, bool $required): ?string
    {
        if ($path === null || trim($path) === '') {
            return $required ? self::MESSAGE : null;
        }

        $path = trim($path);

        // Reject traversal, absolute paths, and anything outside the fixed dir.
        if (str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return self::MESSAGE;
        }

        if (! str_starts_with($path, rtrim($directory, '/').'/')) {
            return self::MESSAGE;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, self::EXTENSIONS, true)) {
            return self::MESSAGE;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return self::MESSAGE;
        }

        $size = $disk->size($path);
        if ($size === false || $size === null || $size > self::MAX_KILOBYTES * 1024) {
            return self::MESSAGE;
        }

        $mime = strtolower((string) $disk->mimeType($path));
        if (! in_array($mime, self::MIMES, true)) {
            return self::MESSAGE;
        }

        return null;
    }

    /**
     * Schedule deletion of a replaced old file only after the DB commit.
     * Never deletes when the path is unchanged or empty.
     */
    public static function scheduleOldFileDeletion(?string $oldPath, ?string $newPath): void
    {
        if ($oldPath === null || trim($oldPath) === '' || $oldPath === $newPath) {
            return;
        }

        DB::afterCommit(function () use ($oldPath): void {
            Storage::disk('public')->delete($oldPath);
        });
    }

    /**
     * Public URL for a stored media path.
     *
     * Signature for Projects handoff: `MediaUploadRules::url(?string $path): ?string`
     * - null/blank → null
     * - `/static/images/...` → returned unchanged (imported legacy cards)
     * - otherwise → `Storage::disk('public')->url($path)`, host-aligned with
     *   the current request (see alignPublicDiskUrlWithRequest). The alignment
     *   keeps Filament previews working when local APP_URL omits the serve
     *   port (e.g. APP_URL=http://localhost while serving :8000); it is a
     *   no-op whenever the configured host already matches the request host
     *   (production included).
     */
    public static function url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (str_starts_with($path, '/static/images')) {
            return $path;
        }

        return self::hostAwareUrl(Storage::disk('public')->url($path));
    }

    /**
     * Rewrite an absolute storage URL to the current request's
     * scheme+host when they differ. Relative URLs and matching hosts pass
     * through unchanged. Console runs without an HTTP request are
     * returned unchanged (unit tests excepted so the behavior is covered).
     */
    public static function hostAwareUrl(string $url): string
    {
        try {
            // Console (mails, sitemap, artisan) has no real request host —
            // leave absolute URLs untouched there.
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return $url;
            }

            $parts = parse_url($url);
            if (! isset($parts['scheme'], $parts['host'])) {
                return $url;
            }

            $requestHost = request()->getSchemeAndHttpHost();
            $urlRoot = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

            if (rtrim($urlRoot, '/') === rtrim($requestHost, '/')) {
                return $url;
            }

            return rtrim($requestHost, '/').substr($url, strlen($urlRoot));
        } catch (\Throwable) {
            return $url;
        }
    }

    /**
     * Local-safe resilience for Filament ImageColumn / ImageEntry / FileUpload
     * previews, which resolve URLs via `Storage::disk('public')->url()` and
     * bypass MediaUploadRules::url(). When the configured public-disk URL
     * host differs from the incoming request host (local APP_URL port drift),
     * point the disk at the request host for the rest of the request.
     * No-op when hosts match, when the URL is relative, or in console.
     */
    public static function alignPublicDiskUrlWithRequest(): void
    {
        try {
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            $configured = (string) config('filesystems.disks.public.url', '');
            if ($configured === '') {
                return;
            }

            $parts = parse_url($configured);
            if (! isset($parts['scheme'], $parts['host'])) {
                return;
            }

            $configuredRoot = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
            $requestHost = request()->getSchemeAndHttpHost();

            if (rtrim($configuredRoot, '/') === rtrim($requestHost, '/')) {
                return;
            }

            $path = $parts['path'] ?? '/storage';
            config(['filesystems.disks.public.url' => rtrim($requestHost, '/').$path]);

            Storage::forgetDisk('public');
        } catch (\Throwable) {
            // Never break boot over a cosmetic URL alignment.
        }
    }
}
