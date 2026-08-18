<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Quote Aging --}}
        <div class="tdt-chart-card">
            <div class="tdt-chart-header">
                <h3 class="tdt-chart-title">Quote Aging</h3>
                <span class="tdt-chart-subtitle">Unanswered quotes by age</span>
            </div>
            <div class="tdt-chart-body">
                @php $aging = $this->getQuoteAgingData(); @endphp
                @foreach ($aging as $bucket)
                    <div class="tdt-aging-row">
                        <div class="tdt-aging-info">
                            <span class="tdt-aging-dot {{ $bucket['color'] }}"></span>
                            <span class="tdt-aging-label">{{ $bucket['label'] }}</span>
                        </div>
                        <div class="tdt-aging-right">
                            <span class="tdt-aging-count">{{ $bucket['count'] }}</span>
                            <span class="tdt-aging-pct">{{ $bucket['percent'] }}%</span>
                        </div>
                    </div>
                @endforeach
                @if ($this->getUnresolvedTickets() > 0)
                    <div class="tdt-aging-alert">
                        <span class="tdt-aging-alert-icon">!</span>
                        {{ $this->getUnresolvedTickets() }} quote(s) older than 7 days still unanswered
                    </div>
                @endif
            </div>
        </div>

        {{-- Chat Response Time --}}
        <div class="tdt-chart-card">
            <div class="tdt-chart-header">
                <h3 class="tdt-chart-title">Chat Response</h3>
                <span class="tdt-chart-subtitle">Last 30 days</span>
            </div>
            <div class="tdt-chart-body tdt-chart-body-center">
                <div class="tdt-response-big-number">
                    {{ $this->getAvgChatResponseTime() }}
                </div>
                <div class="tdt-response-label">Avg. first response time</div>
                <div class="tdt-response-sublabel">From session creation to first staff reply</div>
            </div>
        </div>

        {{-- Status Summary --}}
        <div class="tdt-chart-card">
            <div class="tdt-chart-header">
                <h3 class="tdt-chart-title">Pipeline Status</h3>
                <span class="tdt-chart-subtitle">All inquiries</span>
            </div>
            <div class="tdt-chart-body">
                @php
                    $quotesNew = \App\Models\QuoteRequest::where('status', 'new')->count();
                    $quotesContacted = \App\Models\QuoteRequest::where('status', 'contacted')->count();
                    $quotesClosed = \App\Models\QuoteRequest::where('status', 'closed')->count();
                    $referralsNew = \App\Models\Referral::where('status', 'new')->count();
                    $contactsUnread = \App\Models\ContactMessage::where('is_seen', false)->count();
                    $totalAll = $quotesNew + $quotesContacted + $quotesClosed + $referralsNew + $contactsUnread;
                @endphp
                <div class="tdt-pipeline-items">
                    <div class="tdt-pipeline-item">
                        <span class="tdt-pipeline-dot" style="background:#f59e0b"></span>
                        <span class="tdt-pipeline-name">New Quotes</span>
                        <span class="tdt-pipeline-val">{{ $quotesNew }}</span>
                    </div>
                    <div class="tdt-pipeline-item">
                        <span class="tdt-pipeline-dot" style="background:#0ea5e9"></span>
                        <span class="tdt-pipeline-name">Contacted</span>
                        <span class="tdt-pipeline-val">{{ $quotesContacted }}</span>
                    </div>
                    <div class="tdt-pipeline-item">
                        <span class="tdt-pipeline-dot" style="background:#10b981"></span>
                        <span class="tdt-pipeline-name">Closed</span>
                        <span class="tdt-pipeline-val">{{ $quotesClosed }}</span>
                    </div>
                    <div class="tdt-pipeline-item">
                        <span class="tdt-pipeline-dot" style="background:#8b5cf6"></span>
                        <span class="tdt-pipeline-name">New Referrals</span>
                        <span class="tdt-pipeline-val">{{ $referralsNew }}</span>
                    </div>
                    <div class="tdt-pipeline-item">
                        <span class="tdt-pipeline-dot" style="background:#f43f5e"></span>
                        <span class="tdt-pipeline-name">Unread Messages</span>
                        <span class="tdt-pipeline-val">{{ $contactsUnread }}</span>
                    </div>
                </div>
                <div class="tdt-pipeline-total">
                    <span>Total Pipeline</span>
                    <span class="tdt-pipeline-total-val">{{ $totalAll }}</span>
                </div>
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
