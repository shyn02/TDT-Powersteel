@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="/static/subpages.css">
<link rel="stylesheet" href="/static/about.css">
@endpush

@section('title', 'About Us | TDT Powersteel Corporation')
@section('description', "Learn about TDT Powersteel Corporation — Manila's trusted steel supplier and distributor of PNS-certified steel bars, structural beams, plates, and pipes since 2015.")

@section('content')

    <section class="about-hero">
        <div class="about-hero-slideshow" aria-hidden="true">
            <div class="hero-slide" style="background-image: url('/static/images/steel1.jpg');"></div>
            <div class="hero-slide" style="background-image: url('/static/images/steel2.jpg');"></div>
            <div class="hero-slide" style="background-image: url('/static/images/steel3.jpg');"></div>
            <div class="hero-slide" style="background-image: url('/static/images/steel4.jpg');"></div>
            <div class="hero-slide" style="background-image: url('/static/images/steel5.jpg');"></div>
            <div class="hero-slide" style="background-image: url('/static/images/steel6.jpg');"></div>
            <div class="hero-slide" style="background-image: url('/static/images/steel7.jpg');"></div>
        </div>
        <div class="container about-hero-flex">
            <div class="hero-left reveal">
                <span class="subtitle"><span class="text-orange">ABOUT</span> US</span>
                <h1>BUILDING THE NATION WITH <span class="text-orange">STRENGTH AND TRUST.</span></h1>
                <p>TDT Powersteel Corporation is one of the most trusted steel suppliers in the Philippines, providing high-quality steel products and reliable solutions for construction and industrial projects nationwide.</p>
            </div>
            <div class="hero-right reveal">
                <div class="quote-box">
                    <span class="quote-mark">”</span>
                    <p>"With our growing family here at TDT Powersteel Corporation, leave it to TDT Powersteel as we provide the values and trust and we will continue to diversify to deliver everything you ask. If you think it, dream it, we will make it stay."</p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-stats-light">
        <div class="container stats-flex">
            <div class="stat-box reveal">
                <i class="fa-solid fa-industry text-orange"></i>
                <h3>1,000+</h3>
                <p>STEEL PRODUCTS</p>
            </div>
            <div class="stat-box reveal">
                <i class="far fa-clock text-orange"></i>
                <h3>20+</h3>
                <p>YEARS OF EXPERIENCE</p>
            </div>
            <div class="stat-box reveal">
                <i class="fas fa-users text-orange"></i>
                <h3>10,000+</h3>
                <p>SATISFIED CUSTOMERS</p>
            </div>
            <div class="stat-box reveal">
                <i class="fas fa-truck text-orange"></i>
                <h3>NATIONWIDE</h3>
                <p>DELIVERY COVERAGE</p>
            </div>
            <div class="stat-box reveal">
                <i class="fas fa-warehouse text-orange"></i>
                <h3>MULTIPLE</h3>
                <p>DISTRIBUTION HUBS</p>
            </div>
        </div>
    </section>

    <section class="about-overview">
        <div class="container overview-flex">
            <div class="overview-visual reveal">
                <img src="/static/images/teddy-tee.png" alt="Teddy B. Tee, Jr." class="overview-person">
                <div class="person-caption">
                    <h4>Teddy B. Tee, Jr.</h4>
                    <p>Chairperson and Chief Executive Officer</p>
                </div>
            </div>
            <div class="overview-content reveal">
                <span class="subtitle text-orange">ABOUT TDT POWERSTEEL</span>
                <h2>STEEL SUPPLIER <br>IN THE <span class="text-orange">PHILIPPINES</span></h2>
                <p>Established in 2015, TDT Powersteel Corporation began with a clear commitment to provide high-quality steel products, reliable construction solutions, and dependable service to customers across the Philippines.</p>
                <p>Through the years, we have expanded our product selection, strengthened our distribution network, and built lasting partnerships with contractors, developers, architects, engineers, fabricators, businesses, and individual customers. With more than 1,000 steel and construction products, we strive to meet the requirements of projects of all sizes.</p>
                <p>Today, TDT Powersteel Corporation continues to grow as a trusted partner in the Philippine steel supply and distribution industry. Guided by quality, reliability, integrity, and customer satisfaction, we remain committed to supporting development and contributing to nation-building—one strong structure at a time.</p>
            </div>
        </div>
    </section>

    <section class="vmc-section">
        <div class="container vmc-grid">
            <div class="vmc-card reveal">
                <i class="far fa-eye text-orange"></i>
                <h3>VISION</h3>
                <p>To be the most preferred steel supplier known for excellence, innovation, and service in the Philippines, driving sustainable construction nationwide.</p>
            </div>
            <div class="vmc-card reveal">
                <i class="fas fa-bullseye text-orange"></i>
                <h3>MISSION</h3>
                <p>To provide high-quality steel products and reliable solutions that help build a stronger and better nation, one structure at a time.</p>
            </div>
            <div class="vmc-card reveal">
                <i class="far fa-handshake text-orange"></i>
                <h3>COMMITMENT</h3>
                <p>We are committed to deliver quality products, excellent service and create value for our customers, partners and employees through ethical practices.</p>
            </div>
        </div>
    </section>

    <section class="built-on-trust">
        <div class="container built-on-trust-flex">
            <div class="built-content reveal">
                <span class="subtitle text-orange">OUR COMPANY</span>
                <h2>BUILT ON <span class="text-orange">TRUST.</span><br>DRIVEN BY<br><span class="text-orange">EXCELLENCE.</span></h2>
                <p>We take pride in our strong foundation, experienced team and continuous commitment to delivering the best steel solutions to our clients across the Philippines.</p>
            </div>
            <div class="built-video-panel reveal">
                <video autoplay muted loop playsinline poster="/static/images/truck-bg.jpg">
                    <source src="/static/videos/tdt-powersteel-introductory-video.mp4" type="video/mp4">
                </video>
            </div>
        </div>
    </section>

@endsection
