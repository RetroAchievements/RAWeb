<?php

use App\Models\System;
?>

@props([
    'targetConsoleId' => 1,
    'targetConsoleName' => 'Mega Drive',
    'targetGameId' => null, // int | null
    'targetGameName' => null, // string | null,
    'currentPageLabel' => null, // string | null
])

<?php
$gameListHref = System::isGameSystem($targetConsoleId)
    ? route('system.game.index', ['system' => $targetConsoleId])
    : '/gameList.php?c=' . $targetConsoleId;
?>

{{-- All Games >> Console Name >> Game Name >> Page Name --}}
<div class="navpath">
    <a href="/gameList.php">All Games</a>

    &raquo;

    {{-- If there's game metadata, then show console metadata as a URL. Otherwise, it's plain text. --}}
    @if ($targetConsoleId && $targetConsoleName)
        <a href="{{ $gameListHref }}">{{ $targetConsoleName }}</a>
    @else
        <span class="font-bold">{{ $targetConsoleName }}</span>
    @endif

    @if ($targetGameId && $targetGameName)
        &raquo;

        {{-- If there's a current page label, then show game metadata as a URL. Otherwise, it's plain text. --}}
        @if ($currentPageLabel)
            <a href="{{ route('game.show', $targetGameId) }}">
                <x-game-title :rawTitle="$targetGameName" />
            </a>
        @else
            <span class="font-bold">
                <x-game-title :rawTitle="$targetGameName" />
            </span>
        @endif
    @endif

    @if ($currentPageLabel)
        &raquo;

        <span class="font-bold">{{ $currentPageLabel }}</span>
    @endif
</div>
