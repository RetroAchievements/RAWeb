@props([
    'game' => null, // Game
])

<div class="component">
    <h2 class="text-h3">Leaderboards</h2>

    @if (!$game->visibleLeaderboards()->exists())
        <x-game.leaderboards-listing.empty-state :game="$game" />
    @else
        <x-game.leaderboards-listing.leaderboards-list
            :gameLeaderboards="$game->visibleLeaderboards->sortBy('DisplayOrder')"
        />
    @endif
</div>
