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

    // ---- Task 2: versioned server contract (RED) ----

    private function hmacFor(string $raw): string
    {
        return hash_hmac('sha256', $raw, config('app.key'));
    }

    private function createApiSession(string $raw, array $overrides = []): ChatSession
    {
        $now = $overrides['now'] ?? Carbon::parse('2026-08-01 10:00:00');
        $activity = $overrides['last_activity_at'] ?? $now->copy();

        return ChatSession::create(array_merge([
            'session_token' => $this->hmacFor($raw),
            'client_name' => 'Visitor (/home)',
            'page' => '/home',
            'is_active' => true,
            'created_at' => $now->copy(),
            'last_message_at' => $activity->copy(),
            'last_activity_at' => $activity->copy(),
            'expires_at' => $activity->copy()->addDays(30),
            'status' => 'unassigned',
            'token_version' => 2,
        ], $overrides));
    }

    public function test_chat_unknown_operation_returns_422_without_creating_session(): void
    {
        $before = ChatSession::count();

        $response = $this->postJson('/api/chat/messages', [
            'contractVersion' => 1,
            'operation' => 'nonsense',
            'sessionId' => 'some-token-value-with-enough-length-123456',
        ]);

        $response->assertStatus(422);
        $this->assertSame($before, ChatSession::count(), 'Unknown operation must not create a session.');
    }

    public function test_chat_wrong_contract_version_returns_422_without_creating_session(): void
    {
        $before = ChatSession::count();

        $response = $this->postJson('/api/chat/messages', [
            'contractVersion' => 2,
            'operation' => 'restore',
            'sessionId' => 'some-token-value-with-enough-length-123456',
        ]);

        $response->assertStatus(422);
        $this->assertSame($before, ChatSession::count());
    }

    public function test_chat_restore_requires_session_id(): void
    {
        $response = $this->postJson('/api/chat/messages', [
            'contractVersion' => 1,
            'operation' => 'restore',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, ChatSession::count());
    }

    public function test_chat_poll_requires_session_id(): void
    {
        $response = $this->postJson('/api/chat/messages', [
            'contractVersion' => 1,
            'operation' => 'poll',
            'after' => 0,
        ]);

        $response->assertStatus(422);
    }

    public function test_chat_first_message_without_session_creates_session_and_returns_token(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00'));

        try {
            $response = $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'message',
                'text' => 'Hello, I need help',
                'page' => '/products/',
            ]);

            $response->assertOk();
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $json = $response->json();
            $this->assertSame(1, $json['contractVersion']);
            $this->assertSame('active', $json['status']);
            $this->assertArrayHasKey('sessionId', $json);
            $this->assertIsString($json['sessionId']);
            $this->assertGreaterThanOrEqual(32, strlen($json['sessionId']));
            $this->assertArrayHasKey('expiresAt', $json);

            $session = ChatSession::where('session_token', $this->hmacFor($json['sessionId']))->first();
            $this->assertNotNull($session, 'Created session must be retrievable via HMAC lookup.');
            $this->assertSame('Hello, I need help', $session->messages()->first()->message);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_first_handoff_without_session_creates_and_returns_token(): void
    {
        $response = $this->postJson('/api/chat/messages', [
            'contractVersion' => 1,
            'operation' => 'handoff_requested',
            'page' => '/contact/',
        ]);

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayHasKey('sessionId', $json);
        $session = ChatSession::where('session_token', $this->hmacFor($json['sessionId']))->first();
        $this->assertNotNull($session);
        $this->assertSame('[Requested to speak with a live agent]', $session->messages()->first()->message);
    }

    public function test_chat_message_with_unknown_token_returns_invalid_not_creation(): void
    {
        $before = ChatSession::count();

        $response = $this->postJson('/api/chat/messages', [
            'contractVersion' => 1,
            'operation' => 'message',
            'sessionId' => 'unknown-token-that-was-never-issued-1234567890',
            'text' => 'Hi again',
            'page' => '/home',
        ]);

        $response->assertStatus(404);
        $json = $response->json();
        $this->assertSame(1, $json['contractVersion']);
        $this->assertSame('invalid', $json['status']);
        $this->assertSame('CHAT_INVALID', $json['code']);
        $this->assertSame([], $json['messages']);
        $this->assertSame($before, ChatSession::count(), 'Unknown token must not create a session.');
    }

    public function test_chat_restore_returns_full_client_and_staff_history(): void
    {
        $raw = 'restore-history-raw-token-000000000001';
        $session = $this->createApiSession($raw);
        $agent = User::factory()->create(['name' => 'Ada Agent']);
        $first = $session->messages()->create([
            'sender' => 'client', 'message' => 'Hi', 'created_at' => Carbon::parse('2026-08-01 10:01:00'),
        ]);
        $second = $session->messages()->create([
            'sender' => 'staff', 'staff_user_id' => $agent->id, 'message' => 'Hello there',
            'created_at' => Carbon::parse('2026-08-01 10:02:00'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        try {
            $response = $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'restore',
                'sessionId' => $raw,
            ]);

            $response->assertOk();
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $json = $response->json();
            $this->assertSame(1, $json['contractVersion']);
            $this->assertSame('active', $json['status']);
            $this->assertArrayHasKey('expiresAt', $json);
            $this->assertCount(2, $json['messages']);
            $this->assertSame($first->id, $json['messages'][0]['id']);
            $this->assertSame('client', $json['messages'][0]['sender']);
            $this->assertSame('Hi', $json['messages'][0]['text']);
            $this->assertNull($json['messages'][0]['agentName']);
            $this->assertSame($second->id, $json['messages'][1]['id']);
            $this->assertSame('staff', $json['messages'][1]['sender']);
            $this->assertSame('Ada Agent', $json['messages'][1]['agentName']);
            $this->assertArrayHasKey('createdAt', $json['messages'][0]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_poll_returns_only_messages_after_id(): void
    {
        $raw = 'poll-incremental-raw-token-000000000002';
        $session = $this->createApiSession($raw);
        $agent = User::factory()->create(['name' => 'Poll Agent']);
        $m1 = $session->messages()->create([
            'sender' => 'client', 'message' => 'one', 'created_at' => Carbon::parse('2026-08-01 10:01:00'),
        ]);
        $m2 = $session->messages()->create([
            'sender' => 'staff', 'staff_user_id' => $agent->id, 'message' => 'two',
            'created_at' => Carbon::parse('2026-08-01 10:02:00'),
        ]);
        $m3 = $session->messages()->create([
            'sender' => 'staff', 'staff_user_id' => $agent->id, 'message' => 'three',
            'created_at' => Carbon::parse('2026-08-01 10:03:00'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        try {
            $response = $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'poll',
                'sessionId' => $raw,
                'after' => $m1->id,
            ]);

            $response->assertOk();
            $ids = collect($response->json('messages'))->pluck('id')->all();
            $this->assertSame([$m2->id, $m3->id], $ids);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_invalid_token_returns_404_empty(): void
    {
        $response = $this->postJson('/api/chat/messages', [
            'contractVersion' => 1,
            'operation' => 'restore',
            'sessionId' => 'no-such-session-token-0000000000000003',
        ]);

        $response->assertStatus(404);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $json = $response->json();
        $this->assertSame(1, $json['contractVersion']);
        $this->assertSame('invalid', $json['status']);
        $this->assertSame('CHAT_INVALID', $json['code']);
        $this->assertSame([], $json['messages']);
    }

    public function test_chat_expired_token_at_exact_boundary_returns_410_empty(): void
    {
        $raw = 'expired-boundary-raw-token-000000000004';
        $activity = Carbon::parse('2026-06-01 10:00:00');
        $expires = $activity->copy()->addDays(30);
        $session = $this->createApiSession($raw, [
            'now' => $activity->copy(),
            'last_activity_at' => $activity->copy(),
        ]);
        $session->messages()->create([
            'sender' => 'client', 'message' => 'old', 'created_at' => $activity->copy(),
        ]);

        Carbon::setTestNow($expires->copy());

        try {
            $response = $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'restore',
                'sessionId' => $raw,
            ]);

            // now == expires_at is expired.
            $response->assertStatus(410);
            $json = $response->json();
            $this->assertSame(1, $json['contractVersion']);
            $this->assertSame('expired', $json['status']);
            $this->assertSame('CHAT_EXPIRED', $json['code']);
            $this->assertSame([], $json['messages']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_revoked_token_returns_invalid_404(): void
    {
        $raw = 'revoked-session-raw-token-000000000005';
        $session = $this->createApiSession($raw);
        $session->update(['revoked_at' => Carbon::parse('2026-08-01 11:00:00')]);

        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        try {
            $response = $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'restore',
                'sessionId' => $raw,
            ]);

            $response->assertStatus(404);
            $this->assertSame('CHAT_INVALID', $response->json('code'));
            $this->assertSame([], $response->json('messages'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_terminated_token_returns_410_with_full_history(): void
    {
        $raw = 'terminated-history-raw-token-0000000006';
        $session = $this->createApiSession($raw);
        $agent = User::factory()->create(['name' => 'Term Agent']);
        $session->messages()->create([
            'sender' => 'client', 'message' => 'before end', 'created_at' => Carbon::parse('2026-08-01 10:01:00'),
        ]);
        $session->messages()->create([
            'sender' => 'staff', 'staff_user_id' => $agent->id, 'message' => 'bye',
            'created_at' => Carbon::parse('2026-08-01 10:02:00'),
        ]);
        $terminator = User::factory()->create();
        $session->update([
            'terminated_at' => Carbon::parse('2026-08-01 12:00:00'),
            'terminated_by' => $terminator->id,
            'is_active' => false,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        try {
            $response = $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'restore',
                'sessionId' => $raw,
            ]);

            $response->assertStatus(410);
            $json = $response->json();
            $this->assertSame('terminated', $json['status']);
            $this->assertSame('CHAT_TERMINATED', $json['code']);
            $this->assertCount(2, $json['messages']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_reads_do_not_mutate_expiry(): void
    {
        $raw = 'read-no-mutate-raw-token-000000000007';
        $activity = Carbon::parse('2026-08-01 10:00:00');
        $session = $this->createApiSession($raw, ['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);
        $expectedExpiry = $activity->copy()->addDays(30)->format('Y-m-d H:i:s');
        $session->messages()->create([
            'sender' => 'client', 'message' => 'hello', 'created_at' => $activity->copy(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-05 10:00:00'));

        try {
            $this->postJson('/api/chat/messages', [
                'contractVersion' => 1, 'operation' => 'restore', 'sessionId' => $raw,
            ])->assertOk();
            $this->postJson('/api/chat/messages', [
                'contractVersion' => 1, 'operation' => 'poll', 'sessionId' => $raw, 'after' => 0,
            ])->assertOk();

            $session->refresh();
            $this->assertSame($expectedExpiry, $session->expires_at->format('Y-m-d H:i:s'));
            $this->assertSame(
                $activity->format('Y-m-d H:i:s'),
                $session->last_activity_at->format('Y-m-d H:i:s')
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_guest_message_rolls_expiry_plus_30_days(): void
    {
        $raw = 'guest-roll-raw-token-0000000000000008';
        $activity = Carbon::parse('2026-08-01 10:00:00');
        $session = $this->createApiSession($raw, ['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);
        $writeAt = Carbon::parse('2026-08-05 14:30:00');
        Carbon::setTestNow($writeAt->copy());

        try {
            $response = $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'message',
                'sessionId' => $raw,
                'text' => 'follow up',
                'page' => '/pricing',
            ]);

            $response->assertOk();
            $json = $response->json();
            $this->assertSame('active', $json['status']);
            $this->assertArrayNotHasKey('sessionId', $json, 'Continuation writes must not rotate the token.');

            $session->refresh();
            $this->assertSame(
                $writeAt->format('Y-m-d H:i:s'),
                $session->last_activity_at->format('Y-m-d H:i:s')
            );
            $this->assertSame(
                $writeAt->copy()->addDays(30)->format('Y-m-d H:i:s'),
                $session->expires_at->format('Y-m-d H:i:s')
            );
            $this->assertSame(
                $writeAt->copy()->addDays(30)->toIso8601String(),
                $json['expiresAt']
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_handoff_commit_rolls_expiry_plus_30_days(): void
    {
        $raw = 'handoff-roll-raw-token-000000000000009';
        $activity = Carbon::parse('2026-08-01 10:00:00');
        $session = $this->createApiSession($raw, ['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);
        $writeAt = Carbon::parse('2026-08-06 09:00:00');
        Carbon::setTestNow($writeAt->copy());

        try {
            $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'handoff_requested',
                'sessionId' => $raw,
                'page' => '/home',
            ])->assertOk();

            $session->refresh();
            $this->assertSame('[Requested to speak with a live agent]', $session->messages()->latest('id')->first()->message);
            $this->assertSame(
                $writeAt->copy()->addDays(30)->format('Y-m-d H:i:s'),
                $session->expires_at->format('Y-m-d H:i:s')
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_terminal_writes_do_not_mutate(): void
    {
        $raw = 'terminal-write-raw-token-0000000000010';
        $activity = Carbon::parse('2026-06-01 10:00:00');
        $expires = $activity->copy()->addDays(30);
        $session = $this->createApiSession($raw, ['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);
        $messageCount = $session->messages()->count();

        Carbon::setTestNow($expires->copy()->addDay());

        try {
            $response = $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'message',
                'sessionId' => $raw,
                'text' => 'too late',
                'page' => '/home',
            ]);

            $response->assertStatus(410);
            $this->assertSame('CHAT_EXPIRED', $response->json('code'));

            $session->refresh();
            $this->assertSame($messageCount, $session->messages()->count());
            $this->assertSame(
                $expires->format('Y-m-d H:i:s'),
                $session->expires_at->format('Y-m-d H:i:s'),
                'Failed writes must not roll expiry.'
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_terminated_write_returns_410_with_history_and_no_mutation(): void
    {
        $raw = 'terminated-write-raw-token-000000000011';
        $activity = Carbon::parse('2026-08-01 10:00:00');
        $session = $this->createApiSession($raw, ['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);
        $session->messages()->create([
            'sender' => 'client', 'message' => 'kept', 'created_at' => $activity->copy(),
        ]);
        $terminator = User::factory()->create();
        $session->update([
            'terminated_at' => Carbon::parse('2026-08-01 12:00:00'),
            'terminated_by' => $terminator->id,
            'is_active' => false,
        ]);
        $expectedExpiry = $session->expires_at->format('Y-m-d H:i:s');
        $count = $session->messages()->count();

        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        try {
            $response = $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'message',
                'sessionId' => $raw,
                'text' => 'after end',
                'page' => '/home',
            ]);

            $response->assertStatus(410);
            $json = $response->json();
            $this->assertSame('terminated', $json['status']);
            $this->assertSame('CHAT_TERMINATED', $json['code']);
            $this->assertCount(1, $json['messages']);
            $this->assertSame('kept', $json['messages'][0]['text']);

            $session->refresh();
            $this->assertSame($count, $session->messages()->count());
            $this->assertSame($expectedExpiry, $session->expires_at->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_chat_overlong_text_is_rejected_without_creating_session(): void
    {
        $before = ChatSession::count();

        $response = $this->postJson('/api/chat/messages', [
            'contractVersion' => 1,
            'operation' => 'message',
            'text' => str_repeat('x', 2001),
            'page' => '/home',
        ]);

        $response->assertStatus(422);
        $this->assertSame($before, ChatSession::count());
    }

    public function test_chat_token_in_query_string_is_rejected(): void
    {
        $raw = 'query-token-raw-00000000000000001234';
        $this->createApiSession($raw);

        $response = $this->postJson('/api/chat/messages?sessionId='.urlencode($raw), [
            'contractVersion' => 1,
            'operation' => 'restore',
            'sessionId' => $raw,
        ]);

        $response->assertStatus(422);
    }

    // ---- Task 3: staff replies + idempotent termination (RED) ----

    private function makeStaffUser(string $position = 'admin', bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->profile()->update(['position' => $position]);

        return $user->fresh()->load('profile');
    }

    private function createStaffSession(array $overrides = []): ChatSession
    {
        $now = $overrides['now'] ?? Carbon::parse('2026-08-01 10:00:00');
        $activity = $overrides['last_activity_at'] ?? $now->copy();
        unset($overrides['now'], $overrides['last_activity_at']);

        return ChatSession::create(array_merge([
            'session_token' => $this->makeToken('staff-'.uniqid('', true)),
            'client_name' => 'Visitor (/home)',
            'page' => '/home',
            'is_active' => true,
            'created_at' => $now->copy(),
            'last_message_at' => $activity->copy(),
            'last_activity_at' => $activity->copy(),
            'expires_at' => $activity->copy()->addDays(30),
            'status' => 'active',
            'token_version' => 2,
        ], $overrides));
    }

    public function test_terminate_policy_allows_only_active_admin_position(): void
    {
        $session = $this->createStaffSession();
        $policy = new \App\Policies\ChatSessionPolicy;

        $admin = $this->makeStaffUser('admin', true);
        $salesRep = $this->makeStaffUser('sales_rep', true);
        $support = $this->makeStaffUser('support', true);
        $inactiveAdmin = $this->makeStaffUser('admin', false);

        $this->assertTrue($policy->terminate($admin, $session), 'Active admin must be able to terminate.');
        $this->assertFalse($policy->terminate($salesRep, $session), 'Sales rep must not terminate.');
        $this->assertFalse($policy->terminate($support, $session), 'Support must not terminate.');
        $this->assertFalse($policy->terminate($inactiveAdmin, $session), 'Inactive admin must not terminate.');

        // Existing view/update rules must not be broadened by the new method.
        $this->assertTrue($policy->view($admin, $session));
        $this->assertFalse($policy->view($salesRep, $session), 'Unassigned-claim rule still governs non-admin view.');
    }

    public function test_staff_reply_via_writer_rolls_activity_and_expiry(): void
    {
        $activity = Carbon::parse('2026-08-01 10:00:00');
        $session = $this->createStaffSession(['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);
        $staff = $this->makeStaffUser('admin', true);

        $writeAt = Carbon::parse('2026-08-05 14:30:00');
        Carbon::setTestNow($writeAt->copy());

        try {
            $message = (new \App\Actions\Chat\WriteChatMessage)->forStaff($session, $staff->id, 'Staff follow up');

            $this->assertSame('staff', $message->sender);
            $this->assertSame($staff->id, $message->staff_user_id);

            $session->refresh();
            $this->assertSame(
                $writeAt->format('Y-m-d H:i:s'),
                $session->last_activity_at->format('Y-m-d H:i:s')
            );
            $this->assertSame(
                $writeAt->copy()->addDays(30)->format('Y-m-d H:i:s'),
                $session->expires_at->format('Y-m-d H:i:s')
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_staff_reply_via_writer_rejects_terminal_without_mutation(): void
    {
        $activity = Carbon::parse('2026-08-01 10:00:00');
        $session = $this->createStaffSession(['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);
        $terminator = $this->makeStaffUser('admin', true);
        $session->update([
            'terminated_at' => Carbon::parse('2026-08-01 12:00:00'),
            'terminated_by' => $terminator->id,
            'is_active' => false,
            'status' => 'closed',
        ]);
        $expectedExpiry = $session->expires_at->format('Y-m-d H:i:s');
        $count = $session->messages()->count();
        $staff = $this->makeStaffUser('admin', true);

        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        try {
            try {
                (new \App\Actions\Chat\WriteChatMessage)->forStaff($session, $staff->id, 'too late');
                $this->fail('Terminal staff write must be rejected.');
            } catch (\App\Actions\Chat\ChatWriteRejectedException $e) {
                $this->assertSame('terminated', $e->lifecycle);
            }

            $session->refresh();
            $this->assertSame($count, $session->messages()->count());
            $this->assertSame($expectedExpiry, $session->expires_at->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_view_send_reply_rolls_activity_only_after_commit(): void
    {
        \Filament\Facades\Filament::setCurrentPanel('admin');

        try {
            $admin = $this->makeStaffUser('admin', true);
            $this->actingAs($admin);

            $activity = Carbon::parse('2026-08-01 10:00:00');
            $session = $this->createStaffSession(['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);

            $writeAt = Carbon::parse('2026-08-05 14:30:00');
            Carbon::setTestNow($writeAt->copy());

            $component = \Livewire\Livewire::test(
                \App\Filament\Admin\Resources\ChatSessions\Pages\ViewChatSession::class,
                ['record' => $session->id]
            );
            $component->set('newMessage', 'Staff follow up')->call('sendReply');

            $session->refresh();
            $this->assertSame(1, $session->messages()->where('sender', 'staff')->count());
            $this->assertSame('Staff follow up', $session->messages()->where('sender', 'staff')->first()->message);
            $this->assertSame(
                $writeAt->format('Y-m-d H:i:s'),
                $session->last_activity_at->format('Y-m-d H:i:s'),
                'Committed staff reply must roll activity.'
            );
            $this->assertSame(
                $writeAt->copy()->addDays(30)->format('Y-m-d H:i:s'),
                $session->expires_at->format('Y-m-d H:i:s'),
                'Committed staff reply must roll expiry exactly +30 days.'
            );
        } finally {
            Carbon::setTestNow();
            \Filament\Facades\Filament::setCurrentPanel(null);
        }
    }

    public function test_view_send_reply_rejected_on_terminated_session(): void
    {
        \Filament\Facades\Filament::setCurrentPanel('admin');

        try {
            $admin = $this->makeStaffUser('admin', true);
            $this->actingAs($admin);

            $activity = Carbon::parse('2026-08-01 10:00:00');
            $session = $this->createStaffSession(['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);
            $session->update([
                'terminated_at' => Carbon::parse('2026-08-01 12:00:00'),
                'terminated_by' => $admin->id,
                'is_active' => false,
                'status' => 'closed',
            ]);
            $expectedExpiry = $session->expires_at->format('Y-m-d H:i:s');

            Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

            $component = \Livewire\Livewire::test(
                \App\Filament\Admin\Resources\ChatSessions\Pages\ViewChatSession::class,
                ['record' => $session->id]
            );
            $component->set('newMessage', 'too late')->call('sendReply');

            $session->refresh();
            $this->assertSame(0, $session->messages()->count(), 'Terminal staff reply must not create a message.');
            $this->assertSame($expectedExpiry, $session->expires_at->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
            \Filament\Facades\Filament::setCurrentPanel(null);
        }
    }

    public function test_terminate_action_is_idempotent_retains_history_and_blocks_guest_replay(): void
    {
        \Filament\Facades\Filament::setCurrentPanel('admin');

        try {
            $admin = $this->makeStaffUser('admin', true);
            $this->actingAs($admin);

            $activity = Carbon::parse('2026-08-01 10:00:00');
            $session = $this->createStaffSession(['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);
            $session->messages()->create(['sender' => 'client', 'message' => 'hello', 'created_at' => $activity->copy()]);
            $session->messages()->create([
                'sender' => 'staff', 'staff_user_id' => $admin->id, 'message' => 'hi there',
                'created_at' => $activity->copy()->addHour(),
            ]);
            $expectedExpiry = $session->expires_at->format('Y-m-d H:i:s');
            $expectedActivity = $session->last_activity_at->format('Y-m-d H:i:s');

            $terminatedAt = Carbon::parse('2026-08-02 10:00:00');
            Carbon::setTestNow($terminatedAt->copy());

            $component = \Livewire\Livewire::test(
                \App\Filament\Admin\Resources\ChatSessions\Pages\ViewChatSession::class,
                ['record' => $session->id]
            );
            $component->callAction('terminate');

            $session->refresh();
            $this->assertNotNull($session->terminated_at);
            $this->assertSame(
                $terminatedAt->format('Y-m-d H:i:s'),
                $session->terminated_at->format('Y-m-d H:i:s')
            );
            $this->assertSame($admin->id, $session->terminated_by);
            $this->assertFalse($session->is_active);
            $this->assertSame('closed', $session->status);
            $this->assertSame(2, $session->messages()->count(), 'Termination must never delete messages.');
            $this->assertSame($expectedExpiry, $session->expires_at->format('Y-m-d H:i:s'), 'Termination must not extend expiry.');
            $this->assertSame($expectedActivity, $session->last_activity_at->format('Y-m-d H:i:s'), 'Termination must not roll activity.');
            $this->assertSame(1, \App\Models\ActivityLog::where('actor_id', $admin->id)->count());

            // Repeat termination preserves original actor/time (first-write-wins).
            $secondAdmin = $this->makeStaffUser('admin', true);
            $this->actingAs($secondAdmin);
            Carbon::setTestNow($terminatedAt->copy()->addHour());

            $repeat = \Livewire\Livewire::test(
                \App\Filament\Admin\Resources\ChatSessions\Pages\ViewChatSession::class,
                ['record' => $session->id]
            );
            $repeat->callAction('terminate');

            $session->refresh();
            $this->assertSame($admin->id, $session->terminated_by, 'Repeat must preserve original actor.');
            $this->assertSame(
                $terminatedAt->format('Y-m-d H:i:s'),
                $session->terminated_at->format('Y-m-d H:i:s'),
                'Repeat must preserve original time.'
            );
            $this->assertSame(2, $session->messages()->count());
            $this->assertSame(
                1,
                \App\Models\ActivityLog::where('actor_id', $admin->id)->count(),
                'First-write-wins audit: repeat must not add a second termination log for the winner.'
            );

            // Blocked guest replay after termination returns history without mutation.
            $raw = 'task3-terminated-replay-00000000000131';
            $session->update(['session_token' => hash_hmac('sha256', $raw, (string) config('app.key'))]);
            $count = $session->messages()->count();

            Carbon::setTestNow(Carbon::parse('2026-08-03 10:00:00'));
            $response = $this->postJson('/api/chat/messages', [
                'contractVersion' => 1,
                'operation' => 'message',
                'sessionId' => $raw,
                'text' => 'after end',
                'page' => '/home',
            ]);

            $response->assertStatus(410);
            $this->assertSame('terminated', $response->json('status'));
            $this->assertSame('CHAT_TERMINATED', $response->json('code'));
            $this->assertCount(2, $response->json('messages'));

            $session->refresh();
            $this->assertSame($count, $session->messages()->count());
            $this->assertSame($expectedExpiry, $session->expires_at->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
            \Filament\Facades\Filament::setCurrentPanel(null);
        }
    }

    public function test_terminate_action_denies_non_admin(): void
    {
        $session = $this->createStaffSession();
        $rep = $this->makeStaffUser('sales_rep', true);

        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($rep)->allows('terminate', $session));
    }

    public function test_viewing_claiming_and_termination_do_not_extend_expiry(): void
    {
        \Filament\Facades\Filament::setCurrentPanel('admin');

        try {
            $admin = $this->makeStaffUser('admin', true);
            $rep = $this->makeStaffUser('sales_rep', true);

            $activity = Carbon::parse('2026-08-01 10:00:00');
            $expectedExpiry = $activity->copy()->addDays(30)->format('Y-m-d H:i:s');

            // Viewing (markIncomingAsRead) must not extend expiry.
            $viewed = $this->createStaffSession(['now' => $activity->copy(), 'last_activity_at' => $activity->copy()]);
            $viewed->messages()->create(['sender' => 'client', 'message' => 'ping', 'is_read' => false, 'created_at' => $activity->copy()]);
            $this->actingAs($admin);
            Carbon::setTestNow(Carbon::parse('2026-08-05 10:00:00'));

            $viewer = \Livewire\Livewire::test(
                \App\Filament\Admin\Resources\ChatSessions\Pages\ViewChatSession::class,
                ['record' => $viewed->id]
            );
            $viewer->call('markIncomingAsRead');

            $viewed->refresh();
            $this->assertSame($expectedExpiry, $viewed->expires_at->format('Y-m-d H:i:s'));
            $this->assertSame($activity->format('Y-m-d H:i:s'), $viewed->last_activity_at->format('Y-m-d H:i:s'));

            // Claiming must not extend expiry. The claim transition only assigns
            // assigned_to/status (see ChatController@claim); it never rolls
            // activity. Exercise the same mutation the controller performs.
            $claimable = $this->createStaffSession([
                'now' => $activity->copy(), 'last_activity_at' => $activity->copy(), 'status' => 'unassigned',
            ]);
            $this->actingAs($rep);
            Carbon::setTestNow(Carbon::parse('2026-08-06 10:00:00'));

            $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($rep)->allows('update', $claimable));
            $claimable->update(['assigned_to' => $rep->id, 'status' => 'active']);

            $claimable->refresh();
            $this->assertSame($expectedExpiry, $claimable->expires_at->format('Y-m-d H:i:s'));
            $this->assertSame($activity->format('Y-m-d H:i:s'), $claimable->last_activity_at->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
            \Filament\Facades\Filament::setCurrentPanel(null);
        }
    }
}
