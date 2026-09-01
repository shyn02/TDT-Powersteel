<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\{
    User,
    UserProfile,
    ProductCategory,
    Product,
    QuoteRequest,
    ContactMessage,
    BlogPost,
    SocialHighlight,
    Referral,
    Project,
    ChatSession,
    ChatMessage,
    ActivityLog
};

class PruneArchivedData extends Command
{
    protected $signature = 'prune:archived {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Permanently delete records archived (soft-deleted) > 30 days ago';

    public function handle(): int
    {
        $cutoff = now()->subDays(30);
        $dryRun = $this->option('dry-run');

        $models = [
            'Users' => User::class,
            'UserProfiles' => UserProfile::class,
            'ProductCategories' => ProductCategory::class,
            'Products' => Product::class,
            'QuoteRequests' => QuoteRequest::class,
            'ContactMessages' => ContactMessage::class,
            'BlogPosts' => BlogPost::class,
            'SocialHighlights' => SocialHighlight::class,
            'Referrals' => Referral::class,
            'Projects' => Project::class,
            'ChatSessions' => ChatSession::class,
            'ChatMessages' => ChatMessage::class,
            'ActivityLogs' => ActivityLog::class,
        ];

        $total = 0;
        foreach ($models as $label => $model) {
            $query = $model::onlyTrashed()->where('deleted_at', '<', $cutoff);
            $count = $query->count();
            if ($count > 0) {
                if ($dryRun) {
                    $this->info("[DRY-RUN] $label: $count would be permanently deleted (older than {$cutoff->toDateString()})");
                } else {
                    $query->forceDelete();
                    $this->info("$label: $count permanently deleted");
                    Log::info("PruneArchivedData: $label $count records force-deleted (archived >30 days)");
                    $total += $count;
                }
            }
        }

        if ($total === 0 && !$dryRun) {
            $this->info('No archived records older than 30 days to prune.');
        } elseif (!$dryRun) {
            $this->info("Done — $total total records permanently deleted.");
        }

        return self::SUCCESS;
    }
}
