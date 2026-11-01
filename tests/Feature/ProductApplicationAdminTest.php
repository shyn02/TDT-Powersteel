<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ProductApplications\Pages\CreateProductApplication;
use App\Filament\Admin\Resources\ProductApplications\Pages\EditProductApplication;
use App\Filament\Admin\Resources\ProductApplications\Schemas\ProductApplicationForm;
use App\Filament\Admin\Resources\ProductApplications\Tables\ProductApplicationsTable;
use App\Models\ProductApplication;
use App\Models\User;
use App\Support\MapPosition;
use App\Support\MediaUploadRules;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ProductApplicationAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Filament page components need a current panel when mounted directly
        // through Livewire rather than through an HTTP request.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function admin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->profile()->update(['position' => 'admin']);

        return $user;
    }

    private function member(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->profile()->update(['position' => 'sales_rep']);

        return $user;
    }

    public function test_only_admins_can_reach_the_product_applications_admin(): void
    {
        $this->get('/admin/product-applications')->assertRedirect();

        $this->actingAs($this->member())
            ->get('/admin/product-applications')
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get('/admin/product-applications')
            ->assertOk();
    }

    public function test_admin_can_open_the_create_and_edit_screens(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/product-applications/create')->assertOk();

        $application = ProductApplication::query()->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/product-applications/'.$application->id.'/edit')
            ->assertOk()
            ->assertSee('Application video')
            ->assertSee('Visibility and order')
            ->assertSee('tdt-position-picker', false)
            ->assertSee('Horizontal (x)')
            ->assertSee('Vertical (y)');
    }

    public function test_form_uses_the_shared_media_contract_for_both_uploads(): void
    {
        $source = file_get_contents((new \ReflectionClass(ProductApplicationForm::class))->getFileName());

        $this->assertStringContainsString('MediaUploadRules::MIMES', $source);
        $this->assertStringContainsString('MediaUploadRules::MAX_KILOBYTES', $source);
        $this->assertStringContainsString('MediaUploadRules::VIDEO_MIMES', $source);
        $this->assertStringContainsString('MediaUploadRules::VIDEO_MAX_KILOBYTES', $source);
        $this->assertStringContainsString('MediaUploadRules::DIRECTORY_PRODUCT_APPLICATION', $source);
        $this->assertStringContainsString("disk('public')", $source);
        $this->assertStringContainsString('imageEditor()', $source);

        // The seed key stays out of the editable form.
        $this->assertStringNotContainsString("TextInput::make('migration_key')", $source);
    }

    public function test_table_carries_search_sort_and_archive_affordances(): void
    {
        $source = file_get_contents((new \ReflectionClass(ProductApplicationsTable::class))->getFileName());

        $this->assertStringContainsString("TextColumn::make('label')", $source);
        $this->assertStringContainsString("ToggleColumn::make('is_active')", $source);
        $this->assertStringContainsString('TrashedFilter', $source);
        $this->assertStringContainsString('RestoreAction', $source);
        $this->assertStringContainsString('ForceDeleteAction', $source);
        $this->assertStringContainsString("label('Video')", $source);
    }

    public function test_create_guard_rejects_incomplete_or_invalid_media(): void
    {
        Storage::fake('public');

        $image = $this->callGuard([
            'label' => 'Guarded',
            'image' => 'livewire-tmp/incomplete.tmp',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $image['image'] ?? null);

        $video = $this->callGuard([
            'label' => 'Guarded',
            'video_file' => 'livewire-tmp/incomplete.mp4',
        ]);
        $this->assertSame(MediaUploadRules::VIDEO_MESSAGE, $video['video_file'] ?? null);

        // Right directory, wrong type.
        Storage::disk('public')->put('product-applications/clip.txt', 'not a video');
        $wrongType = $this->callGuard([
            'label' => 'Guarded',
            'video_file' => 'product-applications/clip.txt',
        ]);
        $this->assertSame(MediaUploadRules::VIDEO_MESSAGE, $wrongType['video_file'] ?? null);

        // Wrong directory.
        Storage::disk('public')->putFileAs(
            'products',
            UploadedFile::fake()->image('thumb.jpg'),
            'thumb.jpg'
        );
        $wrongDir = $this->callGuard([
            'label' => 'Guarded',
            'image' => 'products/thumb.jpg',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $wrongDir['image'] ?? null);

        $this->assertSame([], $this->callGuard(['label' => 'Guarded']));
    }

    public function test_create_guard_accepts_a_completed_thumbnail_and_video(): void
    {
        Storage::fake('public');

        Storage::disk('public')->putFileAs(
            'product-applications',
            UploadedFile::fake()->image('thumb.jpg'),
            'thumb.jpg'
        );
        Storage::disk('public')->put(
            'product-applications/clip.mp4',
            (string) file_get_contents(public_path('static/videos/tdt-facebook-highlight.mp4'))
        );

        $this->assertSame([], $this->callGuard([
            'label' => 'Ready',
            'image' => 'product-applications/thumb.jpg',
            'video_file' => 'product-applications/clip.mp4',
        ]));
    }

    public function test_edit_guard_allows_keeping_media_but_validates_replacements(): void
    {
        Storage::fake('public');

        $page = (new \ReflectionClass(EditProductApplication::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(EditProductApplication::class, 'mutateFormDataBeforeSave');
        $method->setAccessible(true);

        // No media keys: untouched media passes, and migration_key is dropped.
        $kept = $method->invoke($page, ['label' => 'Kept', 'migration_key' => 'product-application-01']);
        $this->assertSame(['label' => 'Kept'], $kept);

        try {
            $method->invoke($page, ['video_file' => 'livewire-tmp/incomplete.mp4']);
            $this->fail('An invalid replacement video must throw.');
        } catch (ValidationException $e) {
            $this->assertSame(MediaUploadRules::VIDEO_MESSAGE, $e->errors()['video_file'][0] ?? null);
        }
    }

    public function test_create_action_is_disabled_while_uploading_and_replacing_defers_deletion(): void
    {
        $instance = (new \ReflectionClass(CreateProductApplication::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(CreateProductApplication::class, 'getCreateFormAction');
        $method->setAccessible(true);

        /** @var Action $action */
        $action = $method->invoke($instance);
        $this->assertSame('disabled', $action->getExtraAttributes()['wire:loading.attr'] ?? null);

        $editSource = file_get_contents((new \ReflectionClass(EditProductApplication::class))->getFileName());
        $this->assertStringContainsString('DB::afterCommit', $editSource);
        $this->assertStringContainsString("getOriginal('image')", $editSource);
        $this->assertStringContainsString("getOriginal('video_file')", $editSource);
        $this->assertStringContainsString('scheduleOldFileDeletion', $editSource);
    }

    public function test_a_video_added_here_plays_on_the_public_page(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put(
            'product-applications/clip.mp4',
            (string) file_get_contents(public_path('static/videos/tdt-facebook-highlight.mp4'))
        );

        $application = ProductApplication::create([
            'label' => 'Zulu Video Product',
            'video_file' => 'product-applications/clip.mp4',
            'x' => 18.7,
            'y' => 54.63,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $content = $this->get('/product-applications/')->getContent();

        $this->assertStringContainsString('aria-label="Play video: Zulu Video Product"', $content);
        $this->assertStringContainsString('/storage/product-applications/clip.mp4', $content);

        // Hiding it in the admin removes it from the public page.
        $application->update(['is_active' => false]);
        $this->assertStringNotContainsString('Zulu Video Product', $this->get('/product-applications/')->getContent());
    }

    public function test_position_inputs_drive_a_live_preview(): void
    {
        $source = file_get_contents((new \ReflectionClass(ProductApplicationForm::class))->getFileName());

        $this->assertStringContainsString("TextInput::make('x')", $source);
        $this->assertStringContainsString("TextInput::make('y')", $source);
        // The preview only follows the inputs if they re-render the form.
        $this->assertStringContainsString('->live()', $source);
        $this->assertStringContainsString('product-application-position-preview', $source);

        // The leader line's endpoint is editable, and the preview receives it.
        $this->assertStringContainsString("TextInput::make('anchor_x')", $source);
        $this->assertStringContainsString("TextInput::make('anchor_y')", $source);
        $this->assertStringContainsString("'anchor' =>", $source);
    }

    public function test_position_picker_makes_the_circle_and_line_end_draggable(): void
    {
        $html = view('filament.admin.product-application-position-preview', [
            'position' => MapPosition::from(20, 30),
            'anchor' => MapPosition::from(60, 70),
            'thumbnail' => '/storage/product-applications/purlins.png',
        ])->render();

        // Two handles exist and the drag lifecycle is wired.
        $this->assertStringContainsString('tdt-position-picker-handle', $html);
        $this->assertStringContainsString('tdt-position-picker-anchor', $html);
        $this->assertStringContainsString('x-on:pointerdown="begin($event)"', $html);
        $this->assertStringContainsString('x-on:pointermove="move($event)"', $html);
        $this->assertStringContainsString('x-on:pointerup="finish($event)"', $html);

        // The thumbnail travels with the circle handle.
        $this->assertStringContainsString('/storage/product-applications/purlins.png', $html);

        // Dragging writes the form state once, on release.
        $this->assertStringContainsString("this.\$wire.set('data.x'", $html);
        $this->assertStringContainsString("this.\$wire.set('data.y'", $html);
        $this->assertStringContainsString("this.\$wire.set('data.anchor_x'", $html);
        $this->assertStringContainsString("this.\$wire.set('data.anchor_y'", $html);

        // Both handles start where the stored values are.
        $this->assertStringContainsString('left: 20%; top: 30%', $html);
        $this->assertStringContainsString('left: 60%; top: 70%', $html);
        $this->assertStringContainsString('Leader line to 60% / 70%', $html);
    }

    public function test_position_picker_can_add_and_remove_the_leader_line(): void
    {
        $withAnchor = view('filament.admin.product-application-position-preview', [
            'position' => MapPosition::from(20, 30),
            'anchor' => MapPosition::from(60, 70),
        ])->render();

        $this->assertStringContainsString('Remove leader line', $withAnchor);
        $this->assertStringContainsString('x-on:click="removeLine()"', $withAnchor);

        $withoutAnchor = view('filament.admin.product-application-position-preview', [
            'position' => MapPosition::from(20, 30),
            'anchor' => null,
        ])->render();

        $this->assertStringContainsString('Add leader line', $withoutAnchor);
        $this->assertStringContainsString('x-on:click="addLine()"', $withoutAnchor);
        $this->assertStringNotContainsString('Leader line to', $withoutAnchor);

        // The line element is present but hidden until an anchor exists.
        $this->assertStringContainsString('x-show="ax !== null"', $withoutAnchor);
    }

    public function test_position_picker_without_an_anchor_sits_the_line_on_the_circle(): void
    {
        $html = view('filament.admin.product-application-position-preview', [
            'position' => MapPosition::from(20, 30),
            'anchor' => null,
        ])->render();

        // The hidden anchor handle starts at the circle, so adding a line
        // never jumps to a stale spot.
        $this->assertMatchesRegularExpression(
            '/tdt-position-picker-anchor[\s\S]*?left: 20%; top: 30%/',
            $html
        );
    }

    public function test_position_preview_places_a_marker_at_the_percentage(): void
    {
        $html = view('filament.admin.product-application-position-preview', [
            'position' => MapPosition::from(62.5, 33.25),
        ])->render();

        $this->assertStringContainsString('left: 62.5%', $html);
        $this->assertStringContainsString('top: 33.25%', $html);
        $this->assertStringContainsString('facility-map.png', $html);
        $this->assertStringContainsString('Circle at 62.5% / 33.25%', $html);
    }

    public function test_position_preview_centres_when_unset_and_flags_out_of_range(): void
    {
        $empty = view('filament.admin.product-application-position-preview', [
            'position' => MapPosition::from(null, ''),
        ])->render();

        $this->assertStringContainsString('left: 50%', $empty);
        $this->assertStringContainsString('top: 50%', $empty);

        // An invalid entry renders at the edge but is never presented as a
        // valid position.
        $outside = view('filament.admin.product-application-position-preview', [
            'position' => MapPosition::from(150, -20),
        ])->render();

        $this->assertStringContainsString('left: 100%', $outside);
        $this->assertStringContainsString('top: 0%', $outside);
        $this->assertStringContainsString('is-outside', $outside);
        $this->assertStringContainsString('150% / -20% is outside the map', $outside);
    }

    public function test_position_percentages_keep_the_stored_precision(): void
    {
        $this->assertSame('50', MapPosition::from(null, null)->xPercent());
        $this->assertSame('18.7', MapPosition::from(18.7, 54.63)->xPercent());
        $this->assertSame('54.63', MapPosition::from(18.7, 54.63)->yPercent());
        $this->assertSame('0', MapPosition::from(0, 100)->xPercent());
    }

    public function test_saving_a_new_position_moves_the_circle_on_the_public_page(): void
    {
        $application = ProductApplication::create([
            'label' => 'Zulu Moved Product',
            'x' => 20,
            'y' => 30,
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $content = $this->get('/product-applications/')->getContent();
        $this->assertStringContainsString('left: 20%; top: 30%', $content);

        $application->update(['x' => 70.5, 'y' => 80.25]);

        $moved = $this->get('/product-applications/')->getContent();
        $this->assertStringContainsString('left: 70.5%; top: 80.25%', $moved);
    }

    public function test_out_of_range_position_is_rejected(): void
    {
        $application = ProductApplication::query()->firstOrFail();

        $this->actingAs($this->admin());

        Livewire::test(EditProductApplication::class, ['record' => $application->getRouteKey()])
            ->fillForm(['x' => 150])
            ->call('save')
            ->assertHasFormErrors(['x']);

        Livewire::test(EditProductApplication::class, ['record' => $application->getRouteKey()])
            ->fillForm(['y' => -5])
            ->call('save')
            ->assertHasFormErrors(['y']);
    }

    public function test_only_managed_media_is_eligible_for_deletion(): void
    {
        $this->assertFalse(MediaUploadRules::shouldDeleteReplacedFile(null, 'product-applications/a.png'));
        $this->assertFalse(MediaUploadRules::shouldDeleteReplacedFile('  ', 'product-applications/a.png'));
        $this->assertFalse(MediaUploadRules::shouldDeleteReplacedFile('product-applications/a.png', 'product-applications/a.png'));
        $this->assertFalse(MediaUploadRules::shouldDeleteReplacedFile('/static/images/product-applications/purlins.png', 'product-applications/a.png'));

        $this->assertTrue(MediaUploadRules::shouldDeleteReplacedFile('product-applications/old.png', 'product-applications/new.png'));
        $this->assertTrue(MediaUploadRules::shouldDeleteReplacedFile('product-applications/old.mp4', null));
    }

    public function test_archived_applications_are_pruned_after_thirty_days(): void
    {
        $application = ProductApplication::create([
            'label' => 'Stale',
            'x' => 50,
            'y' => 50,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $application->delete();

        ProductApplication::withTrashed()
            ->where('id', $application->id)
            ->update(['deleted_at' => now()->subDays(31)]);

        $this->artisan('prune:archived')->assertSuccessful();

        $this->assertDatabaseMissing('product_applications', ['id' => $application->id]);
    }

    public function test_archive_restore_and_force_delete(): void
    {
        $application = ProductApplication::create([
            'label' => 'Archivable',
            'x' => 50,
            'y' => 50,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $id = $application->id;

        $application->delete();
        $this->assertSoftDeleted('product_applications', ['id' => $id]);
        $this->assertSame(0, ProductApplication::where('id', $id)->count());

        ProductApplication::onlyTrashed()->where('id', $id)->restore();
        $this->assertDatabaseHas('product_applications', ['id' => $id, 'deleted_at' => null]);

        ProductApplication::where('id', $id)->first()->forceDelete();
        $this->assertDatabaseMissing('product_applications', ['id' => $id]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function callGuard(array $data): array
    {
        $instance = (new \ReflectionClass(CreateProductApplication::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(CreateProductApplication::class, 'guardCompletedUploads');
        $method->setAccessible(true);

        try {
            $method->invoke($instance, $data);
        } catch (ValidationException $e) {
            $flat = [];
            foreach ($e->errors() as $field => $messages) {
                $flat[$field] = is_array($messages) ? ($messages[0] ?? '') : $messages;
            }

            return $flat;
        }

        return [];
    }
}
