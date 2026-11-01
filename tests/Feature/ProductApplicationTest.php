<?php

namespace Tests\Feature;

use App\Models\ProductApplication;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductApplicationTest extends TestCase
{
    public function test_thumbnail_and_video_urls_resolve_through_the_shared_media_contract(): void
    {
        Storage::fake('public');

        $application = new ProductApplication([
            'image' => '/static/images/product-applications/purlins.png',
            'video_file' => 'product-applications/purlins.mp4',
        ]);

        // Static assets pass through; storage paths resolve on the public disk.
        $this->assertSame('/static/images/product-applications/purlins.png', $application->thumbnailUrl());
        $this->assertSame(
            Storage::disk('public')->url('product-applications/purlins.mp4'),
            $application->videoUrl()
        );

        $this->assertNull((new ProductApplication(['image' => null]))->thumbnailUrl());
        $this->assertNull((new ProductApplication(['image' => '  ']))->thumbnailUrl());
        $this->assertNull((new ProductApplication(['video_file' => null]))->videoUrl());
    }

    public function test_only_a_product_application_with_a_video_is_playable(): void
    {
        $this->assertFalse((new ProductApplication(['video_file' => null]))->isPlayable());
        $this->assertFalse((new ProductApplication(['video_file' => '']))->isPlayable());
        $this->assertTrue((new ProductApplication(['video_file' => 'product-applications/purlins.mp4']))->isPlayable());
    }

    public function test_an_anchor_is_optional(): void
    {
        $this->assertFalse((new ProductApplication(['anchor_x' => null, 'anchor_y' => null]))->hasAnchor());
        $this->assertFalse((new ProductApplication(['anchor_x' => 60, 'anchor_y' => null]))->hasAnchor());
        $this->assertNull((new ProductApplication)->anchorPosition());

        $anchored = new ProductApplication(['anchor_x' => 60, 'anchor_y' => 70]);
        $this->assertTrue($anchored->hasAnchor());
        $this->assertSame(60.0, $anchored->anchorPosition()->x());
        $this->assertSame(70.0, $anchored->anchorPosition()->y());
    }

    public function test_labels_sit_on_the_outer_side_of_the_circle(): void
    {
        $this->assertSame('left', (new ProductApplication(['x' => 18.7]))->labelSide());
        $this->assertSame('right', (new ProductApplication(['x' => 50]))->labelSide());
        $this->assertSame('right', (new ProductApplication(['x' => 80.26]))->labelSide());
    }
}
