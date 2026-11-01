<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Projects\Pages\CreateProject;
use App\Filament\Admin\Resources\Projects\Pages\EditProject;
use App\Filament\Admin\Resources\Projects\ProjectResource;
use App\Filament\Admin\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Admin\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use App\Models\User;
use App\Support\MediaUploadRules;
use Filament\Actions\Action;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectAdminTest extends TestCase
{
    private function admin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->profile()->update(['position' => 'admin']);

        return $user;
    }

    private function makeProject(array $overrides = []): Project
    {
        return Project::create(array_merge([
            'tag' => 'Commercial',
            'image' => '/static/images/steel1.jpg',
            'location' => 'Manila',
            'title' => 'Admin Project',
            'info' => 'Admin info.',
            'is_published' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    public function test_guest_is_redirected_and_non_admin_is_forbidden(): void
    {
        $this->get('/admin/projects')->assertRedirect();

        $staff = User::factory()->create(['is_active' => true]);
        $staff->profile()->update(['position' => 'sales_rep']);
        $this->actingAs($staff)->get('/admin/projects')->assertForbidden();

        $inactive = User::factory()->create(['is_active' => false]);
        $this->actingAs($inactive)->get('/admin/projects')->assertForbidden();
    }

    public function test_admin_can_open_project_list(): void
    {
        $this->actingAs($this->admin())->get('/admin/projects')->assertStatus(200);
    }

    public function test_crud_creates_reads_updates_project(): void
    {
        $project = $this->makeProject(['title' => 'Original Title', 'sort_order' => 3]);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'Original Title']);

        $project->update(['title' => 'Renamed Title', 'is_published' => false, 'sort_order' => 7]);

        $this->assertSame('Renamed Title', $project->fresh()->title);
        $this->assertFalse((bool) $project->fresh()->is_published);
        $this->assertSame(7, (int) $project->fresh()->sort_order);
    }

    public function test_table_supports_search_filter_and_sort(): void
    {
        Project::query()->forceDelete();

        $this->makeProject(['title' => 'Searchable Tower', 'location' => 'Quezon City', 'sort_order' => 5]);
        $this->makeProject(['title' => 'Other Hall', 'location' => 'Cebu', 'is_published' => false, 'sort_order' => 1]);

        // Search boundary.
        $this->assertSame(1, Project::where('title', 'like', '%Searchable%')->count());
        $this->assertSame(1, Project::where('location', 'like', '%Cebu%')->count());

        // Filter boundary (published only).
        $this->assertSame(1, Project::where('is_published', true)->where('title', 'like', '%Tower%')->count());

        // Sort boundary (homepage order: sort_order, id).
        $ordered = Project::orderBy('sort_order')->orderBy('id')->pluck('title')->all();
        $this->assertSame(['Other Hall', 'Searchable Tower'], $ordered);

        // Table config carries search/filter/sort affordances.
        $tableSource = file_get_contents((new \ReflectionClass(ProjectsTable::class))->getFileName());
        $this->assertStringContainsString('searchable()', $tableSource);
        $this->assertStringContainsString('sortable()', $tableSource);
        $this->assertStringContainsString('TrashedFilter', $tableSource);

        $formSource = file_get_contents((new \ReflectionClass(ProjectForm::class))->getFileName());
        $this->assertStringContainsString('sort_order', $formSource);
        $this->assertStringContainsString('is_published', $formSource);
    }

    public function test_archive_restore_and_force_delete(): void
    {
        $project = $this->makeProject(['title' => 'Archivable']);
        $id = $project->id;

        $project->delete();
        $this->assertSoftDeleted('projects', ['id' => $id]);
        $this->assertSame(0, Project::where('id', $id)->count());
        $this->assertSame(1, Project::onlyTrashed()->where('id', $id)->count());

        Project::onlyTrashed()->where('id', $id)->restore();
        $this->assertDatabaseHas('projects', ['id' => $id, 'deleted_at' => null]);

        Project::where('id', $id)->first()->forceDelete();
        $this->assertDatabaseMissing('projects', ['id' => $id]);
    }

    public function test_form_uses_public_disk_fixed_dir_and_shared_constraints(): void
    {
        $source = file_get_contents((new \ReflectionClass(ProjectForm::class))->getFileName());

        $this->assertStringContainsString('MediaUploadRules::MIMES', $source);
        $this->assertStringContainsString('MediaUploadRules::MAX_KILOBYTES', $source);
        $this->assertStringContainsString('MediaUploadRules::DIRECTORY_PROJECT', $source);
        $this->assertStringContainsString("disk('public')", $source);
        $this->assertStringContainsString('imageEditor()', $source);
        $this->assertStringNotContainsString('preserveFilenames(true', $source);

        // migration_key stays hidden/immutable: never an editable text input.
        $this->assertStringNotContainsString("TextInput::make('migration_key')", $source);
    }

    public function test_create_guard_requires_completed_permanent_path(): void
    {
        Storage::fake('public');

        $bad = $this->callGuard(CreateProject::class, [
            'title' => 'Guarded',
            'image' => 'livewire-tmp/incomplete.tmp',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $bad['image'] ?? null);

        $missing = $this->callGuard(CreateProject::class, ['title' => 'Missing image']);
        $this->assertSame(MediaUploadRules::MESSAGE, $missing['image'] ?? null);

        $cover = UploadedFile::fake()->image('tile.jpg');
        Storage::disk('public')->putFileAs('projects', $cover, 'tile.jpg');
        $ok = $this->callGuard(CreateProject::class, [
            'title' => 'Guarded ok',
            'image' => 'projects/tile.jpg',
        ]);
        $this->assertSame([], $ok);
    }

    public function test_create_guard_rejects_wrong_prefix_bad_mime_and_oversize(): void
    {
        Storage::fake('public');

        $tile = UploadedFile::fake()->image('tile.jpg');
        Storage::disk('public')->putFileAs('categories', $tile, 'tile.jpg');
        $prefix = $this->callGuard(CreateProject::class, [
            'title' => 'Wrong prefix',
            'image' => 'categories/tile.jpg',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $prefix['image']);

        Storage::disk('public')->put('projects/fake.jpg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $mime = $this->callGuard(CreateProject::class, [
            'title' => 'Bad mime',
            'image' => 'projects/fake.jpg',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $mime['image']);

        Storage::disk('public')->put('projects/big.jpg', str_repeat('0', 5121 * 1024));
        $size = $this->callGuard(CreateProject::class, [
            'title' => 'Too big',
            'image' => 'projects/big.jpg',
        ]);
        $this->assertSame(MediaUploadRules::MESSAGE, $size['image']);
    }

    public function test_edit_guard_allows_keeping_image_but_validates_replacement(): void
    {
        Storage::fake('public');

        $page = (new \ReflectionClass(EditProject::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(EditProject::class, 'mutateFormDataBeforeSave');
        $method->setAccessible(true);

        // No image key: untouched image passes.
        $this->assertSame(['title' => 'Kept'], $method->invoke($page, ['title' => 'Kept']));

        try {
            $method->invoke($page, ['image' => 'livewire-tmp/incomplete.tmp']);
            $this->fail('Invalid replacement image must throw.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(MediaUploadRules::MESSAGE, $e->errors()['image'][0] ?? null);
        }
    }

    public function test_create_action_is_disabled_while_uploading_and_edits_defer_deletion(): void
    {
        $instance = (new \ReflectionClass(CreateProject::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(CreateProject::class, 'getCreateFormAction');
        $method->setAccessible(true);
        /** @var Action $action */
        $action = $method->invoke($instance);
        $attributes = $action->getExtraAttributes();
        $this->assertSame('disabled', $attributes['wire:loading.attr'] ?? null);

        $editSource = file_get_contents((new \ReflectionClass(EditProject::class))->getFileName());
        $this->assertStringContainsString('DB::afterCommit', $editSource);

        $this->assertTrue(ProjectResource::canViewAny() === false, 'Guest context must not pass admin gate.');
    }

    /**
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

        return [];
    }
}
