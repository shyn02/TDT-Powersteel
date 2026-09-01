<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Storage;

class PreviewTest extends TestCase
{
    public function test_admin_preview(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/admin/blog-posts/steel-grade/edit');
        $response->assertStatus(200);
        $content = $response->getContent();
        // Save for inspection
        file_put_contents(storage_path('preview.html'), $content);
        // Check if image url present
        $this->assertStringContainsString('blog-steel-grade.png', $content);
        // Check for filament file upload config
        $this->assertStringContainsString('fileUploadFormComponent', $content);
        // Dump uploaded files JSON snippet
        if(preg_match('/getUploadedFilesUsing.*?(\{.*?\})/s', $content, $m)){
            file_put_contents(storage_path('preview_match.txt'), $m[0]);
        }
    }
    public function test_storage_url(): void
    {
        $post = BlogPost::where('slug','steel-grade')->first();
        $disk = Storage::disk('public');
        $url = $disk->url($post->cover_image);
        $this->assertTrue($disk->exists($post->cover_image));
        echo "URL: $url\n";
        echo "MIME: ".$disk->mimeType($post->cover_image)."\n";
        // Simulate filament logic
        $component = new \Filament\Forms\Components\FileUpload('cover_image');
        $component->disk('public')->directory('blog')->image();
        // Use reflection to call getUploadedFile
        $ref = new \ReflectionMethod(\Filament\Forms\Components\FileUpload::class, 'getUploadedFile');
        $ref->setAccessible(true);
        $result = $ref->invoke($component, $post->cover_image, null);
        var_dump($result);
        $this->assertNotNull($result['url']);
    }
}
