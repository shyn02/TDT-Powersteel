@props(['application'])

@php
    $thumbnail = $application->thumbnailUrl();
    $playable = $application->isPlayable();
@endphp

{{--
    The list row beneath the map: the same Product Application in text form,
    and the small-screen and assistive-technology path to the same modal.
--}}
<li class="product-applications-list-item">
    @if ($playable)
    <button type="button"
            class="product-applications-list-open"
            data-video-trigger
            data-video-url="{{ $application->videoUrl() }}"
            data-label="{{ $application->label }}"
            @if (filled($application->description)) data-description="{{ $application->description }}" @endif
            aria-label="Play video: {{ $application->label }}">
    @else
    <span class="product-applications-list-static">
    @endif
        @if ($thumbnail)
        <img src="{{ $thumbnail }}"
             alt=""
             class="product-applications-list-thumb"
             width="56" height="56" loading="lazy" decoding="async">
        @endif
        <span class="product-applications-list-label">{{ $application->label }}</span>
    @if ($playable)</button>@else</span>@endif
</li>
