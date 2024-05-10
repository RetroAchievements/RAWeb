<?php

use function Laravel\Folio\{name};

name('achievement.comments');

?>

@php

use App\Community\Enums\ArticleType;

@endphp

@props([
    'achievement' => null, // Achievement
])

<x-app-layout pageTitle="Comments: {{ $achievement->Title }}">
    <x-achievement.breadcrumbs
        :achievement="$achievement"
        currentPageLabel="Comments"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! gameAvatar($achievement->game, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Comments: {{ $achievement->Title }}</h1>
    </div>

    <x-comment.list
        articleType="{{ ArticleType::Achievement }}"
        articleId="{{ $achievement->id }}"
        :embedded="false"
    />
</x-app-layout>
