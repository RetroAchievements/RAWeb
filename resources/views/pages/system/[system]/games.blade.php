<?php

use function Laravel\Folio\{name};

name('system.game.index');

?>

@props([
    'availableCheckboxFilters' => [],
    'availableRadioFilters' => [],
    'availableSelectFilters' => [],
    'availableSorts' => [],
    'columns' => [],
    'filterOptions' => [],
    'gameListConsoles' => [],
    'games' => [],
    'shouldAlwaysShowMetaSurface' => true,
    'sortOrder' => 'title',
    'system', // System
    'totalUnfilteredCount' => null, // ?int
])

@php
$pageMetaDescription = '';
$areFiltersPristine = count(request()->query()) === 0;

if ($areFiltersPristine) {
    if (empty($games)) {
        $pageMetaDescription = "There are no games with achievements yet for {$system->Name}. Check again soon.";
    } else {
        $numGames = count($games);
        if ($numGames < 100) {
            $numGames = floor($numGames / 10) * 10; // round down to the nearest tenth
        } elseif ($numGames < 1000) {
            $numGames = floor($numGames / 100) * 100; // round down to the nearest hundredth
        } else {
            $numGames = floor($numGames / 1000) * 1000; // round down to the nearest thousandth
        }

        $localizedCount = localized_number($numGames);
        // WARNING: If you're tweaking this, try to make sure it doesn't exceed 200 characters.
        $pageMetaDescription = "Explore {$localizedCount}+ {$system->Name} games on RetroAchievements. Our achievements bring a fresh perspective to classic games, letting you track your progress as you beat and master each title.";
    }
}
@endphp

<x-app-layout
    pageTitle="{{ $system->Name }} Games"
    :pageDescription="$pageMetaDescription"
>
    <div>
        <x-system-games-page.system-heading
            :systemId="$system->ID"
            :systemName="$system->Name"
        />

        <x-game.game-list
            :availableCheckboxFilters="$availableCheckboxFilters"
            :availableRadioFilters="$availableRadioFilters"
            :availableSelectFilters="$availableSelectFilters"
            :availableSorts="$availableSorts"
            :columns="$columns"
            :consoles="$gameListConsoles"
            :filterOptions="$filterOptions"
            :games="$games"
            :sortOrder="$sortOrder"
            :shouldAlwaysShowMetaSurface="$shouldAlwaysShowMetaSurface"
            :shouldShowCount="true"
            :totalUnfilteredCount="$totalUnfilteredCount"
        />
    </div>
</x-app-layout>
