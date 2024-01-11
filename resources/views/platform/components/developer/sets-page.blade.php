@props([
    'user' => null,
    'consoles' => [],
    'games' => [],
    'sortOrder' => 'title',
    'availableSorts' => [],
    'filterOptions' => [],
    'availableFilters' => [],
    'columns' => [],
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
        :consoles="$consoles"
        :games="$games"
        :sortOrder="$sortOrder"
        :availableSorts="$availableSorts"
        :filterOptions="$filterOptions"
        :availableFilters="$availableFilters"
        :columns="$columns"
    />

</x-app-layout>
