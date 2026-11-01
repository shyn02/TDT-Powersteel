<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BlogPost;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Referral;
use App\Models\SocialHighlight;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseMigrationBootstrapTest extends TestCase
{
    public function test_required_tables_are_available_after_migrations(): void
    {
        foreach ([
            'users',
            'product_categories',
            'quote_requests',
            'chat_sessions',
            'chat_messages',
            'projects',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected table [{$table}] to exist.");
        }
    }

    public function test_every_soft_delete_model_has_a_deleted_at_column(): void
    {
        foreach ([
            ActivityLog::class,
            BlogPost::class,
            ChatMessage::class,
            ChatSession::class,
            ContactMessage::class,
            Product::class,
            ProductCategory::class,
            Project::class,
            QuoteRequest::class,
            Referral::class,
            SocialHighlight::class,
            User::class,
            UserProfile::class,
        ] as $modelClass) {
            $model = new $modelClass;

            $this->assertContains(
                SoftDeletes::class,
                class_uses_recursive($model),
                "Expected [{$modelClass}] to use SoftDeletes."
            );
            $this->assertTrue(
                Schema::hasColumn($model->getTable(), 'deleted_at'),
                "Expected [{$model->getTable()}] to have a deleted_at column."
            );
        }
    }

    public function test_chat_messages_reference_chat_sessions_by_session_id(): void
    {
        $foreignKeys = DB::select('PRAGMA foreign_key_list(chat_messages)');

        $this->assertTrue(
            collect($foreignKeys)->contains(fn (object $foreignKey): bool =>
                $foreignKey->from === 'session_id'
                && $foreignKey->table === 'chat_sessions'
                && $foreignKey->to === 'id'
            ),
            'Expected chat_messages.session_id to reference chat_sessions.id.'
        );
    }
}
