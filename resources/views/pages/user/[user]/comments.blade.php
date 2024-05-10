<?php

use function Laravel\Folio\{name};

name('user.comments');

?>

@php

use App\Community\Enums\ArticleType;

@endphp

@props([
    'user' => null, // User
])

<x-app-layout pageTitle="Comments: {{ $user->display_name }}">
    <x-user.breadcrumbs
        :targetUsername="$user->User"
        currentPage="Comments"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! userAvatar($user, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Comments: {{ $user->display_name }}</h1>
    </div>

    <x-comment.list
        articleType="{{ ArticleType::User }}"
        articleId="{{ $user->id }}"
        :article="$user"
        :embedded="false"
    />
</x-app-layout>
