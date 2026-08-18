<x-filament-widgets::widget>
    <div class="tdt-chart-card">
        <div class="tdt-chart-header">
            <h3 class="tdt-chart-title">Geographic Breakdown</h3>
            <div class="tdt-chart-meta">
                <span class="tdt-meta-pill">{{ $this->getUniqueRegions() }} regions</span>
                <span class="tdt-meta-pill">{{ $this->getTotalReferrals() }} referrals</span>
            </div>
        </div>
        <div class="tdt-chart-body">
            @forelse ($this->getRegionalData() as $region)
                <div class="tdt-geo-row">
                    <div class="tdt-geo-info">
                        <span class="tdt-geo-name">{{ $region['name'] }}</span>
                        <span class="tdt-geo-count">{{ $region['count'] }} ({{ $region['percent'] }}%)</span>
                    </div>
                    <div class="tdt-geo-bar-track">
                        <div class="tdt-geo-bar-fill" style="width: {{ $region['bar_width'] }}%"></div>
                    </div>
                </div>
            @empty
                <p class="tdt-recent-empty">No geographic data yet. Referrals with a region field will appear here.</p>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
