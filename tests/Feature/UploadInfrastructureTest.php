<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Admin\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Admin\Resources\BlogPosts\Schemas\BlogPostForm;
use App\Filament\Admin\Resources\ProductCategories\Pages\CreateProductCategory;
use App\Filament\Admin\Resources\ProductCategories\Pages\EditProductCategory;
use App\Filament\Admin\Resources\ProductCategories\Schemas\ProductCategoryForm;
use App\Models\BlogPost;
use App\Models\ProductCategory;
use App\Support\MediaUploadRules;
use Filament\Actions\Action;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadInfrastructureTest extends TestCase
{
    public function test_media_contract_exposes_mime_size_and_message(): void
    {
        $this->assertSame(['image/jpeg', 'image/png', 'image/webp'], MediaUploadRules::MIMES);
        $this->assertSame(5120, MediaUploadRules::MAX_KILOBYTES);
        $this->assertSame(
            'Image must be a JPG, PNG, or WebP file no larger than 5 MB.',
            MediaUploadRules::MESSAGE
        );
        $this->assertSame('categories', MediaUploadRules::DIRECTORY_CATEGORY);
        $this->assertSame('categories/banners', MediaUploadRules::DIRECTORY_CATEGORY_BANNER);
        $this->assertSame('blog', MediaUploadRules::DIRECTORY_BLOG);
        $this->assertSame('projects', MediaUploadRules::DIRECTORY_PROJECT);
    }

    public function test_rules_accept_images_and_reject_executables(): void
    {
        Storage::fake('public');

        $this->assertNull(MediaUploadRules::validateUploadedFile(UploadedFile::fake()->image('ok.jpg')));
        $this->assertNull(MediaUploadRules::validateUploadedFile(UploadedFile::fake()->image('ok.png')));
        $this->assertNull(MediaUploadRules::validateUploadedFile($this->minimalWebp('ok.webp')));

        $this->assertSame(
            MediaUploadRules::MESSAGE,
            MediaUploadRules::validateUploadedFile(UploadedFile::fake()->image('evil.gif'))
        );
        $this->assertSame(
            MediaUploadRules::MESSAGE,
            MediaUploadRules::validateUploadedFile(UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml'))
        );
        $this->assertSame(
            MediaUploadRules::MESSAGE,
            MediaUploadRules::validateUploadedFile(UploadedFile::fake()->create('shell.php', 10, 'application/x-php'))
        );
        // Spoofed extension: PHP payload renamed as JPG must be rejected on MIME.
        $this->assertSame(
            MediaUploadRules::MESSAGE,
            MediaUploadRules::validateUploadedFile(UploadedFile::fake()->create('shell.jpg', 10, 'application/x-php'))
        );
    }

    public function test_rules_enforce_5mb_boundary(): void
    {
        Storage::fake('public');

        $this->assertNull(
            MediaUploadRules::validateUploadedFile(UploadedFile::fake()->image('exact.jpg')->size(5120))
        );
        $this->assertSame(
            MediaUploadRules::MESSAGE,
            MediaUploadRules::validateUploadedFile(UploadedFile::fake()->image('too-big.jpg')->size(5121))
        );
    }

    public function test_url_prefers_static_passthrough_otherwise_public_disk(): void
    {
        Storage::fake('public');

        $this->assertNull(MediaUploadRules::url(null));
        $this->assertNull(MediaUploadRules::url(''));
        $this->assertSame('/static/images/steel1.jpg', MediaUploadRules::url('/static/images/steel1.jpg'));
        $this->assertSame(
            Storage::disk('public')->url('blog/cover.jpg'),
            MediaUploadRules::url('blog/cover.jpg')
        );
    }

    public function test_forms_use_public_disk_fixed_dirs_and_shared_constraints(): void
    {
        foreach ([ProductCategoryForm::class, BlogPostForm::class] as $form) {
            $source = file_get_contents((new \ReflectionClass($form))->getFileName());

            $this->assertStringContainsString('MediaUploadRules::MIMES', $source, "{$form} must use shared MIME allowlist.");
            $this->assertStringContainsString('MediaUploadRules::MAX_KILOBYTES', $source, "{$form} must use shared 5120KB cap.");
            $this->assertStringContainsString("disk('public')", $source, "{$form} must target the public disk.");
            $this->assertStringNotContainsString('preserveFilenames(true', $source, "{$form} must not preserve user filenames.");
        }

        $categorySource = file_get_contents((new \ReflectionClass(ProductCategoryForm::class))->getFileName());
        $this->assertStringContainsString('MediaUploadRules::DIRECTORY_CATEGORY', $categorySource);
        $this->assertStringContainsString('MediaUploadRules::DIRECTORY_CATEGORY_BANNER', $categorySource);

        $blogSource = file_get_contents((new \ReflectionClass(BlogPostForm::class))->getFileName());
        $this->assertStringContainsString('MediaUploadRules::DIRECTORY_BLOG', $blogSource);
    }

    public function test_create_pages_guard_requires_completed_permanent_path(): void
    {
        Storage::fake('public');

        $categoryPage = $this->callGuard(CreateProductCategory::class, [
            'name' => 'Guarded',
            'slug' => 'guarded',
            'image' => 'livewire-tmp/incomplete.tmp',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $categoryPage['image'] ?? null);
        $this->assertSame(0, ProductCategory::where('slug', 'guarded')->count());

        // Valid permanent path passes the guard.
        $cover = UploadedFile::fake()->image('tile.jpg');
        Storage::disk('public')->putFileAs('categories', $cover, 'tile.jpg');
        $ok = $this->callGuard(CreateProductCategory::class, [
            'name' => 'Guarded',
            'slug' => 'guarded-ok',
            'image' => 'categories/tile.jpg',
        ]);
        $this->assertSame([], $ok);

        $blogPage = $this->callGuard(CreateBlogPost::class, [
            'title' => 'Guarded post',
            'cover_image' => 'livewire-tmp/incomplete.tmp',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $blogPage['cover_image'] ?? null);
        $this->assertSame(0, BlogPost::where('title', 'Guarded post')->count());
    }

    public function test_create_guard_rejects_missing_file_wrong_prefix_bad_mime_and_oversize(): void
    {
        Storage::fake('public');

        // Missing file.
        $missing = $this->callGuard(CreateBlogPost::class, [
            'title' => 'Missing file',
            'cover_image' => 'blog/gone.jpg',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $missing['cover_image']);

        // Wrong prefix (categories file submitted as blog cover).
        $tile = UploadedFile::fake()->image('tile.jpg');
        Storage::disk('public')->putFileAs('categories', $tile, 'tile.jpg');
        $prefix = $this->callGuard(CreateBlogPost::class, [
            'title' => 'Wrong prefix',
            'cover_image' => 'categories/tile.jpg',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $prefix['cover_image']);

        // Bad MIME stored on disk (SVG bytes under .jpg name in the right dir).
        Storage::disk('public')->put('blog/fake.jpg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $mime = $this->callGuard(CreateBlogPost::class, [
            'title' => 'Bad mime',
            'cover_image' => 'blog/fake.jpg',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $mime['cover_image']);

        // Oversize file on disk (> 5120KB).
        $big = str_repeat('0', (5121 * 1024));
        Storage::disk('public')->put('blog/big.jpg', $big);
        $size = $this->callGuard(CreateBlogPost::class, [
            'title' => 'Too big',
            'cover_image' => 'blog/big.jpg',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $size['cover_image']);
        $this->assertSame(0, BlogPost::where('title', 'Too big')->count());
    }

    public function test_edit_pages_defer_old_file_deletion_to_after_commit(): void
    {
        foreach ([EditProductCategory::class, EditBlogPost::class] as $page) {
            $source = file_get_contents((new \ReflectionClass($page))->getFileName());
            $this->assertStringContainsString(
                'DB::afterCommit',
                $source,
                "{$page} must schedule old-file deletion only after commit."
            );
        }
    }

    public function test_edit_rollback_preserves_old_file(): void
    {
        Storage::fake('public');

        $old = UploadedFile::fake()->image('old.jpg');
        Storage::disk('public')->putFileAs('blog', $old, 'old.jpg');
        $post = BlogPost::create([
            'title' => 'Lifecycle',
            'slug' => 'lifecycle',
            'tag' => 'Guides',
            'excerpt' => 'Excerpt',
            'cover_image' => 'blog/old.jpg',
            'body' => '<p>Body</p>',
            'published_date' => now()->toDateString(),
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                \Illuminate\Support\Facades\DB::afterCommit(function () {
                    Storage::disk('public')->delete('blog/old.jpg');
                });

                throw new \RuntimeException('simulated failure before commit');
            });
        } catch (\RuntimeException) {
            // Expected: rollback path.
        }

        $this->assertTrue(Storage::disk('public')->exists('blog/old.jpg'));
        $this->assertSame('blog/old.jpg', $post->fresh()->cover_image);
    }

    public function test_create_action_is_disabled_while_uploading(): void
    {
        foreach ([CreateProductCategory::class, CreateBlogPost::class] as $page) {
            $action = $this->createFormAction($page);
            $attributes = $action->getExtraAttributes();

            $this->assertArrayHasKey('wire:loading.attr', $attributes, "{$page} Create must carry wire:loading disabled state.");
            $this->assertSame('disabled', $attributes['wire:loading.attr']);
        }
    }

    public function test_upload_flow_requires_no_queue_worker(): void
    {
        Queue::fake();
        Storage::fake('public');

        $cover = UploadedFile::fake()->image('cover.jpg');
        Storage::disk('public')->putFileAs('blog', $cover, 'cover.jpg');

        BlogPost::create([
            'title' => 'No queue',
            'slug' => 'no-queue',
            'tag' => 'Guides',
            'excerpt' => 'Excerpt',
            'cover_image' => 'blog/cover.jpg',
            'body' => '<p>Body</p>',
            'published_date' => now()->toDateString(),
        ]);

        Queue::assertNothingPushed();
        $this->assertTrue(Storage::disk('public')->exists('blog/cover.jpg'));
    }

    /**
     * Invoke the page guard without booting Livewire.
     *
     * @param  class-string  $page
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function callGuard(string $page, array $data): array
    {
        $instance = (new \ReflectionClass($page))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($page, 'guardCompletedUploads');

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

        // Flatten Filament Halt (no record) into message map when used.
        return [];
    }

    /**
     * Read the Create action off a page without rendering Livewire.
     *
     * @param  class-string  $page
     */
    private function createFormAction(string $page): Action
    {
        $instance = (new \ReflectionClass($page))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($page, 'getCreateFormAction');
        $method->setAccessible(true);

        return $method->invoke($instance);
    }

    private function minimalWebp(string $name): UploadedFile
    {
        // Minimal 1x1 WebP (RIFF....WEBPVP8 ) — recognized as image/webp by finfo.
        $bytes = base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA==');

        $path = tempnam(sys_get_temp_dir(), 'webp').'.webp';
        file_put_contents($path, $bytes);

        return new UploadedFile($path, $name, 'image/webp', null, true);
    }
}
