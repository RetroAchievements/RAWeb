@props([
    'gameId',
    'gameTitle',
    'gameImageIcon',
    'href',
    'consoleId' => null,
    'consoleName' => null,
])

<?php
$renderedGameTitle = renderGameTitle($gameTitle);
$gameHref = route('game.show', $gameId);

$gameSystemIconSrc = $consoleId ? getSystemIconUrl($consoleId) : null;
?>

<div class="flex items-center" x-data="{ hovered: false }">
    <a 
        href="{{ $href ?? $gameHref }}"
        @mouseover="hovered = true"
        @mouseout="hovered = false"
    >
        <div class="pr-2">
            <img 
                src="{{ media_asset($gameImageIcon) }}" 
                alt="{{ $gameTitle }} game badge"
                width="36" 
                height="36" 
                class="w-9 h-9"
            >
        </div>
    </a>

    <div class="w-full">
        <div class="mb-0.5">
            <a 
                href="{{ $href ?? $gameHref }}"
                class="font-medium text-xs"
                :class="{ 'text-link-hover': hovered }"
            >
                {!! $renderedGameTitle !!}
            </a>
        </div>

        @if($consoleId || $consoleName)
            <div class="flex items-center gap-x-1">
                @if($consoleId && $consoleName)
                    <img src="{{ $gameSystemIconSrc }}" width="18" height="18" alt="{{ $consoleName }} console icon">
                @endif

                @if($consoleName)
                    <span class="block text-xs tracking-tighter">{{ $consoleName }}</span>
                @endif
            </div>
        @endif
    </div>
</div>