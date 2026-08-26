@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="/static/subpages.css">
<link rel="stylesheet" href="/static/contact.css">
@endpush

@section('title', 'Contact Us | TDT Powersteel Corporation')
@section('description', "Get in touch with TDT Powersteel Corporation's sales team — call, email, or visit any of our branches nationwide.")

@section('content')

    <section class="page-banner">
        <div class="container">
            <span class="section-subtitle">GET IN TOUCH</span>
            <h1 class="page-banner-title"><span> CONTACT OUR SALES </span></h1>
            <div class="breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <span class="current">Contact</span>
            </div>
        </div>
    </section>

    <section class="page-section contact-main-section">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-left-col">
                <div class="contact-info-panel">
                    <h3><span>Office & Warehouse</span></h3>
                    <p class="panel-desc">Have questions about our pricing or stock availability? Visit us or drop us a line.</p>

                    <div class="info-list">
                        <div class="info-card">
                            <span class="info-icon">📍</span>
                            <div>
                                <h4>Head Office Address</h4>
                                <p>1017 Vicente Cruz St., Sampaloc, Manila, Philippines</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <span class="info-icon">📞</span>
                            <div>
                                <h4>Hotlines & Sales</h4>
                                <p>(02) 8731-8888 / +63 917 123 4567</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <span class="info-icon">✉️</span>
                            <div>
                                <h4>Email Address</h4>
                                <p>sales@tdtpowersteel.com.ph</p>
                            </div>
                        </div>
                    </div>

                    <div class="branches-block">
                        <h4 class="branches-heading"><span>Our Branches (Click for Details)</span></h4>
                        <div class="branch-list">
                            <details class="branch-details" open>
                                <summary><span><span class="branch-dot">•</span>Manila Main</span></summary>
                                <div class="branch-details-body">
                                    <strong>TDT POWERSTEEL CORPORATION</strong>
                                    <p><strong>Address:</strong> Vicente Cruz St. 1017-A 2/F, Brgy. 475 Sampaloc, NCR, City of Manila First District, 1015</p>
                                    <p><strong>Telefax:</strong> (02) 8831-0000 / (02) 8230-2908</p>
                                    <p><strong>Mobile:</strong> 0977-8509274 / 0917-5385048 / 0977-8035487 / 0977-7083247 / 0917-7079810</p>
                                    <p><strong>Email:</strong> websitehandling@tdtpowersteel.com.ph</p>
                                </div>
                            </details>

                            <details class="branch-details">
                                <summary><span><span class="branch-dot">•</span>Isabela</span></summary>
                                <div class="branch-details-body">
                                    <p><strong>Address:</strong> Nungnungan, Municipality of Cauayan, Province of Isabela</p>
                                    <p><strong>Mobile:</strong> 0917 834 9007 / 0917-7127703 / 0917-7048213 / 0917-7083233 / 0917-1051738</p>
                                </div>
                            </details>

                            <details class="branch-details">
                                <summary><span><span class="branch-dot">•</span>Cebu</span></summary>
                                <div class="branch-details-body">
                                    <p><strong>Address:</strong> Door-1B Vel-ouano Building II, M.C. Briones St., Highway, Brgy. Bakilid Mandaue City</p>
                                    <p><strong>Mobile:</strong> 0917 854 1995 / 0917-8542032 / 0917-8542020</p>
                                </div>
                            </details>

                            <details class="branch-details">
                                <summary><span><span class="branch-dot">•</span>CDO</span></summary>
                                <div class="branch-details-body">
                                    <p><strong>Address:</strong> Unit 11, Cabilogan St., Barangay Bugo, CDO City</p>
                                    <p><strong>Mobile:</strong> 0977 850 9288</p>
                                </div>
                            </details>

                            <details class="branch-details">
                                <summary><span><span class="branch-dot">•</span>Davao</span></summary>
                                <div class="branch-details-body">
                                    <p><strong>Address:</strong> Door-2 GRI Business Center KM. 14, Panacan, Davao City</p>
                                    <p><strong>Mobile:</strong> 0917 813 1091 / 0917-1097828</p>
                                </div>
                            </details>

                            <details class="branch-details">
                                <summary><span><span class="branch-dot">•</span>GenSan</span></summary>
                                <div class="branch-details-body">
                                    <p><strong>Mobile:</strong> 0965 338 2440 / 0912 003 9935</p>
                                </div>
                            </details>
                        </div>
                    </div>

                    <div class="social-links-container" style="margin-top: 25px; border-top: 1px solid rgba(113, 112, 116, 0.15); padding-top: 15px;">
                        <h4 style="color: var(--tdt-dark); font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px; font-weight: 700;">Connect With Us</h4>
                        <div class="social-icons" style="display: flex; gap: 10px;">
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
                </div>
                </div>

                <div class="contact-right-col">
                <div class="map-container">
                    <div class="map-wrapper" style="height: 100%; min-height: 350px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.916172605664!2d120.9930706758455!3d14.603816677027558!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c9f91a629b3b%3A0xf65922880004f141!2s1017%20Vicente%20Cruz%20St%2C%20Sampaloc%2C%20Manila%2C%201008%20Metro%20Manila!5e0!3m2!1sen!2sph!4v1710000000000!5m2!1sen!2sph"
                            width="100%"
                            height="100%"
                            style="border:0; display: block; min-height: 100%;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>

                <div class="contact-form-panel">
                    <h3><span> Send a Direct Message </span></h3>
                    <p class="panel-desc">Send us your inquiries directly and our representative will get back to you shortly.</p>
                    <form class="direct-contact-form" id="directContactForm">
                        @csrf
                        <div style="position:absolute;left:-5000px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                            <label for="contact_website_hp">Leave this field empty</label>
                            <input type="text" name="website" id="contact_website_hp" tabindex="-1" autocomplete="off">
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="cName">Full Name *</label>
                                <input type="text" name="cName" id="cName" required placeholder="Your Name">
                            </div>
                            <div class="form-group">
                                <label for="cCompany">Company Name</label>
                                <input type="text" name="cCompany" id="cCompany" placeholder="Your Company">
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="cEmail">Email Address *</label>
                                <input type="email" name="cEmail" id="cEmail" required placeholder="email@example.com">
                            </div>
                            <div class="form-group">
                                <label for="cPhone">Phone Number *</label>
                                <input type="tel" name="cPhone" id="cPhone" required placeholder="+63 000 000 0000">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cLandline">Landline Number</label>
                            <input type="tel" name="cLandline" id="cLandline" placeholder="(02) 8000 0000">
                        </div>
                        <div class="form-group">
                            <label for="cAddress">Address</label>
                            <input type="text" name="cAddress" id="cAddress" placeholder="Your complete address">
                        </div>
                        <div class="form-group">
                            <label for="cHowHeard">How Did You Hear About Us?</label>
                            <select id="cHowHeard" name="cHowHeard">
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
                        <div class="form-group" id="cHowHeardOtherGroup" style="display:none;">
                            <label for="cHowHeardOther">Please Specify</label>
                            <input type="text" name="cHowHeardOther" id="cHowHeardOther" placeholder="Tell us how you heard about us">
                        </div>
                        <div class="form-group">
                            <label for="cMessage">Message *</label>
                            <textarea name="cMessage" id="cMessage" rows="5" required placeholder="Tell us about your requirements..."></textarea>
                        </div>
                        <button type="submit" class="btn-orange" id="contactSubmitBtn">
                            <span class="btn-text">SEND MESSAGE</span>
                            <span class="spinner"></span>
                        </button>
                    </form>
                </div>
                </div>
            </div>
        </div>
    </section>

@endsection
