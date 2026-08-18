@php $counts = $this->getActionCounts(); @endphp
<x-filament-widgets::widget>
    <div class="tdt-quick-actions">
        <h3 class="tdt-quick-actions-title">Quick Actions</h3>
        <div class="tdt-quick-actions-grid">
            <a href="{{ $this->getQuoteUrl() }}" class="tdt-quick-action-btn tdt-qa-amber">
                <div class="tdt-qa-icon-wrap">
                    <x-heroicon-o-document-text class="w-6 h-6" />
                </div>
                <div class="tdt-qa-text">
                    <span class="tdt-qa-count">{{ $counts['new_quotes'] }}</span>
                    <span class="tdt-qa-label">New Quotes</span>
                </div>
            </a>
            <a href="{{ $this->getReferralUrl() }}" class="tdt-quick-action-btn tdt-qa-sky">
                <div class="tdt-qa-icon-wrap">
                    <x-heroicon-o-user-group class="w-6 h-6" />
                </div>
                <div class="tdt-qa-text">
                    <span class="tdt-qa-count">{{ $counts['new_referrals'] }}</span>
                    <span class="tdt-qa-label">New Referrals</span>
                </div>
            </a>
            <a href="{{ $this->getContactUrl() }}" class="tdt-quick-action-btn tdt-qa-rose">
                <div class="tdt-qa-icon-wrap">
                    <x-heroicon-o-envelope class="w-6 h-6" />
                </div>
                <div class="tdt-qa-text">
                    <span class="tdt-qa-count">{{ $counts['unread_contacts'] }}</span>
                    <span class="tdt-qa-label">Unread Messages</span>
                </div>
            </a>
        </div>
    </div>
</x-filament-widgets::widget>
