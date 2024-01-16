@props([
    'availableCheckboxFilters' => [],
    'filterOptions' => [],
])

<script>
function handleFilterChanged(event, kind) {
    window.updateUrlParameter(
        [`filter[${kind}]`],
        [event.target.checked],
    );
}
</script>

<label class="text-xs font-bold">Filters</label>
<div class="flex gap-x-4 text-2xs gap-y-0.5">
    <div class="flex flex-col">
        @foreach ($availableCheckboxFilters as $key => $text)
            <x-meta-panel.filter-checkbox
                :kind="$key"
                :isPreChecked="$filterOptions[$key]"
            >
                {{ $text }}
            </x-game.related-games-meta-panel.filter-checkbox>
        @endforeach
    </div>
</div>
