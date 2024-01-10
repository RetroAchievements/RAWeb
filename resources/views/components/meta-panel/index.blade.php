@props([
    'availableSorts' => [],
    'selectedSortOrder' => null,
    'availableFilters' => [],
    'filterOptions' => [],
])

<?php
    $hasSorts = count($availableSorts) > 0;
    $hasFilters = count($availableFilters) > 0;
    if ($hasSorts) {
        $header = $hasFilters ? "Sorts and filters" : "Sorts";
    } else if ($hasFilters) {
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

        @if ($hasFilters)
        <div class="grid gap-y-1 sm:px-8">
            <x-meta-panel.filters
                :availableFilters="$availableFilters"
                :filterOptions="$filterOptions"
            />
        </div>
        @endif
    </div>
</div>
@endif
