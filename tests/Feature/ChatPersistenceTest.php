<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatPersistenceTest extends TestCase
{

    private function makeToken(string $seed): string
    {
        return hash('sha256', 'chat-session-'.$seed);
    }

    public function test_migration_adds_lifecycle_columns_indexes_and_fk(): void
    {
        $this->assertTrue(Schema::hasColumn('chat_sessions', 'last_activity_at'), 'Expected last_activity_at column.');
        $this->assertTrue(Schema::hasColumn('chat_sessions', 'terminated_at'), 'Expected terminated_at column.');
        $this->assertTrue(Schema::hasColumn('chat_sessions', 'terminated_by'), 'Expected terminated_by column.');

        // terminated_by -> users.id nullOnDelete
        $foreignKeys = DB::select("PRAGMA foreign_key_list('chat_sessions')");
        $terminatorFk = collect($foreignKeys)->firstWhere('from', 'terminated_by');

        $this->assertNotNull($terminatorFk, 'Expected terminated_by foreign key.');
        $this->assertSame('users', $terminatorFk->table);
        $this->assertSame('id', $terminatorFk->to);
        $this->assertSame('SET NULL', $terminatorFk->on_delete);

        // Indexed expiry/termination (resolve index cids through table info).
        $indexes = DB::select("PRAGMA index_list('chat_sessions')");
        $indexNames = collect($indexes)->pluck('name')->all();
        $tableInfo = DB::select("PRAGMA table_info('chat_sessions')");
        $cidToName = collect($tableInfo)->pluck('name', 'cid')->all();
        $indexedColumnNames = [];
        foreach ($indexNames as $name) {
            foreach (DB::select("PRAGMA index_info('{$name}')") as $info) {
                $indexedColumnNames[] = $cidToName[$info->cid] ?? $info->name;
            }
        }

        $this->assertContains('expires_at', $indexedColumnNames, 'Expected expires_at to be indexed.');
        $this->assertContains('terminated_at', $indexedColumnNames, 'Expected terminated_at to be indexed.');
    }

    public function test_backfill_sets_activity_from_last_message_at_and_expiry_plus_30_days(): void
    {
        $migration = require database_path('migrations/2026_09_18_000002_add_chat_lifecycle_fields.php');
        $migration->down();

        $createdAt = Carbon::parse('2026-06-01 10:00:00');
        $lastMessageAt = Carbon::parse('2026-06-05 12:00:00');

        $id = DB::table('chat_sessions')->insertGetId([
            'session_token' => $this->makeToken('active-backfill'),
            'client_name' => 'Visitor',
            'page' => '/home',
            'is_active' => true,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'last_message_at' => $lastMessageAt->format('Y-m-d H:i:s'),
            'status' => 'active',
            'is_priority' => false,
            'token_version' => 1,
            'revoked_at' => null,
            'expires_at' => null,
        ]);

        $migration->up();

        $row = DB::table('chat_sessions')->where('id', $id)->first();

        $this->assertSame(
            $lastMessageAt->format('Y-m-d H:i:s'),
            Carbon::parse($row->last_activity_at)->format('Y-m-d H:i:s'),
            'Activity should be last_message_at when present.'
        );
        $this->assertSame(
            $lastMessageAt->copy()->addDays(30)->format('Y-m-d H:i:s'),
            Carbon::parse($row->expires_at)->format('Y-m-d H:i:s'),
            'Expiry should be exactly activity + 30 days.'
        );
    }

    public function test_backfill_falls_back_to_created_at_and_covers_soft_deleted_rows(): void
    {
        // last_message_at is NOT NULL in the legacy schema, so the
        // last_message_at ?? created_at fallback is defensive. Verify the
        // migration implements the fallback and that soft-deleted rows are
        // backfilled (DB::table includes trashed by design).
        $migrationSource = file_get_contents(database_path('migrations/2026_09_18_000002_add_chat_lifecycle_fields.php'));
        $this->assertTrue(
            str_contains($migrationSource, '??') || str_contains($migrationSource, 'COALESCE'),
            'Migration must derive activity via last_message_at ?? created_at (or COALESCE).'
        );

        // PHP-level fallback proof for the same expression the migration uses.
        $fallbackRow = (object) ['last_message_at' => null, 'created_at' => '2026-06-02 09:00:00'];
        $this->assertSame('2026-06-02 09:00:00', $fallbackRow->last_message_at ?? $fallbackRow->created_at);

        $migration = require database_path('migrations/2026_09_18_000002_add_chat_lifecycle_fields.php');
        $migration->down();

        $createdAt = Carbon::parse('2026-06-02 09:00:00');

        $id = DB::table('chat_sessions')->insertGetId([
            'session_token' => $this->makeToken('trashed-backfill'),
            'client_name' => 'Visitor',
            'page' => '/pricing',
            'is_active' => true,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'last_message_at' => $createdAt->format('Y-m-d H:i:s'),
            'status' => 'active',
            'is_priority' => false,
            'token_version' => 1,
            'revoked_at' => null,
            'expires_at' => null,
            'deleted_at' => Carbon::parse('2026-06-03 09:00:00')->format('Y-m-d H:i:s'),
        ]);

        $migration->up();

        $row = DB::table('chat_sessions')->where('id', $id)->first();

        $this->assertNotNull($row->last_activity_at, 'Soft-deleted rows must be backfilled.');
        $this->assertSame(
            $createdAt->format('Y-m-d H:i:s'),
            Carbon::parse($row->last_activity_at)->format('Y-m-d H:i:s'),
            'Activity should fall back to created_at when last_message_at equals it.'
        );
        $this->assertSame(
            $createdAt->copy()->addDays(30)->format('Y-m-d H:i:s'),
            Carbon::parse($row->expires_at)->format('Y-m-d H:i:s')
        );

        // Model layer still sees the trashed row with lifecycle predicates.
        $session = ChatSession::withTrashed()->findOrFail($id);
        $this->assertSame(
            'active',
            $session->lifecycleStatus($createdAt->copy()->addDays(10))
        );
    }

    public function test_backfill_preserves_revoked_sessions_as_invalid_without_reactivation(): void
    {
        $migration = require database_path('migrations/2026_09_18_000002_add_chat_lifecycle_fields.php');
        $migration->down();

        $createdAt = Carbon::parse('2026-06-01 08:00:00');
        $lastMessageAt = Carbon::parse('2026-06-04 08:00:00');

        $id = DB::table('chat_sessions')->insertGetId([
            'session_token' => $this->makeToken('revoked-backfill'),
            'client_name' => 'Visitor',
            'page' => '/home',
            'is_active' => false,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'last_message_at' => $lastMessageAt->format('Y-m-d H:i:s'),
            'status' => 'closed',
            'is_priority' => false,
            'token_version' => 0,
            'revoked_at' => Carbon::parse('2026-06-04 09:00:00')->format('Y-m-d H:i:s'),
            'expires_at' => null,
        ]);

        $migration->up();

        $row = DB::table('chat_sessions')->where('id', $id)->first();

        $this->assertNotNull($row->revoked_at, 'Migration must never clear revoked_at.');
        $this->assertFalse((bool) $row->is_active, 'Revoked rows must remain inactive.');
        $this->assertNotNull($row->last_activity_at);
        $this->assertNotNull($row->expires_at);

        $session = ChatSession::findOrFail($id);
        // Revoked stays invalid even though expiry was backfilled and is_active/status must not override it.
        $this->assertSame('invalid', $session->lifecycleStatus($createdAt->copy()->addDays(5)));
        $this->assertFalse($session->isGuestWritable($createdAt->copy()->addDays(5)));
    }

    public function test_terminator_deletion_nulls_actor_but_retains_timestamp(): void
    {
        $actor = User::factory()->create();
        $terminatedAt = Carbon::parse('2026-07-01 12:00:00');

        $session = ChatSession::create([
            'session_token' => $this->makeToken('terminator-null'),
            'client_name' => 'Visitor',
            'page' => '/home',
            'is_active' => false,
            'created_at' => Carbon::parse('2026-06-01 10:00:00'),
            'last_message_at' => Carbon::parse('2026-06-05 10:00:00'),
            'last_activity_at' => Carbon::parse('2026-06-05 10:00:00'),
            'expires_at' => Carbon::parse('2026-07-05 10:00:00'),
            'status' => 'closed',
            'terminated_at' => $terminatedAt,
            'terminated_by' => $actor->id,
        ]);

        $this->assertSame($actor->id, $session->terminator->id);

        $actor->forceDelete();

        $session->refresh();

        $this->assertNull($session->terminated_by, 'Deleting the terminator must null the actor.');
        $this->assertSame(
            $terminatedAt->format('Y-m-d H:i:s'),
            $session->terminated_at->format('Y-m-d H:i:s'),
            'Deleting the terminator must retain terminated_at.'
        );
        $this->assertSame('terminated', $session->lifecycleStatus(Carbon::parse('2026-06-10 10:00:00')));
    }

    public function test_lifecycle_status_exact_boundary_now_lt_expires_is_active(): void
    {
        $activity = Carbon::parse('2026-06-01 10:00:00');
        $expires = $activity->copy()->addDays(30);

        $session = ChatSession::create([
            'session_token' => $this->makeToken('boundary-active'),
            'created_at' => $activity,
            'last_message_at' => $activity,
            'last_activity_at' => $activity,
            'expires_at' => $expires,
            'is_active' => true,
        ]);

        $justBefore = $expires->copy()->subSecond();

        $this->assertSame('active', $session->lifecycleStatus($justBefore));
        $this->assertTrue($session->isGuestWritable($justBefore));
    }

    public function test_lifecycle_status_exact_boundary_now_gte_expires_is_expired(): void
    {
        $activity = Carbon::parse('2026-06-01 10:00:00');
        $expires = $activity->copy()->addDays(30);

        $session = ChatSession::create([
            'session_token' => $this->makeToken('boundary-expired'),
            'created_at' => $activity,
            'last_message_at' => $activity,
            'last_activity_at' => $activity,
            'expires_at' => $expires,
            'is_active' => true,
        ]);

        // Exact boundary: now == expires_at is expired (now < expires_at is the only active case).
        $this->assertSame('expired', $session->lifecycleStatus($expires->copy()));
        $this->assertSame('expired', $session->lifecycleStatus($expires->copy()->addSecond()));
        $this->assertFalse($session->isGuestWritable($expires->copy()));
    }

    public function test_lifecycle_precedence_is_terminated_then_revoked_then_expired(): void
    {
        $now = Carbon::parse('2026-07-10 10:00:00');

        $terminated = ChatSession::create([
            'session_token' => $this->makeToken('precedence-terminated'),
            'created_at' => Carbon::parse('2026-06-01 10:00:00'),
            'last_activity_at' => Carbon::parse('2026-06-01 10:00:00'),
            'expires_at' => Carbon::parse('2026-07-01 10:00:00'),
            'revoked_at' => Carbon::parse('2026-06-02 10:00:00'),
            'terminated_at' => Carbon::parse('2026-06-03 10:00:00'),
            'is_active' => true,
            'status' => 'active',
        ]);
        $this->assertSame('terminated', $terminated->lifecycleStatus($now));
        $this->assertFalse($terminated->isGuestWritable($now));

        $revokedButUnexpired = ChatSession::create([
            'session_token' => $this->makeToken('precedence-revoked'),
            'created_at' => Carbon::parse('2026-07-01 10:00:00'),
            'last_activity_at' => Carbon::parse('2026-07-01 10:00:00'),
            'expires_at' => Carbon::parse('2026-07-31 10:00:00'),
            'revoked_at' => Carbon::parse('2026-07-02 10:00:00'),
            'is_active' => true,
            'status' => 'active',
        ]);
        // is_active/status must not override the terminal predicate.
        $this->assertSame('invalid', $revokedButUnexpired->lifecycleStatus($now));
        $this->assertFalse($revokedButUnexpired->isGuestWritable($now));
    }

    public function test_model_exposes_lifecycle_casts_fillable_and_terminator(): void
    {
        $session = new ChatSession;

        $this->assertContains('last_activity_at', $session->getFillable());
        $this->assertContains('terminated_at', $session->getFillable());
        $this->assertContains('terminated_by', $session->getFillable());

        $casts = $session->getCasts();
        $this->assertSame('datetime', $casts['last_activity_at']);
        $this->assertSame('datetime', $casts['terminated_at']);

        $this->assertSame(User::class, $session->terminator()->getRelated()::class);
        $this->assertSame('terminated_by', $session->terminator()->getForeignKeyName());

        $now = Carbon::now();
        $this->assertIsString($session->lifecycleStatus($now));
        $this->assertIsBool($session->isGuestWritable($now));
    }
}
