@props([
    'allSystems' => null,
    'gameKindFilterOptions' => [],
    'leaderboardKind' => 'retail',
    'selectedConsoleId' => null,
])

<?php
// Arduboy, WASM-4, and Uzebox are homebrew consoles.
// They do not have games that would conventionally be considered "retail".
$allowsRetail = (
    $selectedConsoleId !== 71
    && $selectedConsoleId !== 72
    && $selectedConsoleId !== 80
);
?>

<script>
function handleGameKindsChanged(event) {
    window.updateUrlParameter(
        ['page[number]', 'filter[kind]'],
        [1, event.target.value],
    );
}

function handleConsoleChanged(event) {
    // The "Retail" filter is automatically disabled on homebrew systems.
    // Default to the "All" option.
    const homebrewSystemIds = [71, 72, 80];
    if (homebrewSystemIds.includes(Number(event.target.value))) {
        window.updateUrlParameter(
            ['page[number]', 'filter[system]', 'filter[kind]'],
            [1, event.target.value, 'all'],
        );

        return;
    }

    window.updateUrlParameter(
        ['page[number]', 'filter[system]', 'filter[kind]'],
        [1, event.target.value, 'retail'],
    );
}
</script>

<div x-init="{}">
    <div class="embedded p-4 my-4 w-full">
        <p class="sr-only">Filters</p>

        <div class="grid sm:flex gap-y-4 sm:divide-x-2 divide-embed-highlight">
            <div class="grid gap-y-1 sm:pr-[40px]">
                <x-beaten-games-leaderboard.meta-panel.console-filter
                    :allSystems="$allSystems"
                    :selectedConsoleId="$selectedConsoleId"
                />
            </div>

            <div class="grid gap-y-1 sm:px-8">
                <x-beaten-games-leaderboard.meta-panel.game-kind-filters
                    :allowsRetail="$allowsRetail"
                    :gameKindFilterOptions="$gameKindFilterOptions"
                    :leaderboardKind="$leaderboardKind"
                />
            </div>
        </div>
    </div>
</div>