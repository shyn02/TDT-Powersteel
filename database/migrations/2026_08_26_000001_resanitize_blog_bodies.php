<?php

use App\Models\BlogPost;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Re-sanitize existing blog bodies with the hardened parser-based sanitizer (SEC-02).
        // This handles legacy rows that were saved with the old regex sanitizer.
        BlogPost::query()->chunkById(100, function ($posts) {
            foreach ($posts as $post) {
                $raw = $post->getAttributes()['body'] ?? null;
                $clean = HtmlSanitizer::clean($raw);
                if ($clean !== $raw) {
                    // Bypass mutator to avoid double-clean, write directly
                    \Illuminate\Support\Facades\DB::table('blog_posts')
                        ->where('id', $post->id)
                        ->update(['body' => $clean]);
                }
            }
        });
    }

    public function down(): void
    {
        // No rollback needed - sanitization is one-way.
    }
};
