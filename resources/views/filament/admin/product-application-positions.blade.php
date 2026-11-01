<x-filament-panels::page>
    <div class="tdt-positions-editor"
         x-data="{
            items: {{ \Illuminate\Support\Js::from($items) }},
            dragging: null,
            saving: false,
            item(id) {
                return this.items.find(i => i.id === id);
            },
            coord(id, key) {
                const item = this.item(id);

                return item ? item[key] : 0;
            },
            point(event) {
                const rect = this.$refs.map.getBoundingClientRect();
                const clamp = v => Math.max(0, Math.min(100, v));

                return {
                    x: clamp(((event.clientX - rect.left) / rect.width) * 100),
                    y: clamp(((event.clientY - rect.top) / rect.height) * 100),
                    rect: rect,
                };
            },
            begin(event, id, kind) {
                this.dragging = { id: id, kind: kind };

                if (this.$refs.map.setPointerCapture) {
                    this.$refs.map.setPointerCapture(event.pointerId);
                }

                this.move(event);
            },
            move(event) {
                if (!this.dragging) {
                    return;
                }

                const item = this.item(this.dragging.id);

                if (!item) {
                    return;
                }

                const p = this.point(event);
                const round = v => Math.round(v * 100) / 100;

                if (this.dragging.kind === 'circle') {
                    item.x = round(p.x);
                    item.y = round(p.y);
                } else {
                    item.ax = round(p.x);
                    item.ay = round(p.y);
                }
            },
            finish(event) {
                if (!this.dragging) {
                    return;
                }

                this.dragging = null;

                if (event && this.$refs.map.releasePointerCapture) {
                    try {
                        this.$refs.map.releasePointerCapture(event.pointerId);
                    } catch (e) {}
                }
            },
            async save() {
                this.saving = true;

                try {
                    const saved = await this.$wire.save(this.items);

                    if (Array.isArray(saved)) {
                        this.items = saved;
                    }
                } finally {
                    this.saving = false;
                }
            },
         }">
        <div class="tdt-positions-editor-toolbar">
            <p class="tdt-positions-editor-hint">
                Drag any circle, or the small dot at the end of its line. Nothing changes on the site until you save.
            </p>

            <x-filament::button color="primary" x-on:click="save()" x-bind:disabled="saving">
                <span x-show="!saving">Save positions</span>
                <span x-show="saving" style="display: none;">Saving&hellip;</span>
            </x-filament::button>
        </div>

        <div class="tdt-positions-editor-map"
             x-ref="map"
             x-on:pointermove="move($event)"
             x-on:pointerup="finish($event)"
             x-on:pointercancel="finish($event)">
            <img src="/static/images/facility-map.png"
                 alt=""
                 class="tdt-positions-editor-art"
                 draggable="false"
                 loading="lazy"
                 decoding="async">

            <svg class="tdt-positions-editor-lines"
                 viewBox="0 0 100 100"
                 preserveAspectRatio="none"
                 aria-hidden="true"
                 focusable="false">
                @foreach ($items as $item)
                @if ($item['ax'] !== null && $item['ay'] !== null)
                <line x1="{{ $item['x'] }}"
                      y1="{{ $item['y'] }}"
                      x-bind:x1="coord({{ $item['id'] }}, 'x')"
                      x-bind:y1="coord({{ $item['id'] }}, 'y')"
                      x-bind:x2="coord({{ $item['id'] }}, 'ax')"
                      x-bind:y2="coord({{ $item['id'] }}, 'ay')" />
                @endif
                @endforeach
            </svg>

            @foreach ($items as $item)
            <div class="tdt-positions-editor-item"
                 style="left: {{ $item['x'] }}%; top: {{ $item['y'] }}%;"
                 x-bind:style="`left:${coord({{ $item['id'] }}, 'x')}%; top:${coord({{ $item['id'] }}, 'y')}%`"
                 x-bind:class="coord({{ $item['id'] }}, 'x') < 50 ? 'is-left' : 'is-right'">
                <span class="tdt-positions-editor-circle"
                      x-on:pointerdown.stop.prevent="begin($event, {{ $item['id'] }}, 'circle')">
                    @if ($item['thumbnail'])
                    <img src="{{ $item['thumbnail'] }}" alt="" draggable="false">
                    @endif
                </span>
                <span class="tdt-positions-editor-label">{{ $item['label'] }}</span>
            </div>

            @if ($item['ax'] !== null && $item['ay'] !== null)
            <span class="tdt-positions-editor-anchor"
                  style="left: {{ $item['ax'] }}%; top: {{ $item['ay'] }}%;"
                  x-bind:style="`left:${coord({{ $item['id'] }}, 'ax')}%; top:${coord({{ $item['id'] }}, 'ay')}%`"
                  x-on:pointerdown.stop.prevent="begin($event, {{ $item['id'] }}, 'anchor')"></span>
            @endif
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
