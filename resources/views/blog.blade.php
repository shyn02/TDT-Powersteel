@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="/static/subpages.css">
<link rel="stylesheet" href="/static/blog.css">
@endpush

@push('scripts')
{{-- Required for the "Social Highlights" section below: without these SDK scripts,
     the fb-post/fb-video and instagram-media embed markup just sits as empty boxes —
     these scripts are what actually turn them into visible embeds. Highlights that
     have an uploaded Video file instead of a live permalink don't need these, since
     they render as a plain <video> tag. --}}
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v19.0"></script>
<script async src="//www.instagram.com/embed.js"></script>
@endpush

@section('title', 'Blog | TDT Powersteel Corporation')
@section('description', 'News, guides, and updates from TDT Powersteel Corporation — steel supply insights, logistics, sustainability, and company announcements.')

@section('content')

    <section class="page-banner blog-banner-video">
        <div class="blog-banner-video-wrap">
            <video class="blog-banner-video-el" autoplay muted loop playsinline preload="none" loading="lazy" poster="/static/images/blog-banner-poster.jpg">
                <source src="/static/videos/tdt-powersteel-blog-banner.mp4" type="video/mp4">
            </video>
            <div class="blog-banner-overlay"></div>
        </div>
        <div class="container">
            <span class="section-subtitle">NEWS & INSIGHTS</span>
            <h1 class="page-banner-title">FROM THE TDT BLOG</h1>
            <div class="breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <span class="current">Blog</span>
            </div>
        </div>
    </section>

    <section class="page-section blog-main-section">
        <div class="container">

            <div class="blog-eyebrow-row">
                <h2>Latest Articles</h2>
                <span class="blog-count">{{ $postCount }} post{{ $postCount === 1 ? '' : 's' }}</span>
            </div>

            @if ($featured)
            <article class="blog-featured">
                <div class="blog-media">
                    <span class="blog-tag">{{ $featured->tag }}</span>
                    @if ($featured->cover_image)
                    <img src="{{ asset('storage/' . $featured->cover_image) }}" alt="{{ $featured->title }}" style="width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;">
                    @endif
                </div>
                <div class="blog-featured-body">
                    <span class="blog-date">{{ \Carbon\Carbon::parse($featured->published_date)->format('F j, Y') }}</span>
                    <h2>{{ $featured->title }}</h2>
                    <p>{{ $featured->excerpt }}</p>
                    <a href="{{ route('blog_detail', $featured->slug) }}" class="blog-link-plate">Read Article <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </article>
            @endif

            <div class="blog-grid">
                @forelse ($posts as $post)
                <article class="blog-card">
                    <div class="blog-media">
                        <span class="blog-tag">{{ $post->tag }}</span>
                        @if ($post->cover_image)
                        <img src="{{ asset('storage/' . $post->cover_image) }}" alt="{{ $post->title }}" style="width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;">
                        @endif
                    </div>
                    <div class="blog-card-body">
                        <span class="blog-date">{{ \Carbon\Carbon::parse($post->published_date)->format('F j, Y') }}</span>
                        <h3>{{ $post->title }}</h3>
                        <p>{{ $post->excerpt }}</p>
                        <a href="{{ route('blog_detail', $post->slug) }}" class="blog-card-link">Read More <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </article>
                @empty
                @if (! $featured)
                <p class="blog-placeholder-note">No blog posts yet. Add your first one from the admin's Blog Posts section.</p>
                @endif
                @endforelse
            </div>

            <div class="blog-eyebrow-row social-section-header">
                <h2>Social Highlights</h2>
                <span class="blog-count">FB, IG, TikTok & YouTube Updates</span>
            </div>

            <div class="blog-grid social-grid">
                @foreach ($socialHighlights as $item)
                @if ($item->platform === 'instagram_embed')
                <article class="blog-card social-card">
                    <div class="blog-media social-embed-wrap{{ $item->video_file ? ' has-video' : '' }}">
                        <span class="blog-tag">{{ $item->tag_label }}</span>
                        @if ($item->video_file)
                        <video class="social-profile-video" autoplay muted loop playsinline preload="none" loading="lazy" poster="/static/images/tdt-instagram-highlight-poster.jpg">
                            <source src="{{ asset('storage/' . $item->video_file) }}" type="video/mp4">
                        </video>
                        @else
                        <blockquote class="instagram-media" data-instgrm-permalink="{{ $item->embed_permalink }}" data-instgrm-version="14"></blockquote>
                        @endif
                    </div>
                    <div class="blog-card-body">
                        <span class="blog-date">{{ $item->badge_label }}</span>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->description }}</p>
                        <a href="{{ $item->link_url }}" target="_blank" rel="noopener noreferrer" class="blog-card-link">View on Instagram <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </article>

                @elseif ($item->platform === 'facebook_embed')
                <article class="blog-card social-card">
                    <div class="blog-media social-embed-wrap{{ $item->video_file ? ' has-video' : '' }}">
                        <span class="blog-tag">{{ $item->tag_label }}</span>
                        @if ($item->video_file)
                        <video class="social-profile-video" autoplay muted loop playsinline preload="none" loading="lazy" poster="/static/images/tdt-facebook-highlight-poster.jpg">
                            <source src="{{ asset('storage/' . $item->video_file) }}" type="video/mp4">
                        </video>
                        @elseif (str_contains($item->embed_permalink ?? '', '/reel/') || str_contains($item->embed_permalink ?? '', '/videos/') || str_contains($item->embed_permalink ?? '', '/watch/'))
                        {{-- Facebook's fb-post plugin only understands regular post permalinks
                             (facebook.com/PAGE/posts/ID) — reel and video links need fb-video instead,
                             or the embed silently renders nothing. --}}
                        <div class="fb-video" data-href="{{ $item->embed_permalink }}" data-width="500" data-show-text="false"></div>
                        @else
                        <div class="fb-post" data-href="{{ $item->embed_permalink }}" data-width="500" data-show-text="false"></div>
                        @endif
                    </div>
                    <div class="blog-card-body">
                        <span class="blog-date">{{ $item->badge_label }}</span>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->description }}</p>
                        <a href="{{ $item->link_url }}" target="_blank" rel="noopener noreferrer" class="blog-card-link">Watch on Facebook <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </article>

                @elseif ($item->platform === 'instagram_profile')
                <article class="blog-card social-card">
                    <div class="blog-media social-embed-wrap social-profile-box">
                        <span class="blog-tag">{{ $item->tag_label }}</span>
                        <i class="fa-brands fa-instagram profile-bg-icon"></i>
                        <div class="profile-overlay-content">
                            <i class="fa-brands fa-instagram profile-main-icon"></i>
                            <h4>{{ $item->handle }}</h4>
                        </div>
                    </div>
                    <div class="blog-card-body">
                        <span class="blog-date">{{ $item->badge_label }}</span>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->description }}</p>
                        <a href="{{ $item->link_url }}" target="_blank" rel="noopener noreferrer" class="blog-card-link">Visit Profile <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </article>

                @elseif ($item->platform === 'tiktok_profile')
                <article class="blog-card social-card">
                    <div class="blog-media social-embed-wrap social-profile-box social-profile-box-tiktok{{ $item->video_file ? ' has-video' : '' }}">
                        <span class="blog-tag">{{ $item->tag_label }}</span>
                        @if ($item->video_file)
                        <video class="social-profile-video" autoplay muted loop playsinline preload="none" loading="lazy" poster="/static/images/tdt-tiktok-highlight-poster.jpg">
                            <source src="{{ asset('storage/' . $item->video_file) }}" type="video/mp4">
                        </video>
                        @else
                        <i class="fa-brands fa-tiktok profile-bg-icon"></i>
                        @endif
                        <div class="profile-overlay-content">
                            <i class="fa-brands fa-tiktok profile-main-icon"></i>
                            <h4>{{ $item->handle }}</h4>
                        </div>
                    </div>
                    <div class="blog-card-body">
                        <span class="blog-date">{{ $item->badge_label }}</span>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->description }}</p>
                        <a href="{{ $item->link_url }}" target="_blank" rel="noopener noreferrer" class="blog-card-link">Visit Profile <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </article>

                @elseif ($item->platform === 'youtube_profile')
                <article class="blog-card social-card">
                    <div class="blog-media social-embed-wrap social-profile-box social-profile-box-youtube{{ $item->video_file ? ' has-video' : '' }}">
                        <span class="blog-tag">{{ $item->tag_label }}</span>
                        @if ($item->video_file)
                        <video class="social-profile-video" autoplay muted loop playsinline preload="none" loading="lazy" poster="/static/images/tdt-youtube-highlight-poster.jpg">
                            <source src="{{ asset('storage/' . $item->video_file) }}" type="video/mp4">
                        </video>
                        @else
                        <i class="fa-brands fa-youtube profile-bg-icon"></i>
                        @endif
                        <div class="profile-overlay-content">
                            <i class="fa-brands fa-youtube profile-main-icon"></i>
                            <h4>{{ $item->handle }}</h4>
                        </div>
                    </div>
                    <div class="blog-card-body">
                        <span class="blog-date">{{ $item->badge_label }}</span>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->description }}</p>
                        <a href="{{ $item->link_url }}" target="_blank" rel="noopener noreferrer" class="blog-card-link">Visit Profile <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </article>
                @endif
                @endforeach
            </div>
        </div>
    </section>

    <section class="about-cta-banner">
        <div class="container">
            <h2>HAVE A TOPIC YOU'D LIKE US TO COVER?</h2>
            <p>Reach out and let us know — or get a quote for your next project.</p>
            <a href="{{ route('contact') }}" class="btn-cta-white">CONTACT US</a>
        </div>
    </section>

@endsection