@extends('layouts.app')

@push('styles')
<<<<<<< HEAD
<link rel="stylesheet" href="/static/products.css">
=======
<link rel="stylesheet" href="/static/subpages.css">
>>>>>>> 37fa31520cb1f53b400f2af28defd377eee43a9b
@endpush

@section('title', 'Steel Products | TDT Powersteel Corporation')
@section('description', 'Browse our full range of steel products: PNS-certified deformed bars, structural beams, plates, pipes, wire mesh, and hardware. Nationwide delivery in the Philippines.')

@section('content')

    <section class="page-banner">
        <div class="container">
            <div class="breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span class="sep">/</span>
                <span class="current">Products</span>
            </div>
            <h1 class="page-banner-title">PREMIUM QUALITY <br><span class="highlight">STEEL PRODUCTS</span></h1>
            <p class="page-banner-desc">
                Engineer your vision with unshakeable foundations. Our deformed bars and structural steel components are tested for maximum durability in heavy-duty construction.
            </p>
        </div>
    </section>

    <section class="category-section">
        <div class="container">
            <div class="category-grid">
                @foreach ($categories as $category)
                <a href="{{ route('category_detail', $category->slug) }}" class="category-card reveal">
                    <div class="category-img-wrap">
                        @if ($category->image)
                        <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        @endif
                        <div class="fallback-placeholder"@if ($category->image) style="display:none;"@endif>{{ strtoupper($category->name) }}</div>
                    </div>
                    <div class="category-body">
                        <span class="category-name">{{ $category->name }}</span>
                        <span class="category-arrow"><i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </section>

@endsection
