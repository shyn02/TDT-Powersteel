@extends('layouts.app')

@section('title', 'Terms and Conditions | TDT Powersteel Corporation')
@section('description', 'Read TDT Powersteel Corporation\'s Terms and Conditions for using our website.')

@push('styles')
<link rel="stylesheet" href="{{ asset('static/subpages.css') }}">
<link rel="stylesheet" href="{{ asset('static/terms-and-conditions.css') }}">
@endpush

@section('content')
    <!-- PAGE BANNER -->
    <section class="page-banner terms-banner">
        <div class="container">
            <span class="section-subtitle">LEGAL</span>
            <h1 class="page-banner-title"><span>TERMS AND CONDITIONS</span></h1>
            <div class="breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <span class="current">Terms and Conditions</span>
            </div>
        </div>
    </section>

    <!-- LEGAL CONTENT -->
    <section class="page-section">
        <div class="container">
            <div class="legal-content">

                <span class="effective-date">Effective Date: May 26, 2025</span>

                <p class="legal-intro">Welcome to TDT Power Steel. These Terms and Conditions outline the rules and regulations for the use of our website at <a href="https://tdtpowersteel.com.ph" target="_blank" rel="noopener noreferrer">tdtpowersteel.com.ph</a>. By accessing or using this Website, you accept these Terms and Conditions in full. If you disagree with any part of these terms, please do not use our Website.</p>

                <section>
                    <h2><span class="num">1.</span> Acceptance of Terms</h2>
                    <p>By accessing and using this Website, you agree to be bound by these Terms and Conditions and all applicable laws and regulations in the Philippines, including the Data Privacy Act of 2012 (Republic Act No. 10173).</p>
                </section>

                <section>
                    <h2><span class="num">2.</span> Use of the Website</h2>
                    <p>You agree to use this Website only for lawful purposes. You must not:</p>
                    <ul>
                        <li>Violate any applicable Philippine or international laws.</li>
                        <li>Interfere with the proper functioning of the Website.</li>
                        <li>Attempt to access restricted or unauthorized areas.</li>
                    </ul>
                </section>

                <section>
                    <h2><span class="num">3.</span> Intellectual Property Rights</h2>
                    <p>All content on this Website&mdash;including but not limited to text, images, graphics, and logos&mdash;is the intellectual property of TDT Power Steel and is protected under Philippine copyright and intellectual property laws. You may not use any content from this Website without our prior written permission.</p>
                </section>

                <section>
                    <h2><span class="num">4.</span> Product Information and Pricing</h2>
                    <p>We strive to ensure the accuracy of product details, descriptions, and pricing. However, TDT Power Steel does not guarantee that all information on the Website is always complete, accurate, or up-to-date. Prices and availability are subject to change without notice.</p>
                </section>

                <section>
                    <h2><span class="num">5.</span> Orders and Transactions</h2>
                    <p>All purchases made through the Website are subject to confirmation of order and product availability. We reserve the right to cancel or refuse any order at our discretion. You agree to provide accurate, complete, and current information during the order process.</p>
                </section>

                <section>
                    <h2><span class="num">6.</span> Data Privacy and Protection</h2>
                    <p>We collect and process personal data in accordance with the Data Privacy Act of 2012 and its Implementing Rules and Regulations (IRR). By using this Website and submitting your personal information, you consent to the collection, use, storage, and processing of your data for purposes such as:</p>
                    <ul>
                        <li>Order fulfillment</li>
                        <li>Customer service</li>
                        <li>Marketing (with your consent)</li>
                        <li>Website improvements</li>
                    </ul>
                    <p>We take reasonable steps to protect your personal data from unauthorized access, alteration, or disclosure. For more information, please refer to our <a href="{{ route('privacy_policy') }}">Privacy Policy</a>.</p>
                </section>

                <section>
                    <h2><span class="num">7.</span> Cookies</h2>
                    <p>Our Website may use cookies to enhance your user experience. By continuing to browse the site, you agree to our use of cookies in accordance with our Cookie Policy.</p>
                </section>

                <section>
                    <h2><span class="num">8.</span> Third-Party Links</h2>
                    <p>Our Website may contain links to external websites that are not operated by us. We are not responsible for the content or practices of any third-party sites and encourage you to review their terms and policies.</p>
                </section>

                <section>
                    <h2><span class="num">9.</span> Limitation of Liability</h2>
                    <p>To the fullest extent permitted by law, TDT Power Steel shall not be liable for any damages or losses arising out of your use or inability to use the Website or reliance on any information provided on it.</p>
                </section>

                <section>
                    <h2><span class="num">10.</span> Changes to Terms</h2>
                    <p>We may update these Terms and Conditions at any time without prior notice. Changes will take effect once posted on this page. Your continued use of the Website after changes are posted constitutes your acceptance of the updated Terms.</p>
                </section>

                <section>
                    <h2><span class="num">11.</span> Governing Law</h2>
                    <p>These Terms shall be governed and interpreted under the laws of the Republic of the Philippines. Any disputes shall be resolved in the appropriate courts located in [insert city], Philippines.</p>
                </section>

                <section>
                    <h2><span class="num">12.</span> Contact Information</h2>
                    <p>For questions or concerns regarding these Terms and Conditions or your personal data, please contact:</p>
                    <div class="contact-block">
                        <p>TDT Power Steel</p>
                        <p>1017 Vicente Cruz St. Sampaloc Manila, Philippines</p>
                        <p>Email: <a href="mailto:inquiry@tdtpowersteel.com.ph">inquiry@tdtpowersteel.com.ph</a></p>
                        <p>Phone: 0932 888 7777</p>
                    </div>
                </section>

            </div>
        </div>
    </section>
@endsection
