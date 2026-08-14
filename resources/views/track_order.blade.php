@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="/static/subpages.css">
<link rel="stylesheet" href="/static/tracking.css">
@endpush

@section('title', 'Track Order | TDT Powersteel Corporation')
@section('description', 'Track the status of your steel order or shipment with TDT Powersteel Corporation.')

@section('content')

    <section class="tracking-hero">
        <div class="tracking-hero-bg" style="background-image: url('/static/images/metaltruck.jpg');" aria-hidden="true"></div>
        <div class="container tracking-hero-content">
            <h1 class="tracking-hero-title">TRACK SHIPMENT</h1>
            <p class="tracking-hero-desc">Real-time logistics monitoring for your steel projects. Enter your tracking ID to see your order status.</p>
        </div>
    </section>

    <div class="container">
        <form id="trackingForm" class="tracking-search-bar">
            <input type="text" id="orderNumber" required placeholder="Enter Tracking Number (e.g. TDT-2024-XXXX)">
            <button type="submit" class="btn-orange" id="trackingSubmitBtn">
                <span class="btn-text">TRACK SHIPMENT</span>
                <span class="spinner"></span>
            </button>
        </form>
    </div>

    <section class="page-section tracking-dashboard-section">
        <div class="container">

            <div class="tracking-tabs">
                <button type="button" class="tracking-tab active" data-tab="current">Current Shipments</button>
                <button type="button" class="tracking-tab" data-tab="past">Past Orders</button>
                <button type="button" class="tracking-tab" data-tab="invoices">Pending Invoices</button>
            </div>

            <div class="tracking-demo-note">
                <p>This is a sample preview using demo data. Live order tracking isn't connected to a backend yet — <a href="{{ route('contact') }}">contact our sales team</a> for the real-time status of your order.</p>
            </div>

            <div id="trackingResult"></div>

            <div id="tabPanel-current" class="tracking-tab-panel">
                <div class="tracking-dashboard">
                    <div class="order-card">
                        <div class="order-card-header">
                            <div>
                                <span class="order-card-label">Order ID</span>
                                <h3 id="displayOrderId">TDT-2024-8932</h3>
                            </div>
                            <span class="status-badge in-transit">IN TRANSIT</span>
                        </div>

                        <div class="order-card-block">
                            <span class="order-card-label">Items</span>
                            <ul class="order-items-list">
                                <li><span>Structural Steel I-Beams</span><span class="item-qty">x 24 Units</span></li>
                                <li><span>Cold Rolled Sheets (2mm)</span><span class="item-qty">x 12 Rolls</span></li>
                            </ul>
                        </div>

                        <div class="order-card-block">
                            <span class="order-card-label">Destination</span>
                            <p>Quezon City Metro Project Site A, Phase 2 Construction, NCR.</p>
                        </div>

                        <div class="order-card-block">
                            <span class="order-card-label">Estimated Arrival</span>
                            <p class="eta-date">July 22, 2026 (By 4:00 PM)</p>
                        </div>

                        <div class="order-actions">
                            <a href="#" class="order-action-link">
                                <span><i class="fas fa-file-invoice"></i> Download Invoice</span>
                                <span class="chevron">›</span>
                            </a>
                            <a href="#" class="order-action-link">
                                <span><i class="fas fa-receipt"></i> View Delivery Receipt</span>
                                <span class="chevron">›</span>
                            </a>
                            <a href="{{ route('contact') }}" class="order-action-link">
                                <span><i class="fas fa-phone-alt"></i> Contact Logistics</span>
                                <span class="chevron">›</span>
                            </a>
                        </div>
                    </div>

                    <div class="milestone-card">
                        <h3 class="milestone-heading"><i class="fas fa-map-marker-alt"></i> Milestone Tracking</h3>

                        <div class="milestone-timeline">
                            <div class="milestone-item current">
                                <span class="milestone-dot"></span>
                                <div class="milestone-content">
                                    <span class="milestone-stage-label">Current Stage</span>
                                    <h4>Out for Delivery</h4>
                                    <span class="milestone-date">July 20, 2026 · 09:15 AM</span>
                                    <p class="milestone-note">Shipment has left the TDT Logistics Hub in Valenzuela and is en route to the site. Vehicle: ISUZU Forward 6-Wheeler (NCR-482).</p>
                                </div>
                            </div>

                            <div class="milestone-item done">
                                <span class="milestone-dot"></span>
                                <div class="milestone-content">
                                    <h4>Quality Assurance Passed</h4>
                                    <span class="milestone-date">July 19, 2026 · 04:30 PM</span>
                                    <p>Final structural inspection completed. Certification document #CER-8932-A issued.</p>
                                </div>
                            </div>

                            <div class="milestone-item done">
                                <span class="milestone-dot"></span>
                                <div class="milestone-content">
                                    <h4>Order Processed &amp; Loaded</h4>
                                    <span class="milestone-date">July 19, 2026 · 11:00 AM</span>
                                    <p>Structural I-Beams and Cold Rolled Sheets loaded onto logistics fleet.</p>
                                </div>
                            </div>

                            <div class="milestone-item done">
                                <span class="milestone-dot"></span>
                                <div class="milestone-content">
                                    <h4>Order Confirmed</h4>
                                    <span class="milestone-date">July 18, 2026 · 02:20 PM</span>
                                    <p>Payment verified. Order transmitted to central inventory.</p>
                                </div>
                            </div>
                        </div>

                        <div class="tracking-map-preview">
                            <span class="map-pin">📍</span>
                            <h4>Tracking Live Location</h4>
                            <span class="map-sublabel">Valenzuela City → North Expressway</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tabPanel-past" class="tracking-tab-panel" hidden>
                <div class="tracking-empty-state">
                    <span>📦</span>
                    <p>No past orders to show in this demo yet.</p>
                </div>
            </div>

            <div id="tabPanel-invoices" class="tracking-tab-panel" hidden>
                <div class="tracking-empty-state">
                    <span>🧾</span>
                    <p>No pending invoices to show in this demo yet.</p>
                </div>
            </div>

        </div>
    </section>

    <section class="about-cta-banner">
        <div class="container">
            <h2>CAN'T FIND YOUR ORDER?</h2>
            <p>Reach out to our sales team directly and we'll look it up for you.</p>
            <a href="{{ route('contact') }}" class="btn-cta-white">CONTACT US</a>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    // Tab switching (Current Shipments / Past Orders / Pending Invoices)
    (function () {
        const tabs = document.querySelectorAll('.tracking-tab');
        const panels = {
            current: document.getElementById('tabPanel-current'),
            past: document.getElementById('tabPanel-past'),
            invoices: document.getElementById('tabPanel-invoices'),
        };
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                Object.keys(panels).forEach(function (key) {
                    panels[key].hidden = (key !== tab.dataset.tab);
                });
            });
        });
    })();

    // Order lookup — demo only, no backend yet.
    (function () {
        const form = document.getElementById('trackingForm');
        const result = document.getElementById('trackingResult');
        const displayOrderId = document.getElementById('displayOrderId');
        if (!form || form.dataset.bound) return;
        form.dataset.bound = "1";

        const DEMO_ORDER_ID = 'TDT-2024-8932';

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const entered = document.getElementById('orderNumber').value.trim();

            if (entered.toUpperCase() === DEMO_ORDER_ID) {
                result.innerHTML = '';
                result.classList.remove('active');
            } else {
                displayOrderId.textContent = entered || DEMO_ORDER_ID;
                result.innerHTML =
                    '<div class="tracking-lookup-note">' +
                    '<p>We couldn\'t find live data for <strong>' + entered.replace(/</g, '&lt;') + '</strong> — online tracking is still in demo mode. ' +
                    'Showing sample order <strong>' + DEMO_ORDER_ID + '</strong> below. ' +
                    '<a href="{{ route('contact') }}">Contact our sales team</a> for your order\'s real status.</p>' +
                    '</div>';
                result.classList.add('active');
            }

            document.getElementById('tabPanel-current').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    })();
</script>
@endpush
