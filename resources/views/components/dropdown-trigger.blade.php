@props([
    'desktopHref' => null, // string | null
    'triggerClass' => '',
    'id' => '',
    'title' => '',
])

@if ($desktopHref)
    <a
        href="{{ $desktopHref }}"
        class="{{ $triggerClass ?? '' }}"
        id="dropdownTrigger{{ $id }}"
        role="button"
        aria-haspopup="true"
        aria-expanded="false"
        title="{{ $title ?? '' }}"
    >
        {{ $slot }}
</a>
@else
    <button
        class="{{ $triggerClass ?? '' }}"
        id="dropdownTrigger{{ $id }}"
        role="button"
        aria-haspopup="true"
        aria-expanded="false"
        title="{{ $title ?? '' }}"
    >
        {{ $slot }}
    </button>
@endif