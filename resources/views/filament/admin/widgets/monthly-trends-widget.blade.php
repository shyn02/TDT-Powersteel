<x-filament-widgets::widget>
    <div class="tdt-chart-card">
        <div class="tdt-chart-header">
            <h3 class="tdt-chart-title">Monthly Inquiry Trends</h3>
            <div class="tdt-chart-legend">
                <span class="tdt-legend-item"><span class="tdt-legend-dot" style="background:#f59e0b"></span>Quotes</span>
                <span class="tdt-legend-item"><span class="tdt-legend-dot" style="background:#0ea5e9"></span>Referrals</span>
                <span class="tdt-legend-item"><span class="tdt-legend-dot" style="background:#10b981"></span>Contacts</span>
            </div>
        </div>
        <div class="tdt-chart-body">
            @php $max = max(1, $this->getMaxCount()); @endphp
            <div class="tdt-bars-container">
                @foreach ($this->getMonthlyData() as $month)
                    <div class="tdt-bars-col">
                        <div class="tdt-bars-stack">
                            <div class="tdt-bars-group">
                                <div class="tdt-bar-col-item" style="height: {{ $month['quotes'] > 0 ? max(4, ($month['quotes'] / $max) * 100) : 0 }}%; background: #f59e0b;" title="{{ $month['quotes'] }} quotes"></div>
                                <div class="tdt-bar-col-item" style="height: {{ $month['referrals'] > 0 ? max(4, ($month['referrals'] / $max) * 100) : 0 }}%; background: #0ea5e9;" title="{{ $month['referrals'] }} referrals"></div>
                                <div class="tdt-bar-col-item" style="height: {{ $month['contacts'] > 0 ? max(4, ($month['contacts'] / $max) * 100) : 0 }}%; background: #10b981;" title="{{ $month['contacts'] }} contacts"></div>
                            </div>
                        </div>
                        <span class="tdt-bars-label">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
