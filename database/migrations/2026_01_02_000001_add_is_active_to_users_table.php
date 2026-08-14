<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Django's User model has is_staff/is_superuser/is_active/groups/permissions.
// This project doesn't replicate Django's full auth-permission system —
// panel access & "admin vs staff" is governed by UserProfile.position
// instead (see User::isAdminPosition()) — but we still need a way to
// suspend/deactivate an account, hence just this one extra column.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
