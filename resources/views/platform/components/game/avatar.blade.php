@props([
    'gameId',
    'gameImageIcon',
    'gameTitle',
    'hasTooltip' => true,
    'href',
    'labelClassName' => '',
])

<?php
$gameHref = route('game.show', $gameId);
?>

<div class="gap-x-2 flex relative items-center">
    {{-- Keep the image and game title in a single tooltipped container. --}}
    <a 
        href="{{ $href ?? $gameHref }}" 
        class="flex items-center gap-x-1"
        @if($hasTooltip)
            x-data="tooltipComponent($el, {dynamicType: 'game', dynamicId: '{{ $gameId }}'})" 
            @mouseover="showTooltip($event)"
            @mouseleave="hideTooltip"
            @mousemove="trackMouseMovement($event)"
        @endif
    >
        <img 
            src="{{ media_asset($gameImageIcon) }}" 
            alt="{{ $gameTitle }} game badge"
            width="20" 
            height="20" 
            loading="lazy"
            decoding="async"
        >

        <p class="{{ $labelClassName ?? "" }} max-w-fit font-medium mb-0.5 text-xs">
            <x-game-title :rawTitle="$gameTitle" />
        </p>
    </a>
</div>