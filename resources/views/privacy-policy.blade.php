@extends('layouts.app')

@section('title', 'Privacy Policy | TDT Powersteel Corporation')
@section('description', 'Read TDT Powersteel Corporation\'s Privacy Policy to learn how we collect, use, and protect your personal information.')

@push('styles')
<link rel="stylesheet" href="{{ asset('static/subpages.css') }}">
<link rel="stylesheet" href="{{ asset('static/privacy-policy.css') }}">
@endpush

@section('content')
    <!-- PAGE BANNER -->
    <section class="page-banner privacy-banner">
        <div class="container">
            <span class="section-subtitle">LEGAL</span>
            <h1 class="page-banner-title"><span>PRIVACY POLICY</span></h1>
            <div class="breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <span class="current">Privacy Policy</span>
            </div>
        </div>
    </section>

    <!-- LEGAL CONTENT -->
    <section class="page-section">
        <div class="container">
            <div class="legal-content">

                <span class="effective-date">Effective Date: May 5, 2025</span>

                <p class="legal-intro">TDT Powersteel ("TDT Powersteel", "we", "us", or "our") respects your privacy and is committed to protecting any personal data you provide through our website <a href="https://tdtpowersteel.com.ph" target="_blank" rel="noopener noreferrer">tdtpowersteel.com.ph</a> ("the Site").</p>
                <p class="legal-intro">This Privacy Policy outlines how we collect, use, disclose, and safeguard your information when you visit our Site. By using this Site, you agree to the terms of this policy.</p>

                <section>
                    <h2><span class="num">1.</span> Information We Collect</h2>

                    <h3>a. Personal Information</h3>
                    <p>We collect personal data you voluntarily provide through contact forms, inquiries, or other interactions with the Site. This may include:</p>
                    <ul>
                        <li>Full Name</li>
                        <li>Email Address</li>
                        <li>Contact Number</li>
                        <li>Company/Organization Name (if applicable)</li>
                        <li>Any other information you provide voluntarily</li>
                    </ul>

                    <h3>b. Technical and Usage Information</h3>
                    <p>We may collect:</p>
                    <ul>
                        <li>IP address</li>
                        <li>Browser type and version</li>
                        <li>Device information</li>
                        <li>Pages visited and time spent</li>
                        <li>Referring website</li>
                        <li>Cookies and other tracking technologies</li>
                    </ul>
                </section>

                <section>
                    <h2><span class="num">2.</span> How We Use Your Information</h2>
                    <ul>
                        <li>Respond to your inquiries and provide customer service</li>
                        <li>Process transactions or service requests</li>
                        <li>Improve our website's functionality and content</li>
                        <li>Send you company updates or promotional materials (if you have opted in)</li>
                        <li>Comply with applicable laws and regulations</li>
                    </ul>
                </section>

                <section>
                    <h2><span class="num">3.</span> Sharing Your Information</h2>
                    <p>We do not sell or trade your personal information. We may share your data with:</p>
                    <ul>
                        <li>Authorized employees and agents for business purposes</li>
                        <li>Trusted third-party service providers (e.g., hosting, analytics)</li>
                        <li>Government authorities if required by law</li>
                    </ul>
                </section>

                <section>
                    <h2><span class="num">4.</span> Cookies and Tracking</h2>
                    <p>Our website uses cookies and other technologies to enhance your browsing experience. You may choose to disable cookies through your browser settings, but this may affect functionality.</p>
                </section>

                <section>
                    <h2><span class="num">5.</span> Data Security</h2>
                    <p>We implement reasonable physical, electronic, and administrative safeguards to protect your data. However, no transmission over the internet is fully secure. Use of the Site is at your own risk.</p>
                </section>

                <section>
                    <h2><span class="num">6.</span> Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Request access to the personal data we hold about you</li>
                        <li>Request correction or deletion of your data</li>
                        <li>Withdraw consent for processing (where applicable)</li>
                        <li>Lodge a complaint with the National Privacy Commission (NPC) of the Philippines</li>
                    </ul>
                    <p>To exercise these rights, contact us at the email address below.</p>
                </section>

                <section>
                    <h2><span class="num">7.</span> Third-Party Links</h2>
                    <p>This Site may contain links to external websites. TDT Powersteel is not responsible for the privacy practices of these third-party sites.</p>
                </section>

                <section>
                    <h2><span class="num">8.</span> Changes to This Policy</h2>
                    <p>We may update this Privacy Policy from time to time. Changes will be posted on this page with an updated effective date.</p>
                </section>

                <section>
                    <h2><span class="num">9.</span> Contact Us</h2>
                    <div class="contact-block">
                        <p>TDT Powersteel</p>
                        <p>Website: <a href="https://tdtpowersteel.com.ph" target="_blank" rel="noopener noreferrer">tdtpowersteel.com.ph</a></p>
                        <p>Email: <a href="mailto:inquiry@tdtpowersteel.com.ph">inquiry@tdtpowersteel.com.ph</a></p>
                        <p>Phone: 0932 888 7777</p>
                    </div>
                </section>

            </div>
        </div>
    </section>
@endsection
