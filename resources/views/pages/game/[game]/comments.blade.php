<?php

use function Laravel\Folio\{name};

name('game.comments');

?>

@php

use App\Community\Enums\ArticleType;

@endphp

@props([
    'game' => null, // Game
])

<x-app-layout pageTitle="Comments: {{ $game->Title }}">
    <x-game.breadcrumbs
        :game="$game"
        currentPageLabel="Comments"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! gameAvatar($game, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Comments: {{ $game->Title }}</h1>
    </div>

    <x-comment.list
        articleType="{{ ArticleType::Game }}"
        articleId="{{ $game->id }}"
        :embedded="false"
    />
</x-app-layout>
