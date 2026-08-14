@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="/static/subpages.css">
<link rel="stylesheet" href="/static/blog.css">
@endpush

@section('title', $post->title . ' | TDT Powersteel Blog')
@section('description', $post->excerpt)

@section('content')

    <section class="post-hero">
        <div class="blog-media">
            @if ($post->cover_image)
            <img src="{{ asset('storage/' . $post->cover_image) }}" alt="{{ $post->title }}" style="width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;">
            @endif
        </div>
        <div class="post-hero-plate">
            <h1>{{ $post->title }}</h1>
        </div>
    </section>

    <section class="page-section post-article">
        <div class="container">
            <div class="post-article-inner mx-auto">

                <a href="{{ route('blog') }}" class="back-to-blog-btn">
                    <i class="fa-solid fa-arrow-left"></i> Back to Blog
                </a>

                <div class="post-meta">
                    <span class="post-category">{{ $post->tag }}</span>
                    <span class="post-date">{{ \Carbon\Carbon::parse($post->published_date)->format('F j, Y') }}</span>
                </div>
                <article class="post-body">
                    {!! $post->body !!}
                </article>

                <div class="post-cta-row">
                    <a href="{{ route('contact') }}" class="blog-link-plate">Get a Free Consultation <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    </section>

@endsection
