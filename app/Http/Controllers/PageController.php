<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SocialHighlight;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('home');
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
}
