<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SocialHighlight;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $categories = Cache::remember('active_categories', 60, function () {
            return ProductCategory::where('is_active', true)->orderBy('name')->get();
        });

        // Same categories excluded from "CALCULATE WEIGHT" on the product listing
        // pages are excluded here too, so the homepage calculator's product list
        // always matches what's actually calculable on the products pages.
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
        $categories = Cache::remember('active_categories', 60, function () {
            return ProductCategory::where('is_active', true)->orderBy('name')->get();
        });

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
        $activePosts = Cache::remember('blog_active_posts', 60, function () {
            return BlogPost::where('is_active', true)->orderByDesc('published_date')->get();
        });

        $featured = $activePosts->firstWhere('is_featured', true) ?? $activePosts->first();
        $posts = $featured ? $activePosts->reject(fn ($p) => $p->id === $featured->id) : $activePosts;

        $socialHighlights = Cache::remember('blog_social_highlights', 60, function () {
            return SocialHighlight::where('is_active', true)->orderBy('order')->get();
        });

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
}