<?php

use App\Models\User;
use App\Platform\Services\DeveloperSetsService;
use Illuminate\View\View;

use function Laravel\Folio\{name, render, withTrashed};

withTrashed();

name('developer.sets');

render(function (View $view, User $user, DeveloperSetsService $pageService) {
    return $view->with($pageService->buildViewData(request(), $user));
});

?>

@props([
    'availableCheckboxFilters' => [], // string[]
    'availableSorts' => [],
    'columns' => [],
    'consoles' => null, // ?Collection<System>
    'filterOptions' => [], // bool[]
    'games' => [],
    'sortOrder' => 'title',
    'user' => null, // User
])

<x-app-layout
    pageTitle="{{ $user->User }} - Developed Sets"
    pageDescription="View achievement sets developed by {{ $user->User }} for various games on RetroAchievements"
>
    <x-user.breadcrumbs :targetUsername="$user->User" currentPage="Developed Sets" />

    <div class="mt-3 -mb-3 w-full flex gap-x-3">
        {!! userAvatar($user->User, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $user->User }}'s Developed Sets</h1>
    </div>

    <x-game.game-list
        :availableCheckboxFilters="$availableCheckboxFilters"
        :availableSorts="$availableSorts"
        :columns="$columns"
        :consoles="$consoles"
        :filterOptions="$filterOptions"
        :games="$games"
        :sortOrder="$sortOrder"
    />
</x-app-layout>
