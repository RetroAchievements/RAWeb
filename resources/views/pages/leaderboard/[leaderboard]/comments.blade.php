<?php

use App\Models\Leaderboard;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['can:viewAny,' . Leaderboard::class]);
name('leaderboard.comments');

render(function (View $view, Leaderboard $leaderboard) {
    return $view->with([
        'leaderboard' => $leaderboard,
    ]);
});

?>

@php

use App\Community\Enums\ArticleType;

@endphp

@props([
    'leaderboard' => null, // Leaderboard
])

<x-app-layout
    pageTitle="Comments: {{ $leaderboard->Title }}"
    pageDescription="General discussion about the leaderboard: {{ $leaderboard->Title }}"
    :pageImage="media_asset($leaderboard->game->ImageIcon)"
    pageType="retroachievements:comment-list"
>
    <x-leaderboard.breadcrumbs
        :leaderboard="$leaderboard"
        currentPageLabel="Comments"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! gameAvatar($leaderboard->game, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Comments: {{ $leaderboard->Title }}</h1>
    </div>

    <x-comment.list
        articleType="{{ ArticleType::Leaderboard }}"
        articleId="{{ $leaderboard->id }}"
        :embedded="false"
    />
</x-app-layout>
