@props([
    'gameLeaderboards' => null, // Collection<Leaderboard>
])

<div class="max-h-[980px] overflow-y-auto">
    @foreach ($gameLeaderboards as $leaderboard)
        <x-game.leaderboards-listing.leaderboards-list-item
            :leaderboard="$leaderboard"
        />
    @endforeach
</div>
