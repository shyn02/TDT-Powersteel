@props(['application'])

@php
    $thumbnail = $application->thumbnailUrl();
    $playable = $application->isPlayable();
    $position = $application->position();
@endphp

{{--
    One circle on the Product Applications map.

    A circle with an Application video is a real button carrying its modal
    payload; one without is a plain element, so it is not reachable by
    keyboard and does nothing when clicked.
--}}
@if ($playable)
<button type="button"
        class="product-application"
        data-product-application="{{ $application->id }}"
        data-label-side="{{ $application->labelSide() }}"
        data-video-trigger
        data-video-url="{{ $application->videoUrl() }}"
        data-label="{{ $application->label }}"
        @if (filled($application->description)) data-description="{{ $application->description }}" @endif
        aria-label="Play video: {{ $application->label }}"
        style="left: {{ $position->xPercent() }}%; top: {{ $position->yPercent() }}%;">
@else
<div class="product-application"
     data-product-application="{{ $application->id }}"
     data-label-side="{{ $application->labelSide() }}"
     style="left: {{ $position->xPercent() }}%; top: {{ $position->yPercent() }}%;">
@endif
    <span class="product-application-circle">
        @if ($thumbnail)
        <img src="{{ $thumbnail }}"
             alt=""
             class="product-application-thumb"
             width="116" height="116" loading="lazy" decoding="async">
        @endif
    </span>
    <span class="product-application-label">{{ $application->label }}</span>
@if ($playable)</button>@else</div>@endif
