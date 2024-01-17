@props([
    'availableCheckboxFilters' => [],
    'availableSelectFilters' => [],
    'availableSorts' => [],
    'filterOptions' => [],
    'selectedSortOrder' => null,
])

<?php
    $hasSorts = !empty($availableSorts);;
    $hasCheckboxFilters = !empty($availableCheckboxFilters);
    $hasSelectFilters = !empty($availableSelectFilters);

    if ($hasSorts) {
        $header = ($hasCheckboxFilters || $hasSelectFilters) ? "Sorts and filters" : "Sorts";
    } else if ($hasCheckboxFilters || $hasSelectFilters) {
        $header = "Filters";
    } else {
        $header = null;
    }
?>

@if ($header !== null)
    <div class="embedded p-4 my-4 w-full">
        <p class="sr-only">{{ $header }}</p>

        <div class="grid sm:flex gap-y-4 sm:divide-x-2 divide-embed-highlight">
            @if ($hasSorts)
                <div class="grid gap-y-1 sm:pr-4 xl:pr-8">
                    <x-meta-panel.sort-order
                        :availableSorts="$availableSorts"
                        :selectedSortOrder="$selectedSortOrder"
                    />
                </div>
            @endif

            @if ($hasSelectFilters)
                @foreach ($availableSelectFilters as $availableSelectFilter)
                    <div class="grid gap-y-1 sm:px-8 lg:px-4 xl:px-8">
                        <x-meta-panel.select-filter
                            :allFilterOptions="$filterOptions"
                            :kind="$availableSelectFilter['kind']"
                            :label="$availableSelectFilter['label']"
                            :options="$availableSelectFilter['options']"
                        />
                    </div>
                @endforeach
            @endif

            @if ($hasCheckboxFilters)
                <div class="grid gap-y-1 sm:px-8 lg:pl-4 xl:pl-8">
                    <x-meta-panel.checkbox-filters
                        :availableCheckboxFilters="$availableCheckboxFilters"
                        :filterOptions="$filterOptions"
                    />
                </div>
            @endif
        </div>
    </div>
@endif
