@props([
    'game' => null, // Game
])

<div class="component">
    <h2 class="text-h3">Leaderboards</h2>

    @if (!$game->visibleLeaderboards()->exists())
        <x-game.leaderboards-listing.empty-state :game="$game" />
    @else
        @php
            $gameLeaderboards = $game->visibleLeaderboards()
                ->withTopEntry()
                ->orderBy('DisplayOrder')
                ->get();
        @endphp

        <x-game.leaderboards-listing.leaderboards-list :$gameLeaderboards />
    @endif
</div>
