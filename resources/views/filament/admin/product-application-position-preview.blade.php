@php
    $anchor = $anchor ?? null;
    $thumbnail = $thumbnail ?? null;
@endphp

{{--
    Drag-to-position picker for a Product Application, rendered inside the
    Filament form via Filament\Schemas\Components\View.

    The circle and the end of the leader line are dragged directly on the
    artwork. Dragging updates local Alpine state only; the new values are
    committed to the Livewire form once, on pointer release, so a drag costs
    a single round-trip and the numeric inputs stay the precise, accessible
    path to the same values.

    `$position` / `$anchor` are MapPosition values built from the live form
    state; `$thumbnail` is the uploaded image, when there is one.
--}}
<div class="tdt-position-picker"
     x-data="{
        x: {{ $position->x() }},
        y: {{ $position->y() }},
        ax: {{ $anchor ? $anchor->x() : 'null' }},
        ay: {{ $anchor ? $anchor->y() : 'null' }},
        dragging: null,
        round(value) {
            return Math.round(value * 100) / 100;
        },
        clamp(value) {
            return Math.max(0, Math.min(100, value));
        },
        point(event) {
            const rect = this.$refs.map.getBoundingClientRect();

            return {
                x: this.clamp(((event.clientX - rect.left) / rect.width) * 100),
                y: this.clamp(((event.clientY - rect.top) / rect.height) * 100),
                rect: rect,
            };
        },
        begin(event) {
            const p = this.point(event);
            const px = event.clientX - p.rect.left;
            const py = event.clientY - p.rect.top;
            const dx = px - (this.x / 100) * p.rect.width;
            const dy = py - (this.y / 100) * p.rect.height;
            const dCircle = Math.hypot(dx, dy);
            const dAnchor = this.ax === null
                ? Infinity
                : Math.hypot(
                    px - (this.ax / 100) * p.rect.width,
                    py - (this.ay / 100) * p.rect.height,
                );

            this.dragging = dAnchor < dCircle ? 'anchor' : 'circle';

            if (this.$refs.map.setPointerCapture) {
                this.$refs.map.setPointerCapture(event.pointerId);
            }

            this.move(event);
            event.preventDefault();
        },
        move(event) {
            if (this.dragging === null) {
                return;
            }

            const p = this.point(event);

            if (this.dragging === 'circle') {
                this.x = this.round(p.x);
                this.y = this.round(p.y);
            } else {
                this.ax = this.round(p.x);
                this.ay = this.round(p.y);
            }
        },
        finish(event) {
            if (this.dragging === null) {
                return;
            }

            this.dragging = null;

            if (event && this.$refs.map.releasePointerCapture) {
                try {
                    this.$refs.map.releasePointerCapture(event.pointerId);
                } catch (e) {}
            }

            this.$wire.set('data.x', this.x);
            this.$wire.set('data.y', this.y);

            if (this.ax !== null) {
                this.$wire.set('data.anchor_x', this.ax);
                this.$wire.set('data.anchor_y', this.ay);
            }
        },
        addLine() {
            this.ax = this.round(this.x < 50 ? Math.min(100, this.x + 12) : Math.max(0, this.x - 12));
            this.ay = this.round(this.y);
            this.$wire.set('data.anchor_x', this.ax);
            this.$wire.set('data.anchor_y', this.ay);
        },
        removeLine() {
            this.ax = null;
            this.ay = null;
            this.$wire.set('data.anchor_x', null);
            this.$wire.set('data.anchor_y', null);
        },
     }">
    <div class="tdt-position-picker-map"
         x-ref="map"
         x-on:pointerdown="begin($event)"
         x-on:pointermove="move($event)"
         x-on:pointerup="finish($event)"
         x-on:pointercancel="finish($event)">
        <img src="/static/images/facility-map.png"
             alt=""
             class="tdt-position-picker-art"
             draggable="false"
             loading="lazy"
             decoding="async">

        <svg class="tdt-position-picker-lines"
             viewBox="0 0 100 100"
             preserveAspectRatio="none"
             aria-hidden="true"
             focusable="false">
            <line x-bind:x1="x"
                  x-bind:y1="y"
                  x-bind:x2="ax"
                  x-bind:y2="ay"
                  x-show="ax !== null" />
        </svg>

        <span class="tdt-position-picker-handle{{ $position->isWithinMap() ? '' : ' is-outside' }}"
              style="left: {{ $position->xPercent() }}%; top: {{ $position->yPercent() }}%;"
              x-bind:style="`left: ${x}%; top: ${y}%`"
              title="Drag to move the circle">
            @if ($thumbnail)
            <img src="{{ $thumbnail }}" alt="" class="tdt-position-picker-thumb" draggable="false">
            @endif
        </span>

        <span class="tdt-position-picker-anchor"
              style="left: {{ $anchor ? $anchor->xPercent() : $position->xPercent() }}%; top: {{ $anchor ? $anchor->yPercent() : $position->yPercent() }}%;"
              x-bind:style="`left: ${ax === null ? x : ax}%; top: ${ay === null ? y : ay}%`"
              x-show="ax !== null"
              title="Drag to move the end of the leader line"></span>
    </div>

    <div class="tdt-position-picker-bar">
        <p class="tdt-position-picker-caption{{ $position->isWithinMap() ? '' : ' is-outside' }}">
            @if ($position->isWithinMap())
                Circle at {{ $position->xPercent() }}% / {{ $position->yPercent() }}%
            @else
                {{ $position->rawXPercent() }}% / {{ $position->rawYPercent() }}% is outside the map. Enter values between 0 and 100.
            @endif
            @if ($anchor)
                &middot; Leader line to {{ $anchor->xPercent() }}% / {{ $anchor->yPercent() }}%
            @endif
        </p>

        <div class="tdt-position-picker-actions">
            <button type="button"
                    class="tdt-position-picker-btn"
                    x-show="ax === null"
                    x-on:click="addLine()">Add leader line</button>
            <button type="button"
                    class="tdt-position-picker-btn"
                    x-show="ax !== null"
                    x-on:click="removeLine()">Remove leader line</button>
        </div>
    </div>
</div>
