<?php

namespace Tests\Feature;

use App\Models\ProductApplication;
use Database\Seeders\ProductApplicationSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductApplicationsPageTest extends TestCase
{
    private function makeApplication(array $overrides = []): ProductApplication
    {
        return ProductApplication::create(array_merge([
            'label' => 'Purlins',
            'image' => '/static/images/product-applications/purlins.png',
            'x' => 29.27,
            'y' => 19.17,
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    public function test_product_applications_page_renders(): void
    {
        $response = $this->get('/product-applications/');

        $response->assertOk();
        $response->assertSee('PRODUCT APPLICATIONS');
    }

    public function test_page_lists_active_applications_in_map_order(): void
    {
        $this->makeApplication(['label' => 'Zulu Beta', 'sort_order' => 2]);
        $this->makeApplication(['label' => 'Zulu Alpha', 'sort_order' => 1]);
        $this->makeApplication(['label' => 'Zulu Gamma', 'sort_order' => 2]);
        $this->makeApplication(['label' => 'Hidden Delta', 'is_active' => false]);
        $archived = $this->makeApplication(['label' => 'Archived Epsilon']);
        $archived->delete();

        $content = $this->get('/product-applications/')->getContent();

        $alpha = strpos($content, 'Zulu Alpha');
        $beta = strpos($content, 'Zulu Beta');
        $gamma = strpos($content, 'Zulu Gamma');

        $this->assertNotFalse($alpha);
        $this->assertNotFalse($beta);
        $this->assertNotFalse($gamma);

        // sort_order 1 first, then sort_order 2 by creation order.
        $this->assertTrue($alpha < $beta && $beta < $gamma);

        $this->assertStringNotContainsString('Hidden Delta', $content);
        $this->assertStringNotContainsString('Archived Epsilon', $content);
    }

    public function test_circles_are_positioned_by_percentage_with_outer_labels(): void
    {
        $left = $this->makeApplication(['label' => 'Left Side', 'x' => 18.7, 'y' => 54.63]);
        $right = $this->makeApplication(['label' => 'Right Side', 'x' => 77.19, 'y' => 60.93]);

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringContainsString('left: 18.7%; top: 54.63%', $content);
        $this->assertStringContainsString('left: 77.19%; top: 60.93%', $content);

        $leftTag = $this->circleTag($content, $left->id);
        $rightTag = $this->circleTag($content, $right->id);

        $this->assertStringContainsString('data-label-side="left"', $leftTag);
        $this->assertStringContainsString('data-label-side="right"', $rightTag);
    }

    public function test_labels_are_escaped_on_output(): void
    {
        $this->makeApplication(['label' => '<script>alert("x")</script> & <b>bold</b>']);

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringNotContainsString('<script>alert("x")</script>', $content);
        $this->assertStringNotContainsString('<b>bold</b>', $content);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $content);
    }

    public function test_the_list_below_the_map_mirrors_the_map(): void
    {
        $this->makeApplication(['label' => 'Zulu Alpha', 'sort_order' => 1]);
        $this->makeApplication(['label' => 'Zulu Beta', 'sort_order' => 2]);

        $content = $this->get('/product-applications/')->getContent();

        $list = $this->listBlock($content);

        $this->assertNotSame('', $list);
        $this->assertStringContainsString('Zulu Alpha', $list);

        $alpha = strpos($list, 'Zulu Alpha');
        $beta = strpos($list, 'Zulu Beta');

        $this->assertNotFalse($alpha);
        $this->assertNotFalse($beta);
        $this->assertTrue($alpha < $beta);
    }

    public function test_map_section_is_omitted_when_nothing_is_active(): void
    {
        ProductApplication::query()->forceDelete();

        $this->makeApplication(['is_active' => false]);
        $archived = $this->makeApplication();
        $archived->delete();

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringNotContainsString('product-applications-section', $content);
        $this->assertStringNotContainsString('facility-map.png', $content);
        $this->assertStringContainsString('PRODUCT APPLICATIONS', $content);
    }

    public function test_an_anchored_circle_draws_a_leader_line(): void
    {
        ProductApplication::query()->forceDelete();

        $this->makeApplication([
            'label' => 'Anchored',
            'x' => 20,
            'y' => 30,
            'anchor_x' => 60,
            'anchor_y' => 70,
        ]);

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringContainsString('product-applications-lines', $content);
        $this->assertMatchesRegularExpression(
            '/<line[^>]*x1="20"[^>]*y1="30"[^>]*x2="60"[^>]*y2="70"/s',
            $content
        );
    }

    public function test_a_circle_without_an_anchor_draws_no_line(): void
    {
        ProductApplication::query()->forceDelete();

        $this->makeApplication([
            'label' => 'Unanchored',
            'x' => 20,
            'y' => 30,
            'anchor_x' => null,
            'anchor_y' => null,
        ]);

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringContainsString('Unanchored', $content);
        $this->assertStringNotContainsString('product-applications-lines', $content);
        $this->assertStringNotContainsString('<line', $content);
    }

    public function test_only_anchored_circles_are_traced(): void
    {
        ProductApplication::query()->forceDelete();

        $this->makeApplication(['label' => 'Traced', 'x' => 10, 'y' => 10, 'anchor_x' => 40, 'anchor_y' => 40]);
        $this->makeApplication(['label' => 'Plain', 'x' => 30, 'y' => 30, 'anchor_x' => null, 'anchor_y' => null]);

        $content = $this->get('/product-applications/')->getContent();

        $this->assertSame(1, substr_count($content, '<line'));
        $this->assertMatchesRegularExpression('/x1="10"[^>]*y1="10"[^>]*x2="40"[^>]*y2="40"/s', $content);
    }

    public function test_quote_call_to_action_survives_an_empty_map(): void
    {
        ProductApplication::query()->forceDelete();

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringContainsString('btn-quote-trigger', $content);
        $this->assertStringContainsString('REQUEST A QUOTE', $content);
    }

    public function test_page_is_linked_from_the_main_navigation_and_the_sitemap(): void
    {
        $content = $this->get('/product-applications/')->getContent();

        preg_match('/<nav class="nav-links".*?<\/nav>/s', $content, $matches);
        $nav = $matches[0] ?? '';

        $this->assertStringContainsString('PRODUCT APPLICATIONS', $nav);

        $products = strpos($nav, 'PRODUCTS');
        $applications = strpos($nav, 'PRODUCT APPLICATIONS');

        $this->assertNotFalse($products);
        $this->assertNotFalse($applications);
        $this->assertTrue($products < $applications, 'PRODUCT APPLICATIONS should follow PRODUCTS');

        $this->assertStringContainsString('>Product Applications<', $content);

        $sitemap = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString('/product-applications/', $sitemap);
    }

    public function test_seeded_rows_cover_the_twelve_approved_applications(): void
    {
        $this->seed(ProductApplicationSeeder::class);

        $this->assertDatabaseCount('product_applications', 12);

        $applications = ProductApplication::query()->orderBy('sort_order')->get();

        $this->assertSame(
            ['Purlins', 'Flat Bar', 'Steel Plate', 'Angle Bar', 'Steel Pipes', 'Channel Bar',
                'Tubulars', 'Sheet Pile', 'Wide Flange', 'Deformed Bar', 'Round Bar', 'Hardware Items'],
            $applications->pluck('label')->all()
        );

        $this->assertSame(
            12,
            $applications->map(fn (ProductApplication $a) => $a->x.'|'.$a->y)->unique()->count(),
            'Every seeded circle should have its own position'
        );

        $this->assertSame(0, $applications->filter->isPlayable()->count(), 'Seeded rows ship without videos');

        $this->assertSame(12, $applications->filter->hasAnchor()->count(), 'Every seeded circle draws a leader line');
        foreach ($applications as $application) {
            $anchor = $application->anchorPosition();
            $this->assertNotNull($anchor);
            $this->assertTrue($anchor->isWithinMap(), $application->label.' anchor should sit on the artwork');
        }

        $disk = Storage::disk('public');

        foreach ($applications as $application) {
            // Managed on the public disk so the admin can preview and replace
            // it, with the committed static artwork as its source.
            $this->assertStringStartsWith('product-applications/', (string) $application->image);
            $this->assertTrue($disk->exists($application->image), $application->image.' should be on the public disk');

            $size = @getimagesizefromstring((string) $disk->get($application->image));

            $this->assertNotFalse($size, $application->image.' should be a readable image');
            $this->assertSame('image/png', $size['mime']);
            $this->assertSame(116, $size[0]);
            $this->assertSame(116, $size[1]);

            $this->assertFileExists(
                public_path('static/images/product-applications/'.basename((string) $application->image))
            );
        }

        // Re-running repairs the same rows rather than adding more.
        $this->seed(ProductApplicationSeeder::class);
        $this->assertDatabaseCount('product_applications', 12);
    }

    public function test_a_circle_with_a_video_plays_it(): void
    {
        $playable = $this->makeApplication([
            'label' => 'Purlins',
            'video_file' => 'product-applications/purlins.mp4',
        ]);

        $content = $this->get('/product-applications/')->getContent();
        $tag = $this->circleTag($content, $playable->id);

        $this->assertStringStartsWith('<button', $tag);
        $this->assertStringContainsString('type="button"', $tag);
        $this->assertStringContainsString('aria-label="Play video: Purlins"', $tag);
        $this->assertStringContainsString('/storage/product-applications/purlins.mp4', $tag);
    }

    public function test_a_circle_without_a_video_is_not_interactive(): void
    {
        $inert = $this->makeApplication(['label' => 'Tubulars', 'video_file' => null]);

        $content = $this->get('/product-applications/')->getContent();
        $tag = $this->circleTag($content, $inert->id);

        $this->assertStringStartsWith('<div', $tag);
        $this->assertStringNotContainsString('data-video-url', $tag);
        $this->assertStringNotContainsString('aria-label', $tag);
        $this->assertStringNotContainsString('data-video-trigger', $tag);
    }

    public function test_the_page_ships_one_shared_video_modal(): void
    {
        $this->makeApplication([
            'label' => 'Purlins',
            'video_file' => 'product-applications/purlins.mp4',
            'description' => 'Used for roof framing.',
        ]);

        $content = $this->get('/product-applications/')->getContent();

        [$document, $xpath] = $this->dom($content);

        $nodes = $xpath->query('//*[@id="product-application-modal"]');
        $this->assertSame(1, $nodes->length, 'There should be exactly one shared modal');
        $this->assertNotNull($nodes->item(0));

        $modal = $document->saveHTML($nodes->item(0));

        $this->assertStringContainsString('role="dialog"', $modal);
        $this->assertStringContainsString('aria-modal="true"', $modal);
        $this->assertStringContainsString('data-video-close', $modal);
        $this->assertStringContainsString('aria-label="Close video"', $modal);
        $this->assertStringContainsString('preload="none"', $modal);
        $this->assertStringContainsString('muted', $modal);
        $this->assertStringContainsString('controls', $modal);
        $this->assertStringContainsString('data-video-description', $modal);

        $this->assertStringContainsString('/static/product-applications.js', $content);
    }

    public function test_the_modal_is_absent_when_nothing_is_playable(): void
    {
        ProductApplication::query()->forceDelete();
        $this->makeApplication(['label' => 'Tubulars', 'video_file' => null]);

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringNotContainsString('product-application-modal', $content);
    }

    public function test_an_inert_list_row_does_not_open_the_modal(): void
    {
        ProductApplication::query()->forceDelete();
        $this->makeApplication(['label' => 'Tubulars', 'video_file' => null]);

        $content = $this->get('/product-applications/')->getContent();
        $list = $this->listBlock($content);

        $this->assertStringContainsString('Tubulars', $list);
        $this->assertStringNotContainsString('data-video-trigger', $list);
    }

    public function test_the_list_below_the_map_opens_the_same_modal(): void
    {
        $this->makeApplication([
            'label' => 'Purlins',
            'video_file' => 'product-applications/purlins.mp4',
        ]);

        $content = $this->get('/product-applications/')->getContent();
        $list = $this->listBlock($content);

        $this->assertStringContainsString('data-video-trigger', $list);
        $this->assertStringContainsString('/storage/product-applications/purlins.mp4', $list);
        $this->assertStringContainsString('aria-label="Play video: Purlins"', $list);
    }

    public function test_the_modal_payload_carries_the_description_only_when_set(): void
    {
        $with = $this->makeApplication([
            'label' => 'With Detail',
            'video_file' => 'product-applications/a.mp4',
            'description' => 'Zulu detail line',
        ]);
        $without = $this->makeApplication([
            'label' => 'Without Detail',
            'video_file' => 'product-applications/b.mp4',
            'description' => null,
        ]);

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringContainsString('data-description="Zulu detail line"', $this->circleTag($content, $with->id));
        $this->assertStringNotContainsString('data-description', $this->circleTag($content, $without->id));
    }

    public function test_descriptions_are_escaped_on_output(): void
    {
        $this->makeApplication([
            'label' => 'Escaped',
            'video_file' => 'product-applications/escaped.mp4',
            'description' => '"><script>alert("x")</script>',
        ]);

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringNotContainsString('<script>alert("x")</script>', $content);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $content);
    }

    /** The opening tag of one circle, located by its record id. */
    private function circleTag(string $content, int $id): string
    {
        $this->assertMatchesRegularExpression(
            '/data-product-application="'.$id.'"/',
            $content
        );
        preg_match('/<[^>]*data-product-application="'.$id.'"[^>]*>/', $content, $matches);

        return $matches[0] ?? '';
    }

    /** The list beneath the map, scoped so the rest of the page can't satisfy an assertion. */
    private function listBlock(string $content): string
    {
        preg_match('/<ul[^>]*product-applications-list[^>]*>.*?<\/ul>/s', $content, $matches);

        return $matches[0] ?? '';
    }

    /** @return array{0: \DOMDocument, 1: \DOMXPath} */
    private function dom(string $html): array
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return [$document, new \DOMXPath($document)];
    }
}
