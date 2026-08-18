@php $data = $this->getData(); @endphp
<x-filament-widgets::widget>
    <div class="tdt-status-card">
        <div class="tdt-status-header">
            <h3 class="tdt-status-title">Quote Request Status</h3>
            <span class="tdt-status-total">{{ $data['total'] }} total</span>
        </div>
        <div class="tdt-status-body">
            {{-- Donut ring via conic-gradient --}}
            @php
                $gradientParts = [];
                $accumulated = 0;
                foreach ($data['segments'] as $seg) {
                    if ($seg['count'] > 0) {
                        $start = $accumulated;
                        $end = $accumulated + $seg['pct'];
                        $gradientParts[] = "{$seg['color']} {$start}% {$end}%";
                        $accumulated = $end;
                    }
                }
                if (empty($gradientParts)) {
                    $gradientParts[] = '#e5e7eb 0% 100%';
                }
                $gradient = implode(', ', $gradientParts);
            @endphp
            <div class="tdt-donut-wrap">
                <div class="tdt-donut" style="background: conic-gradient({{ $gradient }});">
                    <div class="tdt-donut-hole">
                        <span class="tdt-donut-total">{{ $data['total'] }}</span>
                        <span class="tdt-donut-label">Total</span>
                    </div>
                </div>
            </div>

            {{-- Legend + percentage bars --}}
            <div class="tdt-status-legend">
                @foreach ($data['segments'] as $seg)
                    <div class="tdt-legend-row">
                        <div class="tdt-legend-top">
                            <span class="tdt-legend-dot" style="background: {{ $seg['color'] }}"></span>
                            <span class="tdt-legend-name">{{ $seg['label'] }}</span>
                            <span class="tdt-legend-val">{{ $seg['count'] }}</span>
                        </div>
                        <div class="tdt-legend-bar-track">
                            <div class="tdt-legend-bar-fill" style="width: {{ $seg['pct'] }}%; background: {{ $seg['color'] }}"></div>
                        </div>
                        <span class="tdt-legend-pct">{{ $seg['pct'] }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
