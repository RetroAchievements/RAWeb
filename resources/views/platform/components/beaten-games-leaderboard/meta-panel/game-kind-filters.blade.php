@props([
    'allowsRetail' => true,
    'gameKindFilterOptions' => [],
    'leaderboardKind' => 'retail',
])

<label class="text-xs font-bold sm:-mb-6">Game kinds</label>
<div class="flex gap-x-4 text-2xs gap-y-0.5">
    <x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio
        value="retail"
        :disabled="!$allowsRetail"
        :selectedValue="$leaderboardKind"
    >
        Retail
    </x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio>

    <x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio
        value="homebrew"
        :selectedValue="$leaderboardKind"
    >
        Homebrew
    </x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio>

    <x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio
        value="hacks"
        :selectedValue="$leaderboardKind"
    >
        Hack
    </x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio>

    <x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio
        value="all"
        :selectedValue="$leaderboardKind"
    >
        All
    </x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio>
</div>