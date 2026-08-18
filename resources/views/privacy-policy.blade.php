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

                <p class="legal-intro">TDT Powersteel Corporation whose registered address is 1017 Vicente Cruz St, Sampaloc, Manila, 1008 Metro Manila ("We") are committed to protecting and preserving the privacy of our visitors when visiting our site or communicating electronically with us.</p>
                <p class="legal-intro">This policy sets out how we process any personal data we collect from you or that you provide to us through our website and social media sites. We confirm that we will keep your information secure and that we will comply fully with all applicable Philippines Data Protection legislation and regulations. Please read the following carefully to understand what happens to personal data that you choose to provide to us, or that we collect from you when you visit our sites. By submitting information you are accepting and consenting to the practices described in this policy.</p>

                <section>
                    <h2>Types of Information We May Collect From You</h2>
                    <p>We may collect, store, and use the following kinds of personal information about individuals who visit and use our website and social media sites:</p>
                    <p><strong>Information you supply to us.</strong> You may supply us with information about you by filling in forms on our website or social media. This includes information you provide when you submit a contact/enquiry form. The information you give us may include, but not limited to, your name, address, e-mail address and phone number.</p>
                </section>

                <section>
                    <h2>How We May Use the Information We Collect</h2>
                    <p>We use the information in the following ways:</p>
                    <p><strong>Information you supply to us.</strong> We will use this information:</p>
                    <ul>
                        <li>to provide you with information and/or services that you request from us;</li>
                        <li>To contact you to provide the information requested.</li>
                    </ul>
                </section>

                <section>
                    <h2>Disclosure of Your Information</h2>
                    <p>Any information you provide to us will either be emailed directly to us or may be stored on a secure server.</p>
                    <p>We do not rent, sell or share personal information about you with other people or non-affiliated companies.</p>
                    <p>We will use all reasonable efforts to ensure that your personal data is not disclosed to regional/national institutions and authorities, unless required by law or other regulations.</p>
                    <p>Unfortunately, the transmission of information via the internet is not completely secure. Although we will do our best to protect your personal data, we cannot guarantee the security of your data transmitted to our site; any transmission is at your own risk. Once we have received your information, we will use strict procedures and security features to try to prevent unauthorised access.</p>
                </section>

                <section>
                    <h2>Your Rights &ndash; Access to Your Personal Data</h2>
                    <p>You have the right to ensure that your personal data is being processed lawfully ("Subject Access Right"). Your subject access right can be exercised in accordance with data protection laws and regulations. Any subject access request must be made in writing to TDT Powersteel Corporation. We will provide your personal data to you within the statutory time frames. To enable us to trace any of your personal data that we may be holding, we may need to request further information from you. If you have a complaint about how we have used your information, you have the right to complain to the National Privacy Commission (NPC).</p>
                </section>

                <section>
                    <h2>Changes to Our Privacy Policy</h2>
                    <p>Any changes we may make to our privacy policy in the future will be posted on this page and, where appropriate, notified to you by e-mail. Please check back frequently to see any updates or changes to our privacy policy.</p>
                </section>

                <section>
                    <h2>Contact</h2>
                    <div class="contact-block">
                        <p>Questions, comments, and requests regarding this privacy policy are welcomed and should be addressed to:</p>
                        <p>Jay-ar Bile &ndash; <a href="mailto:jrbile@tdtpowersteel.com.ph">jrbile@tdtpowersteel.com.ph</a></p>
                    </div>
                </section>

            </div>
        </div>
    </section>
@endsection
