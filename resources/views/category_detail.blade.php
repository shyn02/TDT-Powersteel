@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="/static/subcategory.css">
@endpush

@section('title', $category->name . ' | TDT Powersteel Corporation')
@section('description', $category->banner_desc ?: ($category->intro_desc ?: ($category->name . ' — premium steel products from TDT Powersteel Corporation.')))

@section('content')

    @php
        $bannerUrl = $category->banner_image
            ? asset('storage/' . $category->banner_image)
            : ($category->image ? asset('storage/' . $category->image) : null);
    @endphp

    <section class="page-banner subcat-page-banner" @if ($bannerUrl) style="--banner-img: url('{{ $bannerUrl }}');" @endif>
        <div class="container">
            <div class="breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span class="sep">/</span>
                <a href="{{ route('products') }}">Products</a>
                <span class="sep">/</span>
                <span class="current">{{ $category->name }}</span>
            </div>
            <h1 class="page-banner-title">{{ strtoupper($category->name) }}</h1>
            @if ($category->banner_desc)
            <p class="page-banner-desc">{{ $category->banner_desc }}</p>
            @endif
        </div>
    </section>

    <section class="category-intro">
        <div class="container section-header">
            <span class="section-subtitle">OUR {{ strtoupper($category->name) }}</span>
            <h2 class="section-title">{{ strtoupper($category->name) }} <span class="text-orange">PRODUCTS</span></h2>
            <div class="accent-line"></div>
            @if ($category->intro_desc)
            <p class="section-desc">{{ $category->intro_desc }}</p>
            @endif
        </div>
    </section>

    <section class="subcat-section">
        <div class="container">
            <a href="{{ route('products') }}" class="back-to-products"><span class="arrow">&larr;</span> Back to Products</a>

            <div class="subcat-grid">
                @forelse ($products as $product)
                <div class="subcat-card reveal">
                    <div class="subcat-img-wrap">
                        @if ($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        @endif
                        <div class="fallback-placeholder"@if ($product->image) style="display:none;"@endif>{{ strtoupper($product->name) }}</div>
                    </div>
                    <div class="subcat-body">
                        <h3 class="subcat-title">{{ $product->name }}</h3>
                        @if ($product->description)
                        <p class="subcat-desc">{{ $product->description }}</p>
                        @endif
                        @php $specs = $product->specsList(); @endphp
                        @if (count($specs))
                        <ul class="subcat-specs">
                            @foreach ($specs as $line)
                            <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                        @endif
                        <button class="btn-orange btn-quote-trigger" data-product="{{ $category->name }}">
                            <span class="btn-text">REQUEST A QUOTE</span>
                        </button>
                    </div>
                </div>
                @empty
                <p class="section-desc">No products listed in this category yet.</p>
                @endforelse
            </div>
        </div>
    </section>

@endsection