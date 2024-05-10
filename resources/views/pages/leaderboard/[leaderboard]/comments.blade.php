<?php

use function Laravel\Folio\{name};

name('leaderboard.comments');

?>

@php

use App\Community\Enums\ArticleType;

@endphp

@props([
    'leaderboard' => null, // Leaderboard
])

<x-app-layout pageTitle="Comments: {{ $leaderboard->Title }}">
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
