@props([
    'gameKindFilterOptions' => [],
])

<?php
$selectedValue = 'retail';
if ($gameKindFilterOptions['homebrew'] === true && $gameKindFilterOptions['demos'] !== true) {
    $selectedValue = 'homebrew';
}
if ($gameKindFilterOptions['hacks'] === true && $gameKindFilterOptions['demos'] !== true) {
    $selectedValue = 'hacks';
}
if ($gameKindFilterOptions['demos'] === true) {
    $selectedValue = 'all';
}

$cacheableSelectedValue = $gameKindFilterOptions['homebrew'] === true ? 'all' : 'retail-only';
?>

<label class="text-xs font-bold sm:-mb-6">Game kinds</label>
<div class="flex gap-x-4 text-2xs gap-y-0.5">
    <x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio
        value="retail"
        :selectedValue="$selectedValue"
    >
        Retail
    </x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio>

    <x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio
        value="homebrew"
        :selectedValue="$selectedValue"
    >
        Homebrew
    </x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio>

    <x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio
        value="hacks"
        :selectedValue="$selectedValue"
    >
        Hack
    </x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio>

    <x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio
        value="all"
        :selectedValue="$selectedValue"
    >
        All
    </x-beaten-games-leaderboard.meta-panel.game-kind-filter-radio>
</div>