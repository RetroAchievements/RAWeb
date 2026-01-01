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
                ->whereIn('state', [
                    \App\Platform\Enums\LeaderboardState::Active->value,
                    \App\Platform\Enums\LeaderboardState::Disabled->value,
                ])
                ->orderByRaw("
                    CASE state
                        WHEN '" . \App\Platform\Enums\LeaderboardState::Active->value . "' THEN 0
                        WHEN '" . \App\Platform\Enums\LeaderboardState::Disabled->value . "' THEN 1
                        ELSE 2
                    END
                ")
                ->orderBy('order_column')
                ->get();
        @endphp

        <x-game.leaderboards-listing.leaderboards-list :$gameLeaderboards />
    @endif
</div>
