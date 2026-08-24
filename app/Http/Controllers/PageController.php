<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SocialHighlight;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        $calcExcludedSlugs = ['hardware', 'construction-materials'];

        $calcProducts = Product::with('category')
            ->where('is_active', true)
            ->whereHas('category', function ($q) use ($calcExcludedSlugs) {
                $q->where('is_active', true)->whereNotIn('slug', $calcExcludedSlugs);
            })
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($product) => $product->category->name);

        return view('home', compact('categories', 'calcProducts'));
    }

    public function products(): View
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('products', compact('categories'));
    }

    public function about(): View
    {
        return view('about');
    }

    public function trackOrder(): View
    {
        return view('track_order');
    }

    public function blog(): View
    {
        $activePosts = BlogPost::where('is_active', true)->orderByDesc('published_date')->get();
        $featured = $activePosts->firstWhere('is_featured', true) ?? $activePosts->first();
        $posts = $featured ? $activePosts->reject(fn ($p) => $p->id === $featured->id) : $activePosts;
        $socialHighlights = SocialHighlight::where('is_active', true)->orderBy('order')->get();

        return view('blog', [
            'featured' => $featured,
            'posts' => $posts,
            'postCount' => $activePosts->count(),
            'socialHighlights' => $socialHighlights,
        ]);
    }

    public function blogDetail(string $slug): View
    {
        $post = BlogPost::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('blog_detail', compact('post'));
    }

    public function contact(): View
    {
        return view('contact');
    }

    public function privacyPolicy(): View
    {
        return view('privacy-policy');
    }

    public function termsAndConditions(): View
    {
        return view('terms-and-conditions');
    }

    public function referral(): View
    {
        return view('referral');
    }

    public function categoryDetail(string $slug): View
    {
        $category = ProductCategory::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $products = Product::where('category_id', $category->id)->where('is_active', true)->orderBy('name')->get();

        return view('category_detail', compact('category', 'products'));
    }

    public function sitemap(): Response
    {
        $baseUrl = 'https://www.tdtpowersteel.com';
        $now = now()->toAtomString();

        $staticPages = collect([
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => '/products/', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => '/about/', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/blog/', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => '/contact/', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/privacy-policy/', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => '/terms-and-conditions/', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => '/referral/', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ]);

        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'loc' => "/products/{$c->slug}/",
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ]);

        $posts = BlogPost::where('is_active', true)
            ->orderByDesc('published_date')
            ->get()
            ->map(fn ($p) => [
                'loc' => "/blog/{$p->slug}/",
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'lastmod' => $p->updated_at->toAtomString(),
            ]);

        $all = $staticPages->merge($categories)->merge($posts);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($all as $page) {
            $lastmod = isset($page['lastmod']) ? $page['lastmod'] : $now;
            $xml .= '  <url>' . "\n";
            $xml .= "    <loc>{$baseUrl}{$page['loc']}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>{$page['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$page['priority']}</priority>\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}