<?php

use App\Models\Game;
use App\Platform\Services\SuggestGamesService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,game']);
name('game.suggest');

render(function (View $view, Game $game, SuggestGamesService $pageService) {
    $user = Auth::user();

    return $view->with($pageService->buildViewData($user, $game));
});

?>

<x-app-layout
    pageTitle="Game Suggestions - {{ $game->Title }}"
    pageDescription="A list of random games that a user might want to play if they enjoyed {{ $game->Title }}"
>
    <x-game.breadcrumbs 
        :game="$game"
        currentPageLabel="Game Suggestions"
    />

    <div class="mt-3 -mb-3 w-full flex gap-x-3">
        {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Game Suggestions</h1>
    </div>

    <div class="mt-3">If you liked {{ $game->Title }}, you might also like these:</div>

    <x-game.game-list
        :consoles="$consoles"
        :games="$games"
        :columns="$columns"
        :noGamesMessage="$noGamesMessage"
    />
</x-app-layout>
