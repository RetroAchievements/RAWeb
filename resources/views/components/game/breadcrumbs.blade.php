<?php

use App\Models\System;
?>

@props([
    'game' => null, // Game
    'currentPageLabel' => null, // ?string
])

<?php
$game->load('system');

$gameListHref = System::isGameSystem($game->system->id)
    ? route('system.game.index', ['system' => $game->system->id])
    : '/gameList.php?c=' . $game->system->id;
?>

{{-- All Games >> Console Name >> Game Name >> Page Name --}}
<div class="navpath">
    <a href="/gameList.php">All Games</a>

    &raquo;

    {{-- If there's game metadata, then show console metadata as a URL. Otherwise, it's plain text. --}}
    @if ($gameListHref)
        <a href="{{ $gameListHref }}">{{ $game->system->Name }}</a>
    @else
        <span class="font-bold">{{ $game->system->Name }}</span>
    @endif

    &raquo;

    {{-- If there's a current page label, then show game metadata as a URL. Otherwise, it's plain text. --}}
    @if ($currentPageLabel)
        <a href="{{ route('game.show', $game->id) }}">
            <x-game-title :rawTitle="$game->Title" />
        </a>
    @else
        <span class="font-bold">
            <x-game-title :rawTitle="$game->Title" />
        </span>
    @endif

    @if ($currentPageLabel)
        &raquo;

        <span class="font-bold">{{ $currentPageLabel }}</span>
    @endif
</div>
