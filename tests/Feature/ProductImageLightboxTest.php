<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Products\Schemas\ProductForm;
use App\Filament\Admin\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Admin\Resources\Products\Tables\ProductsTable;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\ProductImageLightbox;
use Tests\TestCase;

class ProductImageLightboxTest extends TestCase
{
    public function test_trigger_attributes_mark_thumbnails_enlargeable(): void
    {
        $attrs = ProductImageLightbox::triggerAttributes();

        $this->assertSame('true', $attrs[ProductImageLightbox::TRIGGER_ATTRIBUTE]);
        $this->assertSame('0', $attrs['tabindex']);
        $this->assertSame('button', $attrs['role']);
        $this->assertStringContainsString('zoom-in', $attrs['style']);
        $this->assertArrayHasKey('aria-label', $attrs);
        $this->assertArrayHasKey('title', $attrs);
    }

    public function test_url_delegates_to_shared_media_contract_with_empty_guard(): void
    {
        // Empty/missing image must resolve to null so no popup is triggered.
        $this->assertNull(ProductImageLightbox::url(null));
        $this->assertNull(ProductImageLightbox::url('   '));
        $this->assertSame('/static/images/steel1.jpg', ProductImageLightbox::url('/static/images/steel1.jpg'));

        // Non-model/array records carry no image.
        $this->assertNull(ProductImageLightbox::recordImage(null));
        $this->assertNull(ProductImageLightbox::recordImage([]));
        $this->assertNull(ProductImageLightbox::recordImage(['image' => '']));
        $this->assertSame('products/photo.jpg', ProductImageLightbox::recordImage(['image' => 'products/photo.jpg']));
    }

    public function test_table_and_infolist_wire_shared_trigger_attributes(): void
    {
        $tableSource = file_get_contents((new \ReflectionClass(ProductsTable::class))->getFileName());
        $this->assertStringContainsString('extraImgAttributes', $tableSource);
        $this->assertStringContainsString('ProductImageLightbox::triggerAttributes', $tableSource);

        $infolistSource = file_get_contents((new \ReflectionClass(ProductInfolist::class))->getFileName());
        $this->assertStringContainsString('extraImgAttributes', $infolistSource);
        $this->assertStringContainsString('ProductImageLightbox::triggerAttributes', $infolistSource);
    }

    public function test_form_preview_uses_shared_url_and_hides_without_image(): void
    {
        $formSource = file_get_contents((new \ReflectionClass(ProductForm::class))->getFileName());
        $this->assertStringContainsString('ProductImageLightbox::url', $formSource);
        $this->assertStringContainsString('ProductImageLightbox::triggerAttributes', $formSource);
        $this->assertStringContainsString('->visible(', $formSource);
    }

    public function test_overlay_markup_meets_accessibility_contract(): void
    {
        $html = view('filament.admin.product-image-lightbox')->render();

        // Single shared overlay mount point.
        $this->assertStringContainsString('data-tdt-lightbox-overlay', $html);

        // Centered dialog semantics.
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);

        // Visible close control with keyboard focus target.
        $this->assertStringContainsString('aria-label="Close enlarged image"', $html);
        $this->assertStringContainsString('data-tdt-lightbox-close', $html);

        // Backdrop click + Escape close paths.
        $this->assertStringContainsString('click.self', $html);
        $this->assertStringContainsString('escape', $html);

        // Background scroll lock + restore.
        $this->assertStringContainsString('overflow', $html);

        // Opened only with a real src (empty/missing guard), via the shared event.
        $this->assertStringContainsString('tdt:open-lightbox', $html);

        // Closed overlay renders no resolvable image (bound src only, no popup).
        $this->assertStringNotContainsString('<img src=', $html);
    }

    public function test_admin_panel_mounts_shared_overlay_once(): void
    {
        $source = file_get_contents((new \ReflectionClass(AdminPanelProvider::class))->getFileName());
        $this->assertStringContainsString('BODY_END', $source);
        $this->assertStringContainsString('product-image-lightbox', $source);
    }
}
