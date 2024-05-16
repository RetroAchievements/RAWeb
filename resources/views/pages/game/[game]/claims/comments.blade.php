<?php

use App\Models\AchievementSetClaim;
use App\Models\Game;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:manage,' . AchievementSetClaim::class]);
name('game.claims.comments');

render(function (View $view, Game $game) {
    return $view->with([
        'game' => $game,
    ]);
});

?>

@php

use App\Community\Enums\ArticleType;

@endphp

@props([
    'game' => null, // Game
])

<x-app-layout pageTitle="Claim Comments: {{ $game->Title }}">
    <x-game.breadcrumbs
        :game="$game"
        currentPageLabel="Claim Comments"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! gameAvatar($game, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Claim Comments: {{ $game->Title }}</h1>
    </div>

    <x-comment.list
        articleType="{{ ArticleType::SetClaim }}"
        articleId="{{ $game->id }}"
        :embedded="false"
    />
</x-app-layout>
