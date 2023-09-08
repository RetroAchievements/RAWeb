@props([
    'gameId',
    'gameTitle',
    'gameImageIcon',
    'href',
    'consoleId' => null,
    'consoleName' => null,
    'hasTooltip' => true,
])

<?php
$renderedGameTitle = renderGameTitle($gameTitle);
$gameHref = route('game.show', $gameId);

$gameSystemIconSrc = $consoleId ? getSystemIconUrl($consoleId) : null;
$showConsoleLine = $consoleId || $consoleName;
?>

<div class="gap-x-2 flex relative items-center">
    <!-- Keep the image and game title in a single tooltipped container. Do not tooltip the console name. -->
    <a 
        href="{{ $href ?? $gameHref }}" 
        @if(!$showConsoleLine) class="flex items-center gap-x-2" @endif
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
            width="36" 
            height="36" 
            class="w-9 h-9"
            loading="lazy"
            decoding="async"
        >

        <p class="{{ $showConsoleLine ? "absolute pl-4 top-0 left-7" : "" }} max-w-fit font-medium mb-0.5 text-xs">
            {!! $renderedGameTitle !!}
        </p>
    </a>

    @if($showConsoleLine)
        <div>
            <!-- Provide invisible space to slide the console underneath -->
            <p class="invisible max-w-fit font-medium mb-0.5 text-xs">{!! $renderedGameTitle !!}</p>

            <div class="flex items-center gap-x-1">
                @if($consoleId && $consoleName)
                    <img src="{{ $gameSystemIconSrc }}" width="18" height="18" alt="{{ $consoleName }} console icon">
                @endif

                @if($consoleName)
                    <span class="block text-xs tracking-tighter mt-px">{{ $consoleName }}</span>
                @endif
            </div>
        </div>
    @endif
</div>
