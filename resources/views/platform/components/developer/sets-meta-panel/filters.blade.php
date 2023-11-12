@props([
    'filterOptions' => [],
])

<label class="text-xs font-bold">Filters</label>
<div class="flex gap-x-4 text-2xs gap-y-0.5">
    <div class="flex flex-col">
        <x-developer.sets-meta-panel.filter-checkbox
            kind="console"
            :isPreChecked="$filterOptions['console']"
        >
            Group by console
        </x-developer.sets-meta-panel.filter-checkbox>

        <x-developer.sets-meta-panel.filter-checkbox
            kind="sole"
            :isPreChecked="$filterOptions['sole']"
        >
            Sole developer
        </x-developer.sets-meta-panel.filter-checkbox>
    </div>
</div>