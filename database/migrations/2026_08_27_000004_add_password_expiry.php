<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_expires_at')->nullable()->after('password');
            $table->boolean('must_change_password')->default(false)->after('password_expires_at');
        });
        Schema::table('password_reset_requests', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('requested_at');
        });
        // Existing temp passwords / reset requests expire in 24h
        \Illuminate\Support\Facades\DB::table('password_reset_requests')
            ->whereNull('expires_at')
            ->update(['expires_at' => \Illuminate\Support\Facades\DB::raw("datetime(requested_at, '+24 hours')")]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_expires_at', 'must_change_password']);
        });
        Schema::table('password_reset_requests', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
