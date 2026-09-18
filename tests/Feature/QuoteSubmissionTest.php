<?php

namespace Tests\Feature;

use App\Models\QuoteRequest;
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

    public function test_migration_refuses_to_restore_a_string_column_when_values_would_be_truncated(): void
    {
        QuoteRequest::create([
            'full_name' => 'Long Existing Quote',
            'estimated_qty' => str_repeat('L', 151),
            'status' => 'new',
            'source' => 'home',
        ]);

        $migration = require database_path('migrations/2026_09_18_000001_expand_quote_estimated_qty_to_text.php');

        $this->expectException(\RuntimeException::class);
        $migration->down();
    }
}
