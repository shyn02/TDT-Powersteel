<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Layer A (local serve temp dir) regression guard.
 *
 * Root cause: `php artisan serve` (default reload mode) scrubs TMP/TEMP/
 * USERPROFILE from the `php -S` child environment (ServeCommand only passes
 * $passthroughVariables), so PHP's temp resolution falls back to C:\Windows
 * (non-writable) and every multipart upload fails with UPLOAD_ERR_NO_TMP_DIR
 * (error 6). `ini_set()` at runtime cannot fix this: PHP populates $_FILES
 * before any app code boots, so the remedy must be ini-level.
 *
 * Fix (local-only, outside the repo): pin `upload_tmp_dir` and
 * `sys_temp_dir` in the local php.ini to storage/tmp/php-uploads.
 * See docs/operations/local-upload-temp-2026-10-10.md.
 */
class UploadTempDirTest extends TestCase
{
    public function test_local_upload_temp_dir_exists_and_is_writable(): void
    {
        $dir = storage_path('tmp/php-uploads');

        $this->assertDirectoryExists(
            $dir,
            'Local upload temp dir is missing; create storage/tmp/php-uploads (see docs/operations/local-upload-temp-2026-10-10.md).'
        );
        $this->assertTrue(
            is_writable($dir),
            "Local upload temp dir [{$dir}] is not writable by the serving PHP process."
        );
    }

    public function test_windows_serving_php_has_writable_upload_tmp_dir(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('Windows-only local-serve temp-dir check.');
        }

        $uploadTmpDir = (string) ini_get('upload_tmp_dir');

        $this->assertNotSame(
            '',
            $uploadTmpDir,
            'upload_tmp_dir is empty: artisan-serve children lose TMP/TEMP/USERPROFILE and fall back to '
            .'C:\Windows (UPLOAD_ERR_NO_TMP_DIR). Pin upload_tmp_dir in the LOCAL php.ini only — '
            .'see docs/operations/local-upload-temp-2026-10-10.md. `php -d` on the parent does NOT reach the php -S child.'
        );
        $this->assertDirectoryExists($uploadTmpDir, "upload_tmp_dir [{$uploadTmpDir}] does not exist.");
        $this->assertTrue(is_writable($uploadTmpDir), "upload_tmp_dir [{$uploadTmpDir}] is not writable.");
    }
}
