<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Products\Pages\CreateProduct;
use App\Filament\Admin\Resources\Products\Pages\EditProduct;
use App\Filament\Admin\Resources\Products\Schemas\ProductForm;
use App\Filament\Admin\Resources\Products\Tables\ProductsTable;
use App\Support\MediaUploadRules;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUrlTest extends TestCase
{
    /**
     * Regression: local APP_URL without the serve port made
     * Storage::disk('public')->url() emit http://localhost/storage/...
     * (port 80, 404) while the app is served on :8000, so product photos
     * broke in the admin list/view/edit pages.
     */
    public function test_product_image_url_matches_request_host_when_config_drifts(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('photo.jpg');
        Storage::disk('public')->putFileAs('products', $photo, 'photo.jpg');
        $this->assertTrue(Storage::disk('public')->exists('products/photo.jpg'));

        // Simulate the drift: disk URL built from APP_URL=http://localhost (no port).
        config()->set('filesystems.disks.public.url', 'http://localhost/storage');
        Storage::forgetDisk('public');

        // Serve the app on :8000 like `php artisan serve` does locally.
        $this->app->instance('request', Request::create('http://localhost:8000/admin/products', 'GET'));

        // Per-request alignment (AppServiceProvider runs this on every boot).
        MediaUploadRules::alignPublicDiskUrlWithRequest();

        $storageUrl = Storage::disk('public')->url('products/photo.jpg');
        $this->assertStringStartsWith('http://localhost:8000/storage/', $storageUrl);
        $this->assertStringEndsWith('products/photo.jpg', $storageUrl);

        // Helper used by model accessors stays in sync with the disk URL.
        $this->assertSame($storageUrl, MediaUploadRules::url('products/photo.jpg'));
    }

    public function test_host_aware_url_rewrites_only_on_mismatch(): void
    {
        $this->app->instance('request', Request::create('http://localhost:8000/admin/products', 'GET'));

        $this->assertSame(
            'http://localhost:8000/storage/products/photo.jpg',
            MediaUploadRules::hostAwareUrl('http://localhost/storage/products/photo.jpg')
        );

        // Matching hosts pass through unchanged.
        $this->assertSame(
            'http://localhost:8000/storage/products/photo.jpg',
            MediaUploadRules::hostAwareUrl('http://localhost:8000/storage/products/photo.jpg')
        );

        // Relative URLs and legacy static passthrough are untouched.
        $this->assertSame('/storage/products/photo.jpg', MediaUploadRules::hostAwareUrl('/storage/products/photo.jpg'));
        $this->assertSame('/static/images/steel1.jpg', MediaUploadRules::url('/static/images/steel1.jpg'));
        $this->assertNull(MediaUploadRules::url(null));
    }

    public function test_product_image_column_renders_from_public_disk(): void
    {
        $tableSource = file_get_contents((new \ReflectionClass(ProductsTable::class))->getFileName());
        $this->assertStringContainsString("ImageColumn::make('image')", $tableSource);
        $this->assertStringContainsString("disk('public')", $tableSource);
    }

    public function test_product_form_uses_shared_media_contract(): void
    {
        $this->assertSame('products', MediaUploadRules::DIRECTORY_PRODUCT);

        $source = file_get_contents((new \ReflectionClass(ProductForm::class))->getFileName());
        $this->assertStringContainsString('MediaUploadRules::MIMES', $source);
        $this->assertStringContainsString('MediaUploadRules::MAX_KILOBYTES', $source);
        $this->assertStringContainsString('MediaUploadRules::DIRECTORY_PRODUCT', $source);
        $this->assertStringContainsString("disk('public')", $source);
    }

    public function test_product_create_guard_validates_stored_path_and_edit_defers_deletion(): void
    {
        Storage::fake('public');

        $bad = $this->callGuard(['image' => 'livewire-tmp/incomplete.tmp']);
        $this->assertSame(MediaUploadRules::MESSAGE, $bad['image'] ?? null);

        // Image is optional (legacy rows carry NULL) — a missing image passes.
        $this->assertSame([], $this->callGuard([]));

        $photo = UploadedFile::fake()->image('photo.jpg');
        Storage::disk('public')->putFileAs('products', $photo, 'photo.jpg');
        $this->assertSame([], $this->callGuard(['image' => 'products/photo.jpg']));

        $editSource = file_get_contents((new \ReflectionClass(EditProduct::class))->getFileName());
        $this->assertStringContainsString('DB::afterCommit', $editSource);
        $this->assertStringContainsString('MediaUploadRules::DIRECTORY_PRODUCT', $editSource);
    }

    /**
     * Invoke the create guard without booting Livewire.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function callGuard(array $data): array
    {
        $instance = (new \ReflectionClass(CreateProduct::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(CreateProduct::class, 'guardCompletedUploads');
        $method->setAccessible(true);

        try {
            $method->invoke($instance, $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $flat = [];
            foreach ($e->errors() as $field => $messages) {
                $flat[$field] = is_array($messages) ? ($messages[0] ?? '') : $messages;
            }

            return $flat;
        }

        return [];
    }
}
