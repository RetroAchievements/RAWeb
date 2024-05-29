@props([
    'maxPerPage' => 25,
    'nextPageUrl' => null, // ?string
    'previousPageUrl' => null, // ?string
])

<div class="flex w-full justify-end mt-4 gap-x-4">
    @if ($previousPageUrl)
        <a href="{{ $previousPageUrl }}">&lt; Previous {{ $maxPerPage }}</a>
    @endif

    @if ($nextPageUrl)
        <a href="{{ $nextPageUrl }}">Next {{ $maxPerPage }} &gt;</a>
    @endif
</div>
