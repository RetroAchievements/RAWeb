@props([
    'availableCheckboxFilters' => [],
    'availableRadioFilters' => [],
    'availableSelectFilters' => [],
    'availableSorts' => [],
    'filterOptions' => [],
    'selectedSortOrder' => null,
])

<?php
    $hasSorts = !empty($availableSorts);;
    $hasCheckboxFilters = !empty($availableCheckboxFilters);
    $hasRadioFilters = !empty($availableRadioFilters);
    $hasSelectFilters = !empty($availableSelectFilters);

    $hasAnyFilters = $hasCheckboxFilters || $hasRadioFilters || $hasSelectFilters;

    if ($hasSorts) {
        $header = $hasAnyFilters ? "Sorts and filters" : "Sorts";
    } else if ($hasAnyFilters) {
        $header = "Filters";
    } else {
        $header = null;
    }
?>

@if ($header !== null)
    <div class="embedded p-4 my-4 w-full">
        <p class="sr-only">{{ $header }}</p>

        <div class="
            grid sm:flex gap-y-4 sm:divide-x-2 divide-embed-highlight
            sm:*:px-4 xl:*:px-8 first:*:pl-0 last:*:pr-0
            "
        >
            @if ($hasSorts)
                <div class="grid gap-y-1">
                    <x-meta-panel.sort-order
                        :availableSorts="$availableSorts"
                        :selectedSortOrder="$selectedSortOrder"
                    />
                </div>
            @endif

            @if ($hasSelectFilters)
                @foreach ($availableSelectFilters as $availableSelectFilter)
                    <div class="grid gap-y-1">
                        <x-meta-panel.select-filter
                            :allFilterOptions="$filterOptions"
                            :kind="$availableSelectFilter['kind']"
                            :label="$availableSelectFilter['label']"
                            :options="$availableSelectFilter['options']"
                        />
                    </div>
                @endforeach
            @endif

            @if ($hasRadioFilters)
                @foreach ($availableRadioFilters as $availableRadioFilter)
                    <div class="grid gap-y-1">
                        <x-meta-panel.radio-filter
                            :allFilterOptions="$filterOptions"
                            :kind="$availableRadioFilter['kind']"
                            :label="$availableRadioFilter['label']"
                            :options="$availableRadioFilter['options']"
                        />
                    </div>
                @endforeach
            @endif

            @if ($hasCheckboxFilters)
                <div class="grid gap-y-1">
                    <x-meta-panel.checkbox-filters
                        :availableCheckboxFilters="$availableCheckboxFilters"
                        :filterOptions="$filterOptions"
                    />
                </div>
            @endif
        </div>
    </div>
@endif
