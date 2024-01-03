@props([
    'selectedSortOrder' => 'console',
    'filterOptions' => [],
])

<script>
function handleSortOrderChanged(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter(
        ['sort'],
        [newQueryParamValue],
    );
}

function handleFilterChanged(event, kind) {
    window.updateUrlParameter(
        [`filter[${kind}]`],
        [event.target.checked],
    );
}
</script>

<div class="embedded p-4 my-4 w-full">
    <p class="sr-only">Filters and sorts</p>

    <div class="grid sm:flex gap-y-4 sm:divide-x-2 divide-embed-highlight">
        <div class="grid gap-y-1 sm:pr-4 xl:pr-8">
            <x-game.related-games-meta-panel.sort-order
                :selectedSortOrder="$selectedSortOrder"
            />
        </div>

        <div class="grid gap-y-1 sm:px-8">
            <x-game.related-games-meta-panel.filters
                :filterOptions="$filterOptions"
            />
        </div>
    </div>
</div>
