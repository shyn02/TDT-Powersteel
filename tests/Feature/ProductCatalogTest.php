<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    private function makeCategory(array $overrides = []): ProductCategory
    {
        return ProductCategory::create(array_merge([
            'name' => 'Steel Bars',
            'slug' => 'steel-bars',
            'is_active' => true,
        ], $overrides));
    }

    private function makeProduct(ProductCategory $category, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Deformed Bar',
            'is_active' => true,
        ], $overrides));
    }

    public function test_specs_and_sizes_split_into_trimmed_non_blank_lines(): void
    {
        $product = new Product([
            'specs' => "  PNS Certified \r\n\r\n Grade 60 \n",
            'sizes' => "10mm\r12mm\n\n 16mm  ",
        ]);

        $this->assertSame(['PNS Certified', 'Grade 60'], $product->specsList());
        $this->assertSame(['10mm', '12mm', '16mm'], $product->sizesList());
    }

    public function test_specs_and_sizes_are_empty_when_unset(): void
    {
        $product = new Product;

        $this->assertSame([], $product->specsList());
        $this->assertSame([], $product->sizesList());
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $this->assertTrue((new Product(['is_active' => 1]))->is_active);
        $this->assertFalse((new Product(['is_active' => 0]))->is_active);
    }

    public function test_products_page_lists_only_active_categories_alphabetically(): void
    {
        $this->makeCategory(['name' => 'Zulu Bars', 'slug' => 'zulu-bars']);
        $this->makeCategory(['name' => 'Alpha Beams', 'slug' => 'alpha-beams']);
        $this->makeCategory(['name' => 'Hidden Pipes', 'slug' => 'hidden-pipes', 'is_active' => false]);
        $this->makeCategory(['name' => 'Archived Plates', 'slug' => 'archived-plates'])->delete();

        $content = $this->get('/products/')->assertOk()->getContent();

        $this->assertTrue(strpos($content, 'Alpha Beams') < strpos($content, 'Zulu Bars'));
        $this->assertStringNotContainsString('Hidden Pipes', $content);
        $this->assertStringNotContainsString('Archived Plates', $content);
    }

    public function test_each_category_card_links_to_its_detail_page(): void
    {
        $category = $this->makeCategory();

        $this->get('/products/')
            ->assertOk()
            ->assertSee(route('category_detail', $category->slug), false);
    }

    public function test_category_cards_render_stored_images_with_a_text_fallback(): void
    {
        $this->makeCategory([
            'name' => 'With Image',
            'slug' => 'with-image',
            'image' => 'categories/bars.jpg',
        ]);
        $this->makeCategory(['name' => 'No Image', 'slug' => 'no-image']);

        $content = $this->get('/products/')->assertOk()->getContent();

        $this->assertStringContainsString('storage/categories/bars.jpg', $content);
        $this->assertSame(2, substr_count($content, 'fallback-placeholder'));
        $this->assertStringContainsString('WITH IMAGE', $content);
        $this->assertStringContainsString('NO IMAGE', $content);
    }

    public function test_category_page_lists_only_its_active_products_alphabetically(): void
    {
        $category = $this->makeCategory();
        $other = $this->makeCategory(['name' => 'Pipes', 'slug' => 'pipes']);

        $this->makeProduct($category, ['name' => 'Zulu Rebar']);
        $this->makeProduct($category, ['name' => 'Alpha Rebar']);
        $this->makeProduct($category, ['name' => 'Hidden Rebar', 'is_active' => false]);
        $this->makeProduct($category, ['name' => 'Trashed Rebar'])->delete();
        $this->makeProduct($other, ['name' => 'Other Product']);

        $content = $this->get(route('category_detail', $category->slug))->assertOk()->getContent();

        $this->assertTrue(strpos($content, 'Alpha Rebar') < strpos($content, 'Zulu Rebar'));
        $this->assertStringNotContainsString('Hidden Rebar', $content);
        $this->assertStringNotContainsString('Trashed Rebar', $content);
        $this->assertStringNotContainsString('Other Product', $content);
    }

    public function test_category_page_renders_descriptions_and_specs_bullets(): void
    {
        $category = $this->makeCategory([
            'name' => 'Steel Bars',
            'slug' => 'steel-bars',
            'banner_desc' => 'Banner copy for bars.',
            'intro_desc' => 'Intro copy for bars.',
        ]);
        $this->makeProduct($category, [
            'name' => 'Rebar',
            'description' => 'Heat treated for strength.',
            'specs' => "PNS Certified\nGrade 60",
        ]);

        $this->get(route('category_detail', $category->slug))
            ->assertOk()
            ->assertSee('Banner copy for bars.')
            ->assertSee('Intro copy for bars.')
            ->assertSee('Heat treated for strength.')
            ->assertSee('PNS Certified')
            ->assertSee('Grade 60');
    }

    public function test_empty_category_page_shows_the_empty_state(): void
    {
        $category = $this->makeCategory(['name' => 'Empty Cat', 'slug' => 'empty-cat']);

        $this->get(route('category_detail', $category->slug))
            ->assertOk()
            ->assertSee('No products listed in this category yet.');
    }

    public function test_inactive_or_unknown_categories_are_not_found(): void
    {
        $inactive = $this->makeCategory(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);

        $this->get(route('category_detail', $inactive->slug))->assertNotFound();
        $this->get(route('category_detail', 'does-not-exist'))->assertNotFound();
    }

    public function test_quote_buttons_carry_the_category_name(): void
    {
        $category = $this->makeCategory(['name' => 'Steel Plates', 'slug' => 'steel-plates']);
        $this->makeProduct($category, ['name' => 'Checkered Plate']);

        $this->get(route('category_detail', $category->slug))
            ->assertOk()
            ->assertSee('data-product="Steel Plates"', false);
    }

    public function test_calculator_button_hides_only_when_disabled_for_normal_categories(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['name' => 'Auto Bar']);
        $this->makeProduct($category, ['name' => 'No Calc Bar', 'calculator_type' => 'none']);

        $content = $this->get(route('category_detail', $category->slug))->assertOk()->getContent();

        $this->assertStringContainsString('data-product-name="Auto Bar"', $content);
        $this->assertStringNotContainsString('data-product-name="No Calc Bar"', $content);
    }

    public function test_excluded_category_products_only_show_the_calculator_when_a_formula_is_assigned(): void
    {
        $category = $this->makeCategory(['name' => 'Hardware', 'slug' => 'hardware']);
        $this->makeProduct($category, ['name' => 'Loose Bolt']);
        $this->makeProduct($category, ['name' => 'Typed Bolt', 'calculator_type' => 'round_bar']);
        $this->makeProduct($category, ['name' => 'No Calc Bolt', 'calculator_type' => 'none']);

        $content = $this->get(route('category_detail', $category->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-product-name="Loose Bolt"', $content);
        $this->assertStringContainsString('data-product-name="Typed Bolt"', $content);
        $this->assertStringContainsString('data-calc-type="round_bar"', $content);
        $this->assertStringNotContainsString('data-product-name="No Calc Bolt"', $content);
    }

    public function test_homepage_calculator_lists_only_eligible_products(): void
    {
        $normal = $this->makeCategory(['name' => 'Steel Bars', 'slug' => 'steel-bars']);
        $hardware = $this->makeCategory(['name' => 'Hardware', 'slug' => 'hardware']);
        $hidden = $this->makeCategory(['name' => 'Hidden Cat', 'slug' => 'hidden-cat', 'is_active' => false]);

        $this->makeProduct($normal, ['name' => 'Auto Round']);
        $this->makeProduct($normal, ['name' => 'Hidden Round', 'is_active' => false]);
        $this->makeProduct($normal, ['name' => 'No Calc', 'calculator_type' => 'none']);
        $this->makeProduct($hardware, ['name' => 'Plain Bolt']);
        $this->makeProduct($hardware, ['name' => 'Typed Bolt', 'calculator_type' => 'round_bar']);
        $this->makeProduct($hidden, ['name' => 'Ghost Product']);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-value="Auto Round"', $content);
        $this->assertStringContainsString('data-value="Typed Bolt"', $content);
        $this->assertStringContainsString('data-calc-type="round_bar"', $content);
        $this->assertStringNotContainsString('data-value="Hidden Round"', $content);
        $this->assertStringNotContainsString('data-value="No Calc"', $content);
        $this->assertStringNotContainsString('data-value="Plain Bolt"', $content);
        $this->assertStringNotContainsString('data-value="Ghost Product"', $content);
    }

    public function test_sitemap_lists_only_active_categories(): void
    {
        $this->makeCategory(['name' => 'Active Bars', 'slug' => 'active-bars']);
        $this->makeCategory(['name' => 'Hidden Bars', 'slug' => 'hidden-bars', 'is_active' => false]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('/products/active-bars/', $xml);
        $this->assertStringNotContainsString('/products/hidden-bars/', $xml);
    }
}
