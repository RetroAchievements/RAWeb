@props([
    'user' => null,
    'consoles' => [],
    'games' => [],
    'columns' => [],
    'noGamesMessage' => 'No games.',
])

<x-app-layout
    pageTitle="{{ $user->User }} - Game Suggestions"
    pageDescription="A list of random games that {{ $user->User }} might want to play"
>
    <x-user.breadcrumbs :targetUsername="$user->User" currentPage="Game Suggestions" />

    <div class="mt-3 w-full flex gap-x-3">
        {!! userAvatar($user->User, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $user->User }}'s Game Suggestions</h1>
    </div>

    <x-game.game-list
        :consoles="$consoles"
        :games="$games"
        :columns="$columns"
        :noGamesMessage="$noGamesMessage"
    />

</x-app-layout>
