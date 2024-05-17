<?php

use App\Platform\Services\SuggestGamesService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth']);
name('games.suggest');

render(function (View $view, SuggestGamesService $pageService) {
    $user = Auth::user();

    return $view->with($pageService->buildViewData($user));
});

?>

@props([
    'columns' => [],
    'consoles' => null, // Collection<int, System>
    'games' => [],
    'noGamesMessage' => 'No suggestions available.',
    'user' => null, // User
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
