@props([
    'gameKindFilterOptions' => [],
])

<label class="text-xs font-bold">Game kinds</label>
<div class="flex gap-x-4 text-2xs gap-y-0.5">
    <div class="flex flex-col">
        <x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox
            kind="retail"
            :isPreChecked="$gameKindFilterOptions['retail']"
        >
            Retail
        </x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox>

        <x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox
            kind="hacks"
            :isPreChecked="$gameKindFilterOptions['hacks']"
        >
            Hacks
        </x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox>
    </div>

    <div class="flex flex-col">
        <x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox
            kind="homebrew"
            :isPreChecked="$gameKindFilterOptions['homebrew']"
        >
            Homebrew
        </x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox>

        <x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox
            kind="unlicensed"
            :isPreChecked="$gameKindFilterOptions['unlicensed']"
        >
            Unlicensed
        </x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox>
    </div>

    <div class="flex flex-col">
        <x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox
            kind="prototypes"
            :isPreChecked="$gameKindFilterOptions['prototypes']"
        >
            Prototypes
        </x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox>

        <x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox
            kind="demos"
            :isPreChecked="$gameKindFilterOptions['demos']"
        >
            Demos
        </x-beaten-games-leaderboard.meta-panel.game-kind-filter-checkbox>
    </div>
</div>