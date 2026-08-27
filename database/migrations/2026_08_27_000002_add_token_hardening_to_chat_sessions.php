<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('token_version')->default(1)->after('session_token');
            $table->timestamp('revoked_at')->nullable()->after('token_version');
            $table->timestamp('expires_at')->nullable()->after('revoked_at');
        });

        // Invalidate all legacy sess_ / short tokens immediately (SEC-06)
        // Do not log raw tokens; just count.
        $legacyCount = DB::table('chat_sessions')
            ->where('session_token', 'like', 'sess_%')
            ->orWhereRaw('LENGTH(session_token) < 32')
            ->count();

        if ($legacyCount > 0) {
            DB::table('chat_sessions')
                ->where('session_token', 'like', 'sess_%')
                ->orWhereRaw('LENGTH(session_token) < 32')
                ->update([
                    'revoked_at' => now(),
                    'is_active' => false,
                    'token_version' => 0,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['token_version', 'revoked_at', 'expires_at']);
        });
    }
};
