<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('last_message_at');
            $table->timestamp('terminated_at')->nullable()->after('expires_at');
            $table->foreignId('terminated_by')->nullable()->after('terminated_at')->constrained('users')->nullOnDelete();
            $table->index('expires_at');
            $table->index('terminated_at');
        });

        // Backfill all non-terminated rows, including soft-deleted rows (DB query
        // includes trashed). Uses PHP Carbon per row so expiry math is identical
        // on SQLite and MySQL. Never touches revoked_at/is_active/status.
        DB::table('chat_sessions')
            ->whereNull('terminated_at')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $base = $row->last_activity_at ?? $row->last_message_at ?? $row->created_at;

                    if ($base === null) {
                        continue;
                    }

                    $activity = Carbon::parse($base);
                    $expires = $activity->copy()->addDays(30);

                    DB::table('chat_sessions')->where('id', $row->id)->update([
                        'last_activity_at' => $activity->format('Y-m-d H:i:s'),
                        'expires_at' => $expires->format('Y-m-d H:i:s'),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropForeign(['terminated_by']);
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['terminated_at']);
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['last_activity_at', 'terminated_at', 'terminated_by']);
        });
    }
};
