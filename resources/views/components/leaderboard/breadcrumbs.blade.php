@props([
    'leaderboard' => null, // Achievement
    'currentPageLabel' => null, // ?string
])

<?php
$leaderboard->loadMissing('game.system');
?>

{{-- All Games >> Console Name >> Game Name >> Leaderboard Name >> Page Name --}}
<div class="navpath">
    <a href="/gameList.php">All Games</a>

    &raquo;

    <a href="{{ route('system.game.index', ['system' => $leaderboard->game->ConsoleID]) }}">{{ $leaderboard->game->system->Name }}</a>

    &raquo;

    <a href="{{ route('game.show', $leaderboard->game->id) }}">
        <x-game-title :rawTitle="$leaderboard->game->Title" />
    </a>

    &raquo;

    {{-- If there's a current page label, then show game metadata as a URL. Otherwise, it's plain text. --}}
    @if ($currentPageLabel)
        {{-- <a href="{{ route('leaderboard.show', $leaderboard->id) }}"> --}}
        <a href="/leaderboardinfo.php?i={{ $leaderboard->id }}">
            <x-game-title :rawTitle="$leaderboard->Title" />
        </a>
    @else
        <span class="font-bold">
            <x-game-title :rawTitle="$leaderboard->Title" />
        </span>
    @endif

    @if ($currentPageLabel)
        &raquo;

        <span class="font-bold">{{ $currentPageLabel }}</span>
    @endif
</div>
