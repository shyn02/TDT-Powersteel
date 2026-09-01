<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soft-delete (archived) support — deleted records stay in DB
     * for 30 days with deleted_at set, then are pruned by the
     * prune:archived command (daily at 02:00). See App\Console\Commands\PruneArchivedData.
     */
    public function up(): void
    {
        $tables = [
            'users',
            'user_profiles',
            'product_categories',
            'products',
            'quote_requests',
            'contact_messages',
            'blog_posts',
            'social_highlights',
            'referrals',
            'projects',
            'chat_sessions',
            'chat_messages',
            'activity_logs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'user_profiles',
            'product_categories',
            'products',
            'quote_requests',
            'contact_messages',
            'blog_posts',
            'social_highlights',
            'referrals',
            'projects',
            'chat_sessions',
            'chat_messages',
            'activity_logs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }
};
