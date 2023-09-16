@props([
    'trendingGames' => [],
])

<div id="active-players-trending-games" class="mb-8">
    <hr class="mt-6 mb-5 border-embed-highlight">

    <div class="my-2">
        <p class="font-bold text-xs">Trending right now</p>

        <div class="grid grid-cols-2 gap-1">
            @foreach ($trendingGames as $trendingGame)
                <div class="rounded-lg bg-embed p-2 flex items-center">
                    <x-game.multiline-avatar
                        :gameId="$trendingGame['GameID']"
                        :gameTitle="$trendingGame['GameTitle']"
                        :gameImageIcon="$trendingGame['GameIcon']"
                        :consoleName="$trendingGame['ConsoleName']"
                        labelClassName="line-clamp-2"
                    />
                </div>
            @endforeach
        </div>
    </div>
</div>
