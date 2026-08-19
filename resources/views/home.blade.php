@extends('layouts.app')

@section('title', "Steel Supplier in Manila | TDT Powersteel Corporation")
@section('description', "Steel supplier in Sampaloc, Manila carrying PNS-certified steel bars, steel beams, steel plates, and steel pipes, with nationwide delivery. Request a free quote in minutes.")

@section('content')
    <section id="home" class="hero-section">
        <!-- Product slideshow background -->
        <div class="hero-slideshow" aria-hidden="true">
            <img src="{{ asset('static/images/steelbars.jpg') }}" alt="">
            <img src="{{ asset('static/images/steelbeams.jpg') }}" alt="">
            <img src="{{ asset('static/images/Steel Plates Banner.jpg') }}" alt="">
            <img src="{{ asset('static/images/Sheet Piles Banner.jpg') }}" alt="">
            <img src="{{ asset('static/images/Roofing Banner.png') }}" alt="">
            <img src="{{ asset('static/images/wiremesh banner.jpg') }}" alt="">
            <img src="{{ asset('static/images/ss-angle-bar.png') }}" alt="">
        </div>
        <!-- Decorative circles -->
        <div class="hero-circles" aria-hidden="true">
            <div class="hc hc-1"></div>
            <div class="hc hc-2"></div>
            <div class="hc hc-3"></div>
            <div class="hc hc-4"></div>
            <div class="hc hc-5"></div>
        </div>
        <div class="container hero-grid">
            <!-- KALIWANG CONTENT - Nilagyan ng slide-door-left para umandar ang animation -->
            <div class="hero-left slide-door-left">
                <span class="hero-badge">TRUSTED PARTNER IN CONSTRUCTION</span>
                <h1 class="hero-title">THE PHILIPPINES' NO. 1 TRUSTED <br><span class="highlight">STEEL SUPPLIER</span></h1>
                <p class="hero-desc">
                    TDT Powersteel Corporation is one of the most reliable and trusted brands in the Philippine steel supply and distribution industry. We deliver premium quality construction solutions with unmatched precision.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('referral') }}" class="btn-dark"><i class="fa-solid fa-people-arrows" style="margin-right:6px;"></i>REFER A PROJECT</a>
                    <a href="https://heyzine.com/flip-book/8dd4401888.html#page/1" target="_blank" rel="noopener noreferrer" class="btn-outline-dark"><i class="fa-solid fa-book-open" style="margin-right:6px;"></i>VIEW CATALOGUE</a>
                    <!-- Mobile-only trigger: opens the Request a Quote card as a popup. Hidden on desktop via CSS. -->
                    <button type="button" class="btn-dark hero-quote-mobile-btn" id="heroQuoteMobileBtn"><i class="fa-solid fa-file-invoice" style="margin-right:6px;"></i>REQUEST A QUOTE</button>
                </div>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <h3>20+ YEARS</h3>
                        <p>OF EXPERIENCE</p>
                    </div>
                    <div class="stat-item">
                        <h3>WIDE VARIETY</h3>
                        <p>OF STEEL PRODUCTS</p>
                    </div>
                    <div class="stat-item">
                        <h3>NATIONWIDE</h3>
                        <p>DISTRIBUTION</p>
                    </div>
                </div>
            </div>
            
            <!-- KANANG FORM CARD - Nilagyan ng slide-door-right para umandar ang animation -->
            <div class="hero-right slide-door-right" id="heroQuoteOverlay">
                <div class="quote-form-card">
                    <div class="card-header-form">
                        <h3>REQUEST A QUOTE</h3>
                        <span class="cat-year">TDT-2026</span>
                        <!-- Mobile-only close button, shown once the card becomes a popup -->
                        <button type="button" class="hero-quote-close" id="heroQuoteCloseBtn" aria-label="Close quote form">&times;</button>
                    </div>
            
                    <form class="hero-quote-form" id="heroQuoteForm" method="POST">
                        @csrf
                        <div class="form-group-hero">
                            <input type="text" id="heroName" name="name" required placeholder="Full Name*">
                        </div>
                        <div class="form-group-hero">
                            <input type="text" id="heroCompany" name="company" required placeholder="Company Name*">
                        </div>
                        <div class="form-group-hero">
                            <input type="text" id="heroMobile" name="mobile" placeholder="Mobile Number">
                        </div>
                        <div class="form-group-hero">
                            <input type="email" id="heroEmail" name="email" required placeholder="Email Address*">
                        </div>
                        <div class="form-group-hero">
                            <input type="text" id="heroAddress" name="address" required placeholder="Project Address*">
                        </div>
                        <div class="form-group-hero">
                            <textarea id="heroRemarks" name="remarks" rows="3" placeholder="Quantity/Remarks"></textarea>
                        </div>
                        <div class="form-group-hero">
                            <select id="heroHowHeard" name="howHeard">
                                <option value="">How Did You Hear About Us?</option>
                                <option value="website">Website / Google Search</option>
                                <option value="social_media">Social Media (Facebook/Instagram)</option>
                                <option value="referral">Referral (Friend/Colleague)</option>
                                <option value="existing_client">Existing Client</option>
                                <option value="trade_show">Trade Show / Event</option>
                                <option value="sales_rep">Sales Representative</option>
                                <option value="others">Others</option>
                            </select>
                        </div>
                        <div class="form-group-hero" id="heroHowHeardOtherGroup" style="display:none;">
                            <input type="text" id="heroHowHeardOther" name="howHeardOther" placeholder="Please specify">
                        </div>
                        <button type="submit" class="btn-submit-hero">SUBMIT INQUIRY</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section id="products" class="products-section">
        <div class="container">
            <div class="section-header reveal">
                <h2 class="section-title">OUR<span> PRODUCTS</span></h2>
                <div class="accent-line"></div>
            </div>

            <div class="products-carousel">
                <button type="button" class="carousel-arrow carousel-arrow-prev" id="productsPrevBtn" aria-label="Previous products">&#10094;</button>

                <div class="products-carousel-viewport" id="productsViewport">
                    <div class="products-grid" id="productsTrack">
                        @forelse ($categories as $category)
                        <div class="product-card reveal">
                            <div class="product-img-container">
                                @if ($category->image)
                                <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="product-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                @endif
                                <div class="fallback-placeholder"@if ($category->image) style="display:none;" @endif>{{ strtoupper($category->name) }}</div>
                            </div>
                            <div class="product-info">
                                <h3><span>{{ $category->name }}</span></h3>
                                <p class="product-desc">{{ $category->banner_desc ?: $category->intro_desc }}</p>
                                <div class="product-footer">
                                    <a href="{{ route('category_detail', $category->slug) }}" class="btn-outline-dark" style="text-align:center;">View Products</a>
                                    <button class="btn-calc-trigger" data-category-slug="{{ $category->slug }}" data-product-name="{{ $category->name }}"><i class="fa-solid fa-calculator"></i> CALCULATE WEIGHT</button>
                                    <button class="btn-quote-trigger btn-orange-sm" data-product="{{ $category->name }}">Inquire Sizes</button>
                                </div>
                            </div>
                        </div>
                        @empty
                        <p class="products-empty-msg">No product categories yet — add one in the admin panel.</p>
                        @endforelse


                        </div>
                </div>

                <button type="button" class="carousel-arrow carousel-arrow-next" id="productsNextBtn" aria-label="Next products">&#10095;</button>
            </div>

            <div class="carousel-dots" id="productsDots" aria-hidden="true"></div>

            <div class="view-all-container">
                <a href="{{ route('products') }}" class="btn-outline-dark">VIEW ALL PRODUCT</a>
            </div>
        </div>
    </section>

    <section id="projects" class="projects-section">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-subtitle">OUR TRACK RECORD</span>
             <h2 class="section-title">PROJECTS WE HAVE <span>SUPPLIED</span></h2>
                <div class="accent-line"></div>
                <p class="projects-intro">A look at some of the developments, fabrication shops, and infrastructure projects we've supplied with steel across the Philippines.</p>
            </div>

            <div class="projects-carousel reveal">
                <div class="projects-track">
                    <div class="project-card">
                        <div class="project-img-container">
                            <img src="/static/images/steel1.jpg" alt="Warehouse and logistics facility project" class="project-img">
                            <span class="project-tag">RENEWABLE ENERGY INFRASTRUCTURE</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> TIWI, ALBAY & BATANGAS–LAGUNA</span>
                            <h3> Tiwi & Mak-ban Geothermal Powerplant</h3>
                            <p>Supplied robust steel products that supported the construction and operational requirements of these major geothermal facilities, which harness the Earth’s natural heat to generate reliable and renewable electricity for the Luzon grid.</p>
                            
                        </div>
                    </div>

                    <div class="project-card">
                        <div class="project-img-container">
                            <img src="/static/images/steel3.jpg" alt="Residential and commercial building project" class="project-img">
                            <span class="project-tag">Commercial Building</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> Pasay City, Metro Manila</span>
                            <h3>SM Mall of Asia</h3>
                            <p>Supplied quality steel materials to support the structural and construction requirements of one of the Philippines’ major shopping, leisure, and entertainment destinations.</p>
                           
                        </div>
                    </div>

                    <div class="project-card">
                        <div class="project-img-container">
                            <img src="/static/images/steel2.jpg" alt="Steel fabrication and roofing project" class="project-img">
                            <span class="project-tag">HOTEL & INTEGRATED RESORT</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> PARAÑAQUE CITY, METRO MANILA</span>
                            <h3>OKADA MANILA</h3>
                            <p>Supplied quality steel materials to support the structural and construction requirements of this 30-hectare luxury integrated resort, featuring hotels, dining, retail, entertainment, and event facilities.</p>
                           
                        </div>
                    </div>

                    <div class="project-card">
                        <div class="project-img-container">
                            <img src="/static/images/steel4.jpg" alt="Nationwide steel delivery project" class="project-img">
                            <span class="project-tag">CONVENTION & EXHIBITION CENTER</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> PASAY CITY, METRO MANILA</span>
                            <h3>SMX CONVENTION CENTER MANILA</h3>
                            <p>Supplied quality steel materials to support the structural and construction requirements of one of the Philippines’ leading venues for conventions, exhibitions, corporate functions, and large-scale events.</p>
                            </div>
                    </div>

                
                    
                    <div class="project-card">
                        <div class="project-img-container">
                            <img src="/static/images/steel6.jpg" alt="Nationwide steel delivery project" class="project-img">
                            <span class="project-tag">TRANSPORTATION INFRASTRUCTURE</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> METRO MANILA TO BACOOR, CAVITE</span>
                            <h3>LRT-1 CAVITE EXTENSION</h3>
                            <p>Contributed quality steel materials to the development of this major railway expansion, designed to improve public transportation and provide faster, more convenient travel between Metro Manila and Cavite.</p>
                            </div>
                    </div>


                        <div class="project-card">
                        <div class="project-img-container">
                            <img src="/static/images/Manila-hotel.jpg" alt="Nationwide steel delivery project" class="project-img">
                            <span class="project-tag">HOTEL & HOSPITALITY</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> ERMITA, MANILA</span>
                            <h3>THE MANILA HOTEL</h3>
                            <p>Provided dependable steel materials for the construction and improvement requirements of this historic luxury hotel, helping preserve its distinguished character while supporting modern hospitality operations.</p>
                            </div>
                    </div>

                        <div class="project-card">
                        <div class="project-img-container">
                            <img src="/static/images/steel5.jpg" alt="Nationwide steel delivery project" class="project-img">
                            <span class="project-tag">INTEGRATED RESORT & ENTERTAINMENT COMPLEX</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> PASAY CITY, METRO MANILA</span>
                            <h3>NEWPORT WORLD RESORTS</h3>
                            <p>Delivered reliable steel materials that contributed to the development of this landmark destination, bringing together luxury hotels, dining establishments, retail spaces, entertainment venues, and event facilities within one integrated complex.</p>
                            </div>
                    </div>

                        <div class="project-card">
                        <div class="project-img-container">
                            <img src="/static/images/SM-SAN LAZARO.jpg" alt="Nationwide steel delivery project" class="project-img">
                            <span class="project-tag">COMMERCIAL & RETAIL DEVELOPMENT</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> SANTA CRUZ, MANILA</span>
                            <h3>SM CITY SAN LAZARO</h3>
                            <p>Supported the development of this major urban shopping destination through the supply of dependable steel materials for its structural and construction needs.</p>
                            </div>
                    </div>

                        <div class="project-card">
                        <div class="project-img-container">
                            <img src="/static/images/GN-POWERPLANT.jpg" alt="Nationwide steel delivery project" class="project-img">
                            <span class="project-tag">POWER & ENERGY INFRASTRUCTURE</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> MARIVELES, BATAAN</span>
                            <h3>GN POWER MARIVELES ENERGY CENTER</h3>
                            <p>Furnished durable steel products that supported the development of this large-scale energy facility, built to strengthen power generation capacity and help meet the growing electricity requirements of the Luzon grid.</p>
                            </div>
                    </div>

                        <div class="project-card">
                        <div class="project-img-container">
                            <img src="/static/images/NLEX-.jpg" alt="Nationwide steel delivery project" class="project-img">
                            <span class="project-tag">ROAD & TRANSPORTATION INFRASTRUCTURE</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> METRO MANILA TO CENTRAL AND NORTHERN LUZON</span>
                            <h3>NORTH LUZON EXPRESSWAY</h3>
                            <p>Contributed durable steel products for the construction and improvement of this major expressway, helping support safer roadways, smoother mobility, and more efficient movement of people and goods between Metro Manila and Northern Luzon.</p>
                            </div>
                    </div>


<!-- ==================== SECOND SET (DUPLICATES FOR MARQUEE LOOP) ==================== -->
                    <div class="project-card" aria-hidden="true">
                        <div class="project-img-container">
                            <img src="/static/images/steel1.jpg" alt="" class="project-img">
                            <span class="project-tag">RENEWABLE ENERGY INFRASTRUCTURE</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> TIWI, ALBAY & BATANGAS–LAGUNA</span>
                            <h3> Tiwi & Mak-ban Geothermal Powerplant</h3>
                            <p>Supplied robust steel products that supported the construction and operational requirements of these major geothermal facilities, which harness the Earth’s natural heat to generate reliable and renewable electricity for the Luzon grid.</p>
                        </div>
                    </div>

                    <div class="project-card" aria-hidden="true">
                        <div class="project-img-container">
                            <img src="/static/images/steel3.jpg" alt="" class="project-img">
                            <span class="project-tag">Commercial Building</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> Pasay City, Metro Manila</span>
                            <h3>SM Mall of Asia</h3>
                            <p>Supplied quality steel materials to support the structural and construction requirements of one of the Philippines’ major shopping, leisure, and entertainment destinations.</p>
                        </div>
                    </div>

                    <div class="project-card" aria-hidden="true">
                        <div class="project-img-container">
                            <img src="/static/images/steel2.jpg" alt="" class="project-img">
                            <span class="project-tag">HOTEL & INTEGRATED RESORT</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> PARAÑAQUE CITY, METRO MANILA</span>
                            <h3>OKADA MANILA</h3>
                            <p>Supplied quality steel materials to support the structural and construction requirements of this 30-hectare luxury integrated resort, featuring hotels, dining, retail, entertainment, and event facilities.</p>
                        </div>
                    </div>

                    <div class="project-card" aria-hidden="true">
                        <div class="project-img-container">
                            <img src="/static/images/steel4.jpg" alt="" class="project-img">
                            <span class="project-tag">CONVENTION & EXHIBITION CENTER</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> PASAY CITY, METRO MANILA</span>
                            <h3>SMX CONVENTION CENTER MANILA</h3>
                            <p>Supplied quality steel materials to support the structural and construction requirements of one of the Philippines’ leading venues for conventions, exhibitions, corporate functions, and large-scale events.</p>
                        </div>
                    </div>

                    <div class="project-card" aria-hidden="true">
                        <div class="project-img-container">
                            <img src="/static/images/steel6.jpg" alt="" class="project-img">
                            <span class="project-tag">TRANSPORTATION INFRASTRUCTURE</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> METRO MANILA TO BACOOR, CAVITE</span>
                            <h3>LRT-1 CAVITE EXTENSION</h3>
                            <p>Contributed quality steel materials to the development of this major railway expansion, designed to improve public transportation and provide faster, more convenient travel between Metro Manila and Cavite.</p>
                        </div>
                    </div>

                    <div class="project-card" aria-hidden="true">
                        <div class="project-img-container">
                            <img src="/static/images/Manila-hotel.jpg" alt="" class="project-img">
                            <span class="project-tag">HOTEL & HOSPITALITY</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> ERMITA, MANILA</span>
                            <h3>THE MANILA HOTEL</h3>
                            <p>Provided dependable steel materials for the construction and improvement requirements of this historic luxury hotel, helping preserve its distinguished character while supporting modern hospitality operations.</p>
                        </div>
                    </div>

                    <div class="project-card" aria-hidden="true">
                        <div class="project-img-container">
                            <img src="/static/images/steel5.jpg" alt="" class="project-img">
                            <span class="project-tag">INTEGRATED RESORT & ENTERTAINMENT COMPLEX</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> PASAY CITY, METRO MANILA</span>
                            <h3>NEWPORT WORLD RESORTS</h3>
                            <p>Delivered reliable steel materials that contributed to the development of this landmark destination, bringing together luxury hotels, dining establishments, retail spaces, entertainment venues, and event facilities within one integrated complex.</p>
                        </div>
                    </div>

                    <div class="project-card" aria-hidden="true">
                        <div class="project-img-container">
                            <img src="/static/images/SM-SAN LAZARO.jpg" alt="" class="project-img">
                            <span class="project-tag">COMMERCIAL & RETAIL DEVELOPMENT</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> SANTA CRUZ, MANILA</span>
                            <h3>SM CITY SAN LAZARO</h3>
                            <p>Supported the development of this major urban shopping destination through the supply of dependable steel materials for its structural and construction needs.</p>
                        </div>
                    </div>

                    <div class="project-card" aria-hidden="true">
                        <div class="project-img-container">
                            <img src="/static/images/GN-POWERPLANT.jpg" alt="" class="project-img">
                            <span class="project-tag">POWER & ENERGY INFRASTRUCTURE</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> MARIVELES, BATAAN</span>
                            <h3>GN POWER MARIVELES ENERGY CENTER</h3>
                            <p>Furnished durable steel products that supported the development of this large-scale energy facility, built to strengthen power generation capacity and help meet the growing electricity requirements of the Luzon grid.</p>
                        </div>
                    </div>

                    <div class="project-card" aria-hidden="true">
                        <div class="project-img-container">
                            <img src="/static/images/NLEX-.jpg" alt="" class="project-img">
                            <span class="project-tag">ROAD & TRANSPORTATION INFRASTRUCTURE</span>
                        </div>
                        <div class="project-info">
                            <span class="project-location"><i class="fa-solid fa-location-dot"></i> METRO MANILA TO CENTRAL AND NORTHERN LUZON</span>
                            <h3>NORTH LUZON EXPRESSWAY</h3>
                            <p>Contributed durable steel products for the construction and improvement of this major expressway, helping support safer roadways, smoother mobility, and more efficient movement of people and goods between Metro Manila and Northern Luzon.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="why-choose" class="why-choose-section">
        <div class="container">
            <div class="why-choose-header reveal">
                <span class="header-line"></span>
                <h2>WHY CHOOSE TDT POWERSTEEL?</h2>
                <span class="header-line"></span>
            </div>

            <div class="why-choose-grid">
                <div class="why-card reveal reveal-card-deal">
                    <div class="why-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="m9 11 2 2 4-4"></path>
                        </svg>
                    </div>
                    <h3><span>PREMIUM QUALITY</span> MATERIALS</h3>
                    <p>We source only high-grade steel from trusted, certified mills worldwide to ensure maximum safety.</p>
                </div>

                <div class="why-card reveal reveal-card-deal">
                    <div class="why-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="12" x="2" y="6" rx="2"></rect>
                            <circle cx="12" cy="12" r="2"></circle>
                            <path d="M6 12h.01M18 12h.01"></path>
                        </svg>
                    </div>
                    <h3><span>COMPETITIVE</span> PRICING</h3>
                    <p>We offer the best market value without compromising the quality of our industrial components.</p>
                </div>

                <div class="why-card reveal reveal-card-deal">
                    <div class="why-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 3.73A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                            <path d="M12 22V12"></path>
                            <path d="m3.3 7 8.7 5 8.7-5"></path>
                        </svg>
                    </div>
                    <h3><span>LARGE</span> INVENTORY</h3>
                    <p>We maintain a vast, ready-to-ship stock of diverse steel products to meet any project scale.</p>
                </div>

                <div class="why-card reveal reveal-card-deal">
                    <div class="why-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13" rx="2" ry="2"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                    </div>
                    <h3><span>FAST</span> DELIVERY</h3>
                    <p>Our specialized logistics network ensures precise on-time delivery across the entire archipelago.</p>
                </div>

                <div class="why-card reveal reveal-card-deal">
                    <div class="why-icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M19 16v6"></path>
                            <path d="M22 19h-6"></path>
                        </svg>
                    </div>
                    <h3><span>EXPERT</span> SUPPORT</h3>
                    <p>Our technical team is ready to assist you with specifications and structural solutions.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="faq-section">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-subtitle">COMMON INQUIRIES</span>
                <h2 class="section-title">FREQUENTLY ASKED<span> QUESTIONS</span></h2>
                <div class="accent-line"></div>
            </div>

            <div class="faq-accordion">
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Are your steel products PNS certified?</span>
                        <span class="faq-icon">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Yes. All our deformed steel bars, structural beams, and pipes strictly comply with Philippine National Standards (PNS 49 for rebars) and undergo rigid quality inspection tests.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Do you offer nationwide delivery?</span>
                        <span class="faq-icon">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Yes, we deliver nationwide. We have our own logistics fleet consisting of boom trucks, dropsides, and forwarders to ensure on-time delivery directly to your project site.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Is there a minimum order requirement?</span>
                        <span class="faq-icon">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>For delivery orders, we require a minimum volume depending on the product type. However, for warehouse pickups here at Vicente Cruz St., Sampaloc, we do not impose any minimum order quantity.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>How often do your steel prices change?</span>
                        <span class="faq-icon">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Steel prices fluctuate based on global market demands, raw material costs, and importation rates. We update our price list regularly. To get the most accurate and competitive pricing, you can request a formal quotation through our 'Get a Quote' button.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>What is the standard lead time for deliveries?</span>
                        <span class="faq-icon">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>For Metro Manila and nearby provinces, delivery usually takes 1 to 3 business days upon payment confirmation and order finalization. For provincial deliveries outside Luzon, shipment schedule will depend on vessel availability and will be coordinated closely with our logistics team.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection