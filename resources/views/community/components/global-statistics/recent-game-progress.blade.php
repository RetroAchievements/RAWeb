@props([
    'game',
    'headingLabel' => 'Most recent game mastered',
    'timestamp' => '',
    'userId' => 1,
])

<?php

use App\Models\User;

$user = User::find($userId);

if (!$user) {
    return null;
}

$system = $game->console->Name;
$gameUrl = route('game.show', $game->ID);
?>

<div>
    <div class="text-2xs flex lg:justify-between items-center gap-x-2 w-full mb-0.5 flex-nowrap">
        <p>{{ $headingLabel }}</p>
        <p class="smalldate !min-w-0">{{ $timestamp }}</p>
    </div>

    <div class="text-xs bg-embed p-4 rounded border border-embed-highlight">
        <div class="flex gap-x-2.5 leading-4 relative">
            {{-- Keep the image and game title in a single tooltipped container. --}}
            <a
                href="{{ $gameUrl }}"
                x-data="tooltipComponent($el, {dynamicType: 'game', dynamicId: '{{ $game->ID }}'})"
                @mouseover="showTooltip($event)"
                @mouseleave="hideTooltip"
                @mousemove="trackMouseMovement($event)"
            >
                <img
                    src="{{ media_asset($game->ImageIcon) }}"
                    alt="{{ $game->Title }} badge"
                    width="64"
                    height="64"
                    class="w-16 h-16 min-w-[64px] rounded-sm"
                >

                <p class="absolute pl-4 top-[-2px] left-[58px] max-w-fit line-clamp-2 mb-px">
                    <x-game-title :rawTitle="$game->Title" />
                </p>
            </a>

            <div class="-mt-1 w-full">
                {{-- Provide invisible space to slide the console underneath --}}
                <p class="invisible max-w-fit line-clamp-2 mb-px">
                    <x-game-title :rawTitle="$game->Title" />
                </p>

                <p>
                    {{ $game->console->Name }}
                </p>

                <hr class="mt-1 mb-2 border-embed-highlight">

                {!! userAvatar($user->User, iconSize: 22, iconClass: 'rounded-sm mr-0.5') !!}
            </div>
        </div>
    </div>
</div>
