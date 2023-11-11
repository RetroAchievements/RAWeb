@props([
    'allSystems' => null,
    'gameKindFilterOptions' => [],
    'selectedConsoleId' => null,
    'selectedAllowHacks' => true,
])

<script>
function handleGameKindsChanged(event) {
    window.updateUrlParameter(
        ['page[number]', 'filter[kind]'],
        [1, event.target.value],
    );
}

function handleConsoleChanged(event) {
    window.updateUrlParameter(
        ['page[number]', 'filter[system]'],
        [1, event.target.value],
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
                    :gameKindFilterOptions="$gameKindFilterOptions"
                />
            </div>
        </div>
    </div>
</div>