<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\QuoteRequests\Tables\QuoteRequestsTable;
use App\Models\QuoteRequest;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuoteSubmissionTest extends TestCase
{
    public function test_hero_remarks_longer_than_150_characters_are_preserved(): void
    {
        $remarks = str_repeat('H', 151);

        $response = $this->postJson(route('submit_quote'), [
            'name' => 'Hero Customer',
            'mobile' => '09171234567',
            'remarks' => "  {$remarks}  ",
        ]);

        $response->assertOk();
        $this->assertSame($remarks, QuoteRequest::query()->latest('id')->value('estimated_qty'));
    }

    public function test_hero_remarks_of_4000_characters_are_preserved(): void
    {
        $remarks = str_repeat('R', 4000);

        $response = $this->postJson(route('submit_quote'), [
            'name' => 'Long Remark Customer',
            'mobile' => '09171234568',
            'remarks' => "  {$remarks}  ",
        ]);

        $response->assertOk();
        $this->assertSame($remarks, QuoteRequest::query()->latest('id')->value('estimated_qty'));
    }

    public function test_hero_remarks_of_exactly_150_characters_are_preserved(): void
    {
        $remarks = str_repeat('E', 150);

        $response = $this->postJson(route('submit_quote'), [
            'name' => 'Exact Length Customer',
            'mobile' => '09171234572',
            'remarks' => $remarks,
        ]);

        $response->assertOk();
        $this->assertSame($remarks, QuoteRequest::query()->latest('id')->value('estimated_qty'));
    }

    public function test_multibyte_hero_remarks_longer_than_150_characters_are_preserved(): void
    {
        $remarks = str_repeat('鋼', 151);

        $response = $this->postJson(route('submit_quote'), [
            'name' => 'Multibyte Customer',
            'mobile' => '09171234573',
            'remarks' => $remarks,
        ]);

        $response->assertOk();
        $this->assertSame($remarks, QuoteRequest::query()->latest('id')->value('estimated_qty'));
    }

    public function test_modal_estimated_quantity_preserves_full_composed_value(): void
    {
        $estimatedQty = str_repeat('M', 1500);

        $response = $this->postJson(route('submit_quote'), [
            'clientName' => 'Modal Customer',
            'clientContact' => '09171234569',
            'estimatedQty' => "  {$estimatedQty}  ",
            'subProduct' => 'Wide Flange',
            'sizeSpec' => '200x100',
        ]);

        $response->assertOk();
        $this->assertSame(
            "{$estimatedQty} (Wide Flange | 200x100)",
            QuoteRequest::query()->latest('id')->value('estimated_qty')
        );
    }

    public function test_payloads_over_10_kb_are_still_rejected(): void
    {
        $response = $this->postJson(route('submit_quote'), [
            'name' => 'Large Payload Customer',
            'mobile' => '09171234570',
            'remarks' => str_repeat('P', 11000),
        ]);

        $response->assertStatus(413);
    }

    public function test_invalid_phone_is_still_rejected(): void
    {
        $response = $this->postJson(route('submit_quote'), [
            'name' => 'Invalid Phone Customer',
            'mobile' => 'not-a-phone',
            'remarks' => 'A valid remark',
        ]);

        $response->assertUnprocessable();
    }

    public function test_non_string_phone_is_still_rejected(): void
    {
        $response = $this->postJson(route('submit_quote'), [
            'name' => 'Non String Phone Customer',
            'mobile' => ['09171234571'],
            'remarks' => 'A valid remark',
        ]);

        $response->assertUnprocessable();
    }

    public function test_quote_estimated_quantity_uses_text_schema(): void
    {
        $this->assertSame('text', Schema::getColumnType('quote_requests', 'estimated_qty'));
    }

    public function test_migration_upgrade_preserves_existing_varchar_values(): void
    {
        Schema::table('quote_requests', function (Blueprint $table): void {
            $table->string('estimated_qty', 150)->change();
        });

        $existingValue = str_repeat('V', 150);
        QuoteRequest::create([
            'full_name' => 'Existing Varchar Quote',
            'estimated_qty' => $existingValue,
            'status' => 'new',
            'source' => 'home',
        ]);

        $migration = require database_path('migrations/2026_09_18_000001_expand_quote_estimated_qty_to_text.php');
        $migration->up();

        $this->assertSame('text', Schema::getColumnType('quote_requests', 'estimated_qty'));
        $this->assertSame($existingValue, QuoteRequest::query()->latest('id')->value('estimated_qty'));
    }

    public function test_migration_refuses_to_restore_a_string_column_when_values_would_be_truncated(): void
    {
        QuoteRequest::create([
            'full_name' => 'Long Existing Quote',
            'estimated_qty' => str_repeat('L', 151),
            'status' => 'new',
            'source' => 'home',
        ]);

        $migration = require database_path('migrations/2026_09_18_000001_expand_quote_estimated_qty_to_text.php');

        try {
            $migration->down();
            $this->fail('Expected the migration rollback to refuse truncating quote values.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('exceed 150 characters', $exception->getMessage());
        }

        $this->assertSame('text', Schema::getColumnType('quote_requests', 'estimated_qty'));
    }

    public function test_admin_table_displays_the_full_estimated_quantity_without_a_character_limit(): void
    {
        $table = QuoteRequestsTable::configure(Table::make($this->createMock(HasTable::class)));
        $estimatedQuantityColumn = $table->getColumn('estimated_qty');

        $this->assertNotNull($estimatedQuantityColumn);
        $this->assertNull($estimatedQuantityColumn->getCharacterLimit());
    }
}
