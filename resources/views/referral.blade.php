@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="/static/subpages.css">
<link rel="stylesheet" href="/static/referral.css">
@endpush

@section('title', 'Refer a Project | TDT Powersteel Corporation')
@section('description', 'Refer a project to TDT Powersteel and get rewarded once it closes. Tell us about the client, the project, and we\'ll take it from there.')

@section('content')

    <section class="referral-hero">
        <div class="referral-hero-inner container">
            <div class="referral-breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span class="sep">/</span>
                <span class="current">Refer a Project</span>
            </div>
            <h1 class="referral-hero-title">Refer a Project, <span>Get Rewarded</span></h1>
            <p class="referral-hero-desc">Know a contractor, developer, or business that needs steel? Refer them to TDT Powersteel — once their project closes, you get rewarded for the introduction.</p>
        </div>
    </section>

    <section class="referral-steps-section">
        <div class="container">
            <div class="referral-steps-grid">
                <div class="referral-step-card">
                    <span class="referral-step-number">1</span>
                    <div class="referral-step-copy">
                        <h3>Submit the Referral</h3>
                        <p>Fill out the form below with your details and the project you're referring to us.</p>
                    </div>
                </div>
                <div class="referral-step-card">
                    <span class="referral-step-number">2</span>
                    <div class="referral-step-copy">
                        <h3>We Reach Out</h3>
                        <p>Our sales team contacts the referred company to discuss their steel requirements.</p>
                    </div>
                </div>
                <div class="referral-step-card">
                    <span class="referral-step-number">3</span>
                    <div class="referral-step-copy">
                        <h3>Get Rewarded</h3>
                        <p>Once the referred project is confirmed and closed, we get in touch with you about your reward.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="referral-form-section">
        <div class="container">
            <div class="referral-form-card">
                <form id="referralForm">
                    @csrf
                    <div style="position:absolute;left:-5000px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                        <label for="ref_website_hp">Leave this field empty</label>
                        <input type="text" name="website" id="ref_website_hp" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="referral-fieldset">
                        <div class="referral-fieldset-title"><i class="fas fa-user"></i> Your Information</div>
                        <div class="referral-form-grid">
                            <div class="referral-field">
                                <label for="ref_fullname">Full Name *</label>
                                <input type="text" name="ref_fullname" id="ref_fullname" required placeholder="Your Name">
                            </div>
                            <div class="referral-field">
                                <label for="ref_company">Company Name</label>
                                <input type="text" name="ref_company" id="ref_company" placeholder="Your Company (optional)">
                            </div>
                            <div class="referral-field">
                                <label for="ref_phone">Mobile Number *</label>
                                <input type="tel" name="ref_phone" id="ref_phone" required placeholder="+63 000 000 0000">
                            </div>
                            <div class="referral-field">
                                <label for="ref_email">Email Address *</label>
                                <input type="email" name="ref_email" id="ref_email" required placeholder="email@example.com">
                            </div>
                        </div>
                    </div>

                    <div class="referral-fieldset">
                        <div class="referral-fieldset-title"><i class="fas fa-building"></i> Project You're Referring</div>
                        <div class="referral-form-grid">
                            <div class="referral-field">
                                <label for="ref_contact_person">Contact Person *</label>
                                <input type="text" name="ref_contact_person" id="ref_contact_person" required placeholder="Name of the person we should contact">
                            </div>
                            <div class="referral-field">
                                <label for="ref_referred_company">Referred Company *</label>
                                <input type="text" name="ref_referred_company" id="ref_referred_company" required placeholder="Company or project owner">
                            </div>
                            <div class="referral-field">
                                <label for="ref_project_type">Project Type *</label>
                                <select name="ref_project_type" id="ref_project_type" required>
                                    <option value="">-- Select project type --</option>
                                    <option value="Residential">Residential</option>
                                    <option value="Commercial">Commercial</option>
                                    <option value="Industrial">Industrial</option>
                                    <option value="Infrastructure">Infrastructure</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="referral-field">
                                <label for="ref_project_scale">Estimated Project Scale *</label>
                                <select name="ref_project_scale" id="ref_project_scale" required>
                                    <option value="">-- Select scale --</option>
                                    <option value="Small">Small</option>
                                    <option value="Medium">Medium</option>
                                    <option value="Large">Large</option>
                                    <option value="Not sure yet">Not sure yet</option>
                                </select>
                            </div>
                            <div class="referral-field">
                                <label for="ref_region">Project Location / Region *</label>
                                <input type="text" name="ref_region" id="ref_region" required placeholder="e.g. Metro Manila, Cebu, Davao">
                            </div>
                        </div>
                        <div class="referral-form-grid single" style="margin-top: 20px;">
                            <div class="referral-field">
                                <label for="ref_remarks">Additional Notes</label>
                                <textarea name="ref_remarks" id="ref_remarks" placeholder="Anything else we should know about this project or the referred company..."></textarea>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="referral-submit-btn" id="referralSubmitBtn">
                        <span class="btn-text"><i class="fas fa-paper-plane"></i> Submit Referral</span>
                        <span class="spinner"></span>
                    </button>
                </form>
            </div>
        </div>
    </section>

@endsection
