@php $data = $this->getData(); @endphp
<x-filament-widgets::widget>
    <div class="tdt-status-card">
        <div class="tdt-status-header">
            <h3 class="tdt-status-title">Quote Requests</h3>
            <span class="tdt-status-total">{{ $data['total'] }} total</span>
        </div>
        <div class="tdt-status-body">

            {{-- Stacked bar --}}
            <div class="tdt-stacked-bar">
                @foreach ($data['segments'] as $seg)
                    <div class="tdt-stacked-seg" style="width: {{ $seg['pct'] }}%; background: {{ $seg['color'] }};" title="{{ $seg['label'] }}: {{ $seg['count'] }} ({{ $seg['pct'] }}%)"></div>
                @endforeach
            </div>

            {{-- Inline legend --}}
            <div class="tdt-inline-legend">
                @foreach ($data['segments'] as $seg)
                    <div class="tdt-inline-item">
                        <span class="tdt-inline-dot" style="background: {{ $seg['color'] }}"></span>
                        <span class="tdt-inline-label">{{ $seg['label'] }}</span>
                        <span class="tdt-inline-count">{{ $seg['count'] }}</span>
                        <span class="tdt-inline-pct">{{ $seg['pct'] }}%</span>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-filament-widgets::widget>
