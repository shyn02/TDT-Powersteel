<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BlogPost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreviewTest extends TestCase
{
    public function test_admin_preview(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['is_active' => true]);
        $user->profile()->update(['position' => 'admin']);

        $coverImage = UploadedFile::fake()->image('blog-steel-grade.png');
        Storage::disk('public')->putFileAs('blog', $coverImage, 'blog-steel-grade.png');
        BlogPost::create([
            'title' => 'Steel Grade',
            'slug' => 'steel-grade',
            'tag' => 'Guides',
            'excerpt' => 'Steel grade guide',
            'cover_image' => 'blog/blog-steel-grade.png',
            'body' => '<p>Steel grade guide</p>',
            'published_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/admin/blog-posts/steel-grade/edit');
        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('blog-steel-grade.png', $content);
        $this->assertStringContainsString('fileUploadFormComponent', $content);
    }

    public function test_storage_url(): void
    {
        Storage::fake('public');

        $coverImage = UploadedFile::fake()->image('blog-steel-grade.png');
        Storage::disk('public')->putFileAs('blog', $coverImage, 'blog-steel-grade.png');
        $post = BlogPost::create([
            'title' => 'Steel Grade',
            'slug' => 'steel-grade',
            'tag' => 'Guides',
            'excerpt' => 'Steel grade guide',
            'cover_image' => 'blog/blog-steel-grade.png',
            'body' => '<p>Steel grade guide</p>',
            'published_date' => now()->toDateString(),
        ]);

        $disk = Storage::disk('public');
        $url = $disk->url($post->cover_image);
        $this->assertTrue($disk->exists($post->cover_image));
        $component = new \Filament\Forms\Components\FileUpload('cover_image');
        $component->disk('public')->directory('blog')->image();
        $ref = new \ReflectionMethod(\Filament\Forms\Components\FileUpload::class, 'getUploadedFile');
        $ref->setAccessible(true);
        $result = $ref->invoke($component, $post->cover_image, null);
        $this->assertNotNull($result['url']);
    }
}
