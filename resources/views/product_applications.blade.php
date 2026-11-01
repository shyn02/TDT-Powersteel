@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="/static/subpages.css">
<link rel="stylesheet" href="/static/product-applications.css">
@endpush

@push('scripts')
<script defer src="/static/product-applications.js"></script>
@endpush

@section('title', 'Product Applications | TDT Powersteel Corporation')
@section('description', 'See how TDT Powersteel products are used in real construction and industrial applications across the Philippines.')

@section('content')

    <section class="page-banner">
        <div class="container">
            <div class="breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span class="sep">/</span>
                <span class="current">Product Applications</span>
            </div>
            <h1 class="page-banner-title">PRODUCT APPLICATIONS</h1>
            <p class="page-banner-desc">
                Explore the TDT Powersteel facility and see where each product is used. Pick a product on the map to learn how it is applied on site.
            </p>
        </div>
    </section>

    @if ($applications->isNotEmpty())
    <section class="product-applications-section" id="product-applications">
        <div class="container">
            <p class="product-applications-scroll-hint" aria-hidden="true">Swipe the map to explore it</p>

            <div class="product-applications-map-scroll">
                <div class="product-applications-map">
                    <img src="/static/images/facility-map.png"
                         alt="Illustrated map of the TDT Powersteel facility showing where each product is applied"
                         class="product-applications-art"
                         width="1920" height="1080" loading="lazy" decoding="async">

                    {{-- Leader lines sit behind the circles, so each line starts
                         at the circle's edge without any edge maths. --}}
                    @php($withAnchors = $applications->filter->hasAnchor())
                    @if ($withAnchors->isNotEmpty())
                    <svg class="product-applications-lines"
                         viewBox="0 0 100 100"
                         preserveAspectRatio="none"
                         aria-hidden="true"
                         focusable="false">
                        @foreach ($withAnchors as $application)
                        @php($anchor = $application->anchorPosition())
                        <line x1="{{ $application->position()->xPercent() }}"
                              y1="{{ $application->position()->yPercent() }}"
                              x2="{{ $anchor->xPercent() }}"
                              y2="{{ $anchor->yPercent() }}" />
                        @endforeach
                    </svg>
                    @endif

                    @foreach ($applications as $application)
                    <x-product-application.circle :application="$application" />
                    @endforeach
                </div>
            </div>

            <div class="product-applications-index">
                <h2 class="product-applications-index-title">Every application on the map</h2>
                <ul class="product-applications-list">
                    @foreach ($applications as $application)
                    <x-product-application.list-item :application="$application" />
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
    @endif

    <section class="product-applications-cta-section">
        <div class="container">
            <div class="product-applications-cta">
                <h2 class="product-applications-cta-title">Need these for your project?</h2>
                <p class="product-applications-cta-desc">Tell us what you need and our team will get back to you with a quote.</p>
                <button type="button" class="btn-orange btn-quote-trigger" data-product="Product Applications">
                    <span class="btn-text">REQUEST A QUOTE</span>
                </button>
            </div>
        </div>
    </section>

    @if ($applications->contains(fn ($application) => $application->isPlayable()))
    <div id="product-application-modal" class="video-modal" hidden>
        <div class="video-modal-backdrop" data-video-close></div>
        <div class="video-modal-dialog"
             role="dialog"
             aria-modal="true"
             aria-labelledby="product-application-modal-title">
            <div class="video-modal-header">
                <h2 class="video-modal-title" id="product-application-modal-title"></h2>
                <button type="button"
                        class="video-modal-close"
                        data-video-close
                        aria-label="Close video">&times;</button>
            </div>
            <video class="video-modal-video" controls playsinline preload="none" muted></video>
            <p class="video-modal-description" data-video-description hidden></p>
        </div>
    </div>
    @endif

@endsection
