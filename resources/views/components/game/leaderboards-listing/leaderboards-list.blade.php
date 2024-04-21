@props([
    'gameLeaderboards' => null, // Collection<Leaderboard>
])

<div class="max-h-[980px] overflow-y-auto">
    @foreach ($gameLeaderboards as $leaderboard)
        {{-- Don't expose hidden leaderboards to the UI. --}}
        @if ($leaderboard->order_column < 0)
            @continue
        @endif

        <x-game.leaderboards-listing.leaderboards-list-item
            :leaderboard="$leaderboard"
        />
    @endforeach
</div>
