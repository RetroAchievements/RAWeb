@props([
    'nonSubsetTags' => [],
    'showTags' => true,
    'strippedTitle' => '',
    'subsetKind' => null,
])

{{ $strippedTitle }}

@if ($showTags)
    @foreach ($nonSubsetTags as $nonSubsetTag)
        <span class="tag">
            <span>{{ $nonSubsetTag }}</span>
        </span>
    @endforeach

    @if ($subsetKind)
        <span class="tag">
            <span class="tag-label">Subset</span>
            <span class="tag-arrow"></span>
            <span>{{ $subsetKind }}</span>
        </span>
    @endif
@endif
