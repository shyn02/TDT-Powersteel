<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Support\MediaUploadRules;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectHomepageTest extends TestCase
{
    private function makeProject(array $overrides = []): Project
    {
        return Project::create(array_merge([
            'tag' => 'Commercial',
            'image' => '/static/images/steel1.jpg',
            'location' => 'Manila',
            'title' => 'Sample Project',
            'info' => 'Sample info.',
            'is_published' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    public function test_homepage_lists_published_projects_in_sort_order(): void
    {
        Project::query()->forceDelete();

        $this->makeProject(['title' => 'Zulu Beta Hall', 'sort_order' => 2]);
        $this->makeProject(['title' => 'Zulu Alpha Tower', 'sort_order' => 1]);
        $this->makeProject(['title' => 'Zulu Gamma Center', 'sort_order' => 2, 'location' => 'Cavite']);

        $response = $this->get('/');
        $response->assertStatus(200);

        $content = $response->getContent();
        $firstPos = strpos($content, 'Zulu Alpha Tower');
        $secondPos = strpos($content, 'Zulu Beta Hall');
        $thirdPos = strpos($content, 'Zulu Gamma Center');

        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertNotFalse($thirdPos);
        // sort_order 1 first, then sort_order 2 ordered by id (Beta before Gamma).
        $this->assertTrue($firstPos < $secondPos && $secondPos < $thirdPos);
    }

    public function test_homepage_excludes_unpublished_and_trashed(): void
    {
        $this->makeProject(['title' => 'Visible Project']);
        $this->makeProject(['title' => 'Hidden Draft', 'is_published' => false]);
        $trashed = $this->makeProject(['title' => 'Archived Project']);
        $trashed->delete();

        $content = $this->get('/')->getContent();

        $this->assertStringContainsString('Visible Project', $content);
        $this->assertStringNotContainsString('Hidden Draft', $content);
        $this->assertStringNotContainsString('Archived Project', $content);
    }

    public function test_homepage_escapes_title_and_info(): void
    {
        $this->makeProject([
            'title' => '<script>alert("x")</script>',
            'info' => '<b>bold</b> & "quotes"',
        ]);

        $content = $this->get('/')->getContent();

        $this->assertStringNotContainsString('<script>alert("x")</script>', $content);
        $this->assertStringContainsString('&lt;script&gt;', $content);
        $this->assertStringNotContainsString('<b>bold</b>', $content);
        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $content);
    }

    public function test_homepage_renders_duplicate_aria_hidden_set_for_marquee(): void
    {
        $this->makeProject(['title' => 'Marquee Project', 'location' => 'Makati']);

        $content = $this->get('/')->getContent();

        // First set has meaningful alt, duplicate set is aria-hidden with empty alt.
        $this->assertStringContainsString('alt="Marquee Project, Makati"', $content);
        $this->assertStringContainsString('aria-hidden="true"', $content);

        // Visible + marquee duplicate: title appears in both sets, duplicate img has empty alt.
        $this->assertGreaterThanOrEqual(2, substr_count($content, 'Marquee Project'));
        $this->assertStringContainsString('alt=""', $content);
    }

    public function test_image_url_resolves_static_passthrough_and_public_disk(): void
    {
        Storage::fake('public');

        $static = $this->makeProject(['image' => '/static/images/steel1.jpg']);
        $this->assertSame('/static/images/steel1.jpg', $static->fresh()->image_url);

        Storage::disk('public')->put('projects/disk.jpg', 'fake-bytes');
        $disk = $this->makeProject(['image' => 'projects/disk.jpg', 'title' => 'Disk Project']);
        $this->assertSame(Storage::disk('public')->url('projects/disk.jpg'), $disk->fresh()->image_url);

        $this->assertNull((new Project(['image' => null]))->image_url);
        $this->assertNull((new Project(['image' => '  ']))->image_url);
        $this->assertSame(
            Storage::disk('public')->url('projects/a.jpg'),
            MediaUploadRules::url('projects/a.jpg')
        );
    }

    public function test_homepage_omits_projects_section_when_empty(): void
    {
        Project::query()->forceDelete();

        $content = $this->get('/')->getContent();

        $this->assertStringNotContainsString('id="projects"', $content);
        $this->assertStringNotContainsString('PROJECTS WE HAVE', $content);
    }
}
