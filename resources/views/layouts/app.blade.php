<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Steel Supplier in Manila | TDT Powersteel Corporation')</title>
    <meta name="description" content="@yield('description', 'Steel supplier in Sampaloc, Manila carrying PNS-certified steel bars, steel beams, steel plates, and steel pipes, with nationwide delivery. Request a free quote in minutes.')">
    <meta name="author" content="TDT Powersteel">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/favicon.ico">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'TDT Powersteel Corporation | Premium Steel Supplier')">
    <meta property="og:description" content="@yield('description', 'Premium quality construction solutions. Sourcing PNS-certified steel bars, columns, plates, and pipes with nationwide delivery.')">
    <meta property="og:image" content="{{ secure_url('/static/images/social-share-preview.png') }}">
    <meta property="og:image:alt" content="TDT Powersteel Corporation - Premium Steel Supplier">
    <meta property="og:site_name" content="TDT Powersteel Corporation">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'TDT Powersteel Corporation | Premium Steel Supplier')">
    <meta name="twitter:description" content="@yield('description', 'Premium quality construction solutions. Sourcing PNS-certified steel bars, columns, plates, and pipes with nationwide delivery.')">
    <meta name="twitter:image" content="{{ secure_url('/static/images/social-share-preview.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- All CSS files properly linked inside head -->
    <link rel="stylesheet" href="/static/base.css">
    <link rel="stylesheet" href="/static/header.css">
    <link rel="stylesheet" href="/static/hero.css">
    <link rel="stylesheet" href="/static/faq.css">
    <link rel="stylesheet" href="/static/projects.css">
    <link rel="stylesheet" href="/static/products.css">
    <link rel="stylesheet" href="/static/products-carousel.css">
    <link rel="stylesheet" href="/static/why-choose.css">
    <link rel="stylesheet" href="/static/footer.css">
    <link rel="stylesheet" href="/static/widgets.css">
    <link rel="stylesheet" href="/static/chatwidget.css">
    <link rel="stylesheet" href="/static/calculator.css">
    @stack('styles')

    <!-- JSON-LD Structured Data: Organization + LocalBusiness -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "LocalBusiness",
        "name": "TDT Powersteel Corporation",
        "description": "Premium steel supplier in the Philippines carrying PNS-certified steel bars, beams, plates, pipes, and more with nationwide delivery.",
        "url": "https://www.tdtpowersteel.com",
        "logo": "https://www.tdtpowersteel.com/static/images/logo.png",
        "image": "https://www.tdtpowersteel.com/static/images/social-share-preview.png",
        "telephone": "+62-2-8831-0000",
        "email": "inquiry@tdtpowersteel.com.ph",
        "address": {
            "@@type": "PostalAddress",
            "streetAddress": "1017 Vicente Cruz St., Sampaloc",
            "addressLocality": "Manila",
            "addressRegion": "Metro Manila",
            "postalCode": "1008",
            "addressCountry": "PH"
        },
        "geo": {
            "@@type": "GeoCoordinates",
            "latitude": "14.6131",
            "longitude": "120.9925"
        },
        "openingHoursSpecification": [
            {
                "@@type": "OpeningHoursSpecification",
                "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday"],
                "opens": "08:00",
                "closes": "18:00"
            },
            {
                "@@type": "OpeningHoursSpecification",
                "dayOfWeek": "Friday",
                "opens": "08:00",
                "closes": "17:00"
            }
        ],
        "sameAs": [
            "https://www.facebook.com/share/1Bsz7WUitR/",
            "https://www.instagram.com/tdtpowersteelinc/",
            "https://youtube.com/@tdtpowersteelinc"
        ],
        "priceRange": "$$",
        "areaServed": {
            "@type": "Country",
            "name": "Philippines"
        }
    }
    </script>
</head>
<body>


    <div class="top-bar">
        <div class="container top-bar-flex">
            <div class="top-left">
                <span>📍 1017 VICENTE CRUZ ST., SAMPALOC, MANILA, PHILIPPINES</span>
            </div>
            <div class="top-right">
                <span>📞 (02) 8831-0000</span>
                <span>MON - FRI: 08:00 AM - 05:00 PM</span>
            </div>
        </div>
    </div>

    <header class="main-header" id="mainHeader">
        <div class="container nav-container">
            <div class="logo-group">
                <div class="logo">
                    <a href="/" style="display:block;">
                        <img src="/static/images/logo.png" alt="TDT Powersteel Logo" class="site-logo">
                    </a>
                </div>
            </div>
            
            <button class="menu-toggle" id="mobileMenuBtn" aria-label="Toggle Menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <nav class="nav-links" id="navLinks">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">HOME</a>
                <a href="{{ route('products') }}" class="{{ request()->is('products*') ? 'active' : '' }}">PRODUCTS</a>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">ABOUT US</a>
                <a href="{{ route('blog') }}" class="{{ request()->is('blog*') ? 'active' : '' }}">BLOG</a>
                <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">CONTACT</a>
            </nav>
            <div class="nav-overlay" id="navOverlay"></div>
        </div>
    </header>

    @yield('content')

    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-col footer-brand">
                <div class="footer-logo-wrap">
                    <img src="/static/images/logo.png" alt="TDT Powersteel Logo" class="footer-logo">
                </div>
                <p class="footer-tagline">Your trusted partner in construction, delivering premium steel products and reliable service nationwide.</p>
                <div class="social-icons">
                <a href="https://www.facebook.com/share/1Bsz7WUitR/" target="_blank" rel="noopener noreferrer" class="social-btn facebook" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.instagram.com/tdtpowersteelinc/" target="_blank" rel="noopener noreferrer" class="social-btn instagram" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://youtube.com/@tdtpowersteelinc?si=yS93GZls53MU0alc" target="_blank" rel="noopener noreferrer" class="social-btn youtube" aria-label="YouTube">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Quick Links</h4>
                <ul class="footer-list">
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('products') }}">Products</a></li>
                    <li><a href="{{ route('about') }}">About Us</a></li>
                    <li><a href="{{ route('blog') }}">Blog</a></li>
                    <li><a href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Products</h4>
                <ul class="footer-list">
                    <li><a href="{{ route('category_detail', 'steel-bars') }}">Steel Bars</a></li>
                    <li><a href="{{ route('category_detail', 'wide-flange') }}">Wide Flange</a></li>
                    <li><a href="{{ route('category_detail', 'tubes-pipes') }}">Tubes and Pipes</a></li>
                    <li><a href="{{ route('category_detail', 'plates-sheets') }}">Plates</a></li>
                    <li><a href="{{ route('category_detail', 'wiremesh') }}">Wire Meshes</a></li>
                    <li><a href="{{ route('category_detail', 'roofing') }}">Roofing</a></li>
                    <li><a href="{{ route('products') }}">View All Products</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Contact Us</h4>
                <ul class="footer-list footer-contact-list">
                    <li><i class="fa-solid fa-location-dot"></i><span>1017 Vicente Cruz St., Sampaloc, Manila, Philippines</span></li>
                    <li><i class="fa-solid fa-phone"></i><span>Trunkline: (02) 8831-0000</span></li>
                    <li><i class="fa-solid fa-fax"></i><span>Telefax: (02) 8230-2906</span></li>
                    <li><i class="fa-solid fa-mobile-screen"></i><span>Mobile: 0932 888 7777</span></li>
                    <li><i class="fa-solid fa-envelope"></i><span>inquiry@tdtpowersteel.com.ph</span></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Business Hours</h4>
                <ul class="footer-hours-list">
                    <li><span>Mon - Thu</span><span>8:00 AM - 6:00 PM</span></li>
                    <li><span>Friday</span><span>8:00 AM - 5:00 PM</span></li>
                    <li><span>Saturday</span><span>Closed</span></li>
                    <li><span>Sunday</span><span>Closed</span></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container footer-bottom-flex">
                <p>© 2026 TDT Powersteel Corporation. All Rights Reserved.</p>
                <div class="footer-legal-links">
                    <a href="{{ route('privacy_policy') }}">Privacy Policy</a>
                    <a href="{{ route('terms_and_conditions') }}">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Weight Calculator Modal -->
    <div id="calcModalOverlay" class="calc-modal-overlay">
        <div class="calc-modal">
            <div class="calc-header">
                <h2>WEIGHT CALCULATOR</h2>
                <button class="calc-close" id="calcClose">&times;</button>
            </div>
            <div class="calc-body">
                <div class="calc-product-name" id="calcProductName"></div>
                <div id="calcFormContainer"></div>
                <div class="calc-btn-row">
                    <button type="button" class="calc-btn calc-btn-calc" id="calcCalcBtn">CALCULATE</button>
                    <button type="button" class="calc-btn calc-btn-reset" id="calcResetBtn">RESET</button>
                </div>
                <div class="calc-result" id="calcResult">
                    <div class="calc-result-label">Estimated Weight</div>
                    <div class="calc-result-weight" id="calcResultWeight"></div>
                    <div class="calc-result-secondary" id="calcResultSecondary"></div>
                    <div class="calc-result-breakdown" id="calcResultBreakdown"></div>
                    <div class="calc-btn-row result-actions">
                        <button type="button" class="calc-btn calc-btn-quote" id="calcQuoteBtn">REQUEST A QUOTE</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quote Modal -->
    <div id="quoteModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h2>REQUEST A QUOTE</h2>
                <button class="modal-close" id="closeModal">X</button>
            </div>
            <form id="quoteForm">
                <input type="hidden" name="sourcePage" value="home">
                <div class="form-group">
                    <label for="selectedProduct">Steel Category</label>
                    <input type="text" name="productCategory" id="selectedProduct" readonly>
                </div>
                
                <div class="form-group" id="subProductGroup">
                    <label for="subProductSelect">Specific Steel Product / Size</label>
                    <select name="subProduct" id="subProductSelect" style="cursor: pointer;">
                        <option value="">-- Select Size / Specification --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="clientName">Full Name *</label>
                    <input type="text" name="clientName" id="clientName" required placeholder="e.g. Juan Dela Cruz">
                </div>
                <div class="form-group">
                    <label for="clientCompany">Company Name</label>
                    <input type="text" name="clientCompany" id="clientCompany" placeholder="e.g. ABC Construction">
                </div>
                <div class="form-group">
                    <label for="clientEmail">Email Address *</label>
                    <input type="email" name="clientEmail" id="clientEmail" required placeholder="e.g. juan@example.com">
                </div>
                <div class="form-group">
                    <label for="clientContact">Contact Number *</label>
                    <input type="text" name="clientContact" id="clientContact" required placeholder="e.g. 0917XXXXXXX">
                </div>
                <div class="form-group">
                    <label for="clientAddress">Project Address</label>
                    <input type="text" name="clientAddress" id="clientAddress" placeholder="e.g. Sampaloc, Manila">
                </div>
                <div class="form-group">
                    <label for="estimatedQty">Estimated Quantity Needed *</label>
                    <input type="text" name="estimatedQty" id="estimatedQty" required placeholder="e.g. 500 pcs / 10 tons">
                </div>
                <div class="form-group">
                    <label for="qHowHeard">How Did You Hear About Us?</label>
                    <select id="qHowHeard" name="qHowHeard">
                        <option value="">-- Select an option --</option>
                        <option value="website">Website / Google Search</option>
                        <option value="social_media">Social Media (Facebook/Instagram)</option>
                        <option value="referral">Referral (Friend/Colleague)</option>
                        <option value="existing_client">Existing Client</option>
                        <option value="trade_show">Trade Show / Event</option>
                        <option value="sales_rep">Sales Representative</option>
                        <option value="others">Others</option>
                    </select>
                </div>
                <div class="form-group" id="qHowHeardOtherGroup" style="display:none;">
                    <label for="qHowHeardOther">Please Specify</label>
                    <input type="text" name="qHowHeardOther" id="qHowHeardOther" placeholder="Tell us how you heard about us">
                </div>
                <button type="submit" class="btn-orange submit-btn" id="quoteSubmitBtn">
                    <span class="btn-text">SUBMIT REQUEST</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Back to top button -->
    <button id="backToTopBtn" title="Go to top">↑</button>

    <!-- Main JavaScript file handles everything cleanly -->
    <script defer src="/static/script.js"></script>
    <script defer src="/static/chatwidget.js"></script>
    <script defer src="/static/calculator.js"></script>
    @stack('scripts')
</body>
</html>