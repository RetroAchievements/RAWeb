<?php

use App\Models\System;
?>

@props([
    'achievement' => null, // Achievement
    'currentPageLabel' => null, // ?string
])

<?php
$achievement->load('game');
?>

{{-- All Games >> Console Name >> Game Name >> Achievement Name >> Page Name --}}
<div class="navpath">
    <a href="/gameList.php">All Games</a>

    &raquo;

    <a href="{{ route('system.game.index', ['system' => $achievement->game->ConsoleID]) }}">{{ $achievement->game->system->Name }}</a>

    &raquo;

    <a href="{{ route('game.show', $achievement->game->id) }}">
        <x-game-title :rawTitle="$achievement->game->Title" />
    </a>

    &raquo;

    {{-- If there's a current page label, then show game metadata as a URL. Otherwise, it's plain text. --}}
    @if ($currentPageLabel)
        <a href="{{ route('achievement.show', $achievement->id) }}">
            <x-game-title :rawTitle="$achievement->Title" />
        </a>
    @else
        <span class="font-bold">
            <x-game-title :rawTitle="$achievement->Title" />
        </span>
    @endif

    @if ($currentPageLabel)
        &raquo;

        <span class="font-bold">{{ $currentPageLabel }}</span>
    @endif
</div>
