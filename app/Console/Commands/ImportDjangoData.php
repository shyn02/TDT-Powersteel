<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QuoteRequest;
use App\Models\SiteSettings;
use App\Models\SocialHighlight;
use App\Models\SystemSettings;
use Illuminate\Console\Command;
use PDO;

class ImportDjangoData extends Command
{
    protected $signature = 'import:django-data {path : Full path to the Django db.sqlite3 file}';

    protected $description = 'One-time import of real content (categories, products, blog posts, social highlights, quote requests, settings) from the old Django db.sqlite3 into the new Laravel tables.';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->importCategories($pdo);
        $this->importProducts($pdo);
        $this->importBlogPosts($pdo);
        $this->importSocialHighlights($pdo);
        $this->importQuoteRequests($pdo);
        $this->importSettings($pdo);

        $this->info('Done. Note: image files themselves were NOT copied — only the');
        $this->info('filenames/paths in the database. See README_IMPORT.md for the');
        $this->info('separate step of copying the actual media files.');

        return self::SUCCESS;
    }

    private function importCategories(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT * FROM core_productcategory')->fetchAll();

        foreach ($rows as $row) {
            ProductCategory::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'image' => $row['image'] ?: null,
                    'banner_image' => $row['banner_image'] ?: null,
                    'banner_desc' => $row['banner_desc'] ?: null,
                    'intro_desc' => $row['intro_desc'] ?: null,
                    'is_active' => (bool) $row['is_active'],
                ]
            );
        }

        $this->info(count($rows) . ' product categories imported.');
    }

    private function importProducts(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT * FROM core_product')->fetchAll();
        $catMap = $this->buildCategorySlugToIdMap($pdo);

        $count = 0;
        foreach ($rows as $row) {
            $djangoCatId = $row['category_id'];
            $slug = $catMap['by_django_id'][$djangoCatId] ?? null;
            $newCategory = $slug ? ProductCategory::where('slug', $slug)->first() : null;

            if (! $newCategory) {
                continue; // orphaned product, skip
            }

            Product::updateOrCreate(
                ['category_id' => $newCategory->id, 'name' => $row['name']],
                [
                    'specs' => $row['specs'] ?: null,
                    'sizes' => $row['sizes'] ?: null,
                    'description' => $row['description'] ?: null,
                    'image' => $row['image'] ?: null,
                    'is_active' => (bool) $row['is_active'],
                ]
            );
            $count++;
        }

        $this->info($count . ' products imported.');
    }

    /** Maps Django's numeric category_id -> slug, so we can re-look-up the new Laravel id. */
    private function buildCategorySlugToIdMap(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT id, slug FROM core_productcategory')->fetchAll();
        $byDjangoId = [];
        foreach ($rows as $row) {
            $byDjangoId[$row['id']] = $row['slug'];
        }

        return ['by_django_id' => $byDjangoId];
    }

    private function importBlogPosts(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT * FROM core_blogpost')->fetchAll();

        foreach ($rows as $row) {
            BlogPost::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'title' => $row['title'],
                    'tag' => $row['tag'],
                    'excerpt' => $row['excerpt'],
                    'cover_image' => $row['cover_image'] ?: null,
                    'body' => $row['body'],
                    'is_featured' => (bool) $row['is_featured'],
                    'is_active' => (bool) $row['is_active'],
                    'published_date' => $row['published_date'],
                ]
            );
        }

        $this->info(count($rows) . ' blog posts imported.');
    }

    private function importSocialHighlights(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT * FROM core_socialhighlight')->fetchAll();

        foreach ($rows as $row) {
            SocialHighlight::updateOrCreate(
                ['title' => $row['title'], 'link_url' => $row['link_url']],
                [
                    'platform' => $row['platform'],
                    'tag_label' => $row['tag_label'],
                    'badge_label' => $row['badge_label'],
                    'description' => $row['description'],
                    'embed_permalink' => $row['embed_permalink'] ?: null,
                    'handle' => $row['handle'] ?: null,
                    'video_file' => $row['video_file'] ?: null,
                    'is_active' => (bool) $row['is_active'],
                    'order' => $row['order'],
                ]
            );
        }

        $this->info(count($rows) . ' social highlights imported.');
    }

    private function importQuoteRequests(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT * FROM core_quoterequest')->fetchAll();
        $catMap = $this->buildCategorySlugToIdMap($pdo);

        $count = 0;
        foreach ($rows as $row) {
            $slug = $catMap['by_django_id'][$row['category_id']] ?? null;
            $newCategory = $slug ? ProductCategory::where('slug', $slug)->first() : null;

            QuoteRequest::create([
                'category_id' => $newCategory?->id,
                'product_id' => null, // product_id remap skipped — low value for historical requests
                'full_name' => $row['full_name'],
                'company_name' => $row['company_name'] ?: null,
                'email' => $row['email'] ?: null,
                'phone' => $row['phone'] ?: null,
                'address' => $row['address'] ?: null,
                'how_heard' => $row['how_heard'] ?: null,
                'estimated_qty' => $row['estimated_qty'],
                'status' => $row['status'],
                'is_seen' => (bool) $row['is_seen'],
                'source' => $row['source'],
                'created_at' => $row['created_at'],
            ]);
            $count++;
        }

        $this->info($count . ' quote requests imported.');
    }

    private function importSettings(PDO $pdo): void
    {
        $site = $pdo->query('SELECT * FROM core_sitesettings LIMIT 1')->fetch();
        if ($site) {
            $settings = SiteSettings::current();
            $settings->fill([
                'site_name' => $site['site_name'],
                'support_email' => $site['support_email'] ?: null,
                'support_phone' => $site['support_phone'] ?: null,
                'company_address' => $site['company_address'] ?: null,
                'session_timeout_minutes' => $site['session_timeout_minutes'],
                'max_login_attempts' => $site['max_login_attempts'],
                'lockout_minutes' => $site['lockout_minutes'],
                'require_strong_passwords' => (bool) $site['require_strong_passwords'],
                'notify_new_quote' => (bool) $site['notify_new_quote'],
                'notify_new_referral' => (bool) $site['notify_new_referral'],
                'notify_new_chat' => (bool) $site['notify_new_chat'],
                'notification_email' => $site['notification_email'] ?: null,
                'timezone_name' => $site['timezone_name'],
                'currency' => $site['currency'],
                'date_format' => $site['date_format'],
                'maintenance_mode' => (bool) $site['maintenance_mode'],
                'maintenance_message' => $site['maintenance_message'],
            ])->save();
            $this->info('Site settings imported.');
        }

        $system = $pdo->query('SELECT * FROM core_systemsettings LIMIT 1')->fetch();
        if ($system) {
            $settings = SystemSettings::current();
            $settings->fill([
                'max_active_chats_per_rep' => $system['max_active_chats_per_rep'],
            ])->save();
            $this->info('System settings imported.');
        }
    }
}
