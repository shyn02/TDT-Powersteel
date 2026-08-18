<x-filament-widgets::widget>
    <div class="tdt-chart-card">
        <div class="tdt-chart-header">
            <h3 class="tdt-chart-title">Top Product Categories</h3>
            <span class="tdt-chart-subtitle">By open quote requests</span>
        </div>
        <div class="tdt-chart-body">
            @forelse ($this->getTopCategories() as $cat)
                <div class="tdt-cat-row">
                    <div class="tdt-cat-info">
                        <span class="tdt-cat-name">{{ $cat['name'] }}</span>
                        <span class="tdt-cat-count">{{ $cat['open'] }} open / {{ $cat['total'] }} total</span>
                    </div>
                    <div class="tdt-cat-bar-track">
                        <div class="tdt-cat-bar-fill" style="width: {{ $cat['percent'] }}%"></div>
                    </div>
                </div>
            @empty
                <p class="tdt-recent-empty">No category data yet.</p>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
