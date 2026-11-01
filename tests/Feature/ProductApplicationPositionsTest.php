<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ProductApplications\Pages\EditProductApplicationPositions;
use App\Models\ProductApplication;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class ProductApplicationPositionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function admin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->profile()->update(['position' => 'admin']);

        return $user;
    }

    public function test_only_admins_can_open_the_mass_position_editor(): void
    {
        $this->get('/admin/product-applications/positions')->assertRedirect();

        $staff = User::factory()->create(['is_active' => true]);
        $staff->profile()->update(['position' => 'sales_rep']);

        $this->actingAs($staff)
            ->get('/admin/product-applications/positions')
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get('/admin/product-applications/positions')
            ->assertOk();
    }

    public function test_the_editor_renders_every_circle_and_anchor(): void
    {
        $content = $this->actingAs($this->admin())
            ->get('/admin/product-applications/positions')
            ->assertOk()
            ->assertSee('Save positions')
            ->assertSee('tdt-positions-editor-circle', false)
            ->assertSee('tdt-positions-editor-anchor', false)
            ->assertSee('tdt-positions-editor-lines', false)
            ->getContent();

        $this->assertSame(12, substr_count($content, 'tdt-positions-editor-circle"'));
        $this->assertSame(12, substr_count($content, 'tdt-positions-editor-anchor"'));
    }

    public function test_the_list_page_links_to_the_editor(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/product-applications')
            ->assertOk()
            ->assertSee('Mass move')
            ->assertSee('/admin/product-applications/positions', false);
    }

    public function test_saving_moves_every_circle_and_clamps_out_of_range_values(): void
    {
        $this->actingAs($this->admin());

        $first = ProductApplication::query()->orderBy('id')->firstOrFail();
        $second = ProductApplication::query()->orderBy('id')->skip(1)->firstOrFail();

        Livewire::test(EditProductApplicationPositions::class)
            ->call('save', [
                ['id' => $first->id, 'x' => 12.345, 'y' => 200, 'ax' => 5, 'ay' => 6],
                ['id' => $second->id, 'x' => -4, 'y' => 40.5, 'ax' => null, 'ay' => null],
            ])
            ->assertHasNoErrors();

        $first->refresh();
        $this->assertSame(12.35, $first->x);
        $this->assertSame(100.0, $first->y);
        $this->assertSame(5.0, $first->anchor_x);
        $this->assertSame(6.0, $first->anchor_y);

        $second->refresh();
        $this->assertSame(0.0, $second->x);
        $this->assertSame(40.5, $second->y);
        $this->assertNull($second->anchor_x);
        $this->assertNull($second->anchor_y);
    }

    public function test_unknown_ids_are_ignored(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(EditProductApplicationPositions::class)
            ->call('save', [
                ['id' => 999999, 'x' => 10, 'y' => 10, 'ax' => 20, 'ay' => 20],
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('product_applications', ['id' => 999999]);
    }

    public function test_a_mass_move_reaches_the_public_page(): void
    {
        $this->actingAs($this->admin());

        $application = ProductApplication::query()->orderBy('id')->firstOrFail();

        Livewire::test(EditProductApplicationPositions::class)
            ->call('save', [
                ['id' => $application->id, 'x' => 33, 'y' => 44, 'ax' => 66, 'ay' => 77],
            ]);

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringContainsString('left: 33%; top: 44%', $content);
        $this->assertMatchesRegularExpression(
            '/x1="33"[^>]*y1="44"[^>]*x2="66"[^>]*y2="77"/s',
            $content
        );
    }
}
