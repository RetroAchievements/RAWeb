@props([
    'achievement' => null, // Achievement
    'currentPageLabel' => null, // ?string
])

<?php
$achievement->loadMissing('game.system');
?>

{{-- All Games >> Console Name >> Game Name >> Achievement Name >> Page Name --}}
<div class="navpath">
    <a href="{{ route('game.index') }}">All Games</a>

    &raquo;

    <a href="{{ route('system.game.index', ['system' => $achievement->game->system_id]) }}">{{ $achievement->game->system->name }}</a>

    &raquo;

    <a href="{{ route('game.show', $achievement->game->id) }}">
        <x-game-title :rawTitle="$achievement->game->title" />
    </a>

    &raquo;

    {{-- If there's a current page label, then show game metadata as a URL. Otherwise, it's plain text. --}}
    @if ($currentPageLabel)
        <a href="{{ route('achievement.show', $achievement->id) }}">
            <x-game-title :rawTitle="$achievement->title" />
        </a>
    @else
        <span class="font-bold">
            <x-game-title :rawTitle="$achievement->title" />
        </span>
    @endif

    @if ($currentPageLabel)
        &raquo;

        <span class="font-bold">{{ $currentPageLabel }}</span>
    @endif
</div>
