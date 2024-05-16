<?php

use App\Models\System;
use App\Platform\Services\SystemGamesPageService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['can:viewAny,' . App\Models\System::class]);
name('system.game.index');

render(function (View $view, System $system, SystemGamesPageService $pageService) {
    return $view->with($pageService->buildViewData(request(), $system));
});

?>

@props([
    'availableCheckboxFilters' => [],
    'availableRadioFilters' => [], // (string|string[])[][]
    'availableSelectFilters' => [], // (string|string[])[][]
    'availableSorts' => [],
    'columns' => [],
    'filterOptions' => [],
    'gameListConsoles' => null, // Collection<int, System>
    'games' => [],
    'pageMetaDescription' => '',
    'shouldAlwaysShowMetaSurface' => false,
    'sortOrder' => 'title',
    'system' => null, // System
    'totalUnfilteredCount' => 0,
])

<x-app-layout
    pageTitle="{{ $system->name }} Games"
    :pageDescription="$pageMetaDescription"
>
    <div>
        <x-system-games-page.system-heading
            :systemId="$system->id"
            :systemName="$system->name"
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
