@props([
    'activePlayersCount' => 0,
    'totalActivePlayers' => 0,
])

<p>
    Viewing

    <span
        class="font-bold"
        id="active-players-viewing"
    >
        {{ localized_number($activePlayersCount) }}
    </span>

    @if ($activePlayersCount !== $totalActivePlayers)
        of 

        <span
            class="font-bold"
            id="active-players-total"
        >
            {{ localized_number($totalActivePlayers) }}
        </span>
    @endif

    {{ mb_strtolower(__res('player', $activePlayersCount)) }} in-game.
</p>
