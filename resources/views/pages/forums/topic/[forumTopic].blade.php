<?php

use App\Community\Enums\SubscriptionSubjectType;
use App\Models\ForumTopic;
use App\Policies\ForumTopicCommentPolicy;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Community\Services\ForumTopicPageService;

use function Laravel\Folio\{name, middleware, render};

name('forum.topic');

render(function (View $view, ForumTopic $forumTopic, ForumTopicPageService $pageService) {
    return $view->with($pageService->buildViewData(request(), $forumTopic));
});

?>

@props([
    'allForumTopicCommentsForTopic' => null, // Collection<int, ForumTopicComment>
    'category' => null, // ForumCategory
    'commentsPerPage' => 0,
    'currentPage' => 0,
    'forum' => null, // Forum
    'forumTopic' => null, // ForumTopic
    'isSubscribed' => false,
    'offset' => 0,
    'targetCommentId' => null, // ?int
    'user' => null, // ?User
])

@php
    $numTotalComments = $forumTopic->comments()->count();
    $totalPages = $commentsPerPage > 0 ? ceil($numTotalComments / $commentsPerPage) : 1;
@endphp

<x-app-layout>
    {{-- TODO forum breadcrumbs component --}}
    <div class="navpath">
        <a href="forum.php">Forum Index</a>
        &raquo; <a href='/forum.php?c={{ $forum->category->id }}'>{{ $forum->category->title }}</a>
        &raquo; <a href='/viewforum.php?f={{ $forum->id }}'>{{ $forum->title }}</a>
        &raquo; <b>{{ $forumTopic->title }}</b>
    </div>

    <h1 class="text-h2">{{ $forumTopic->title }}</h1>

    @can('update', $forumTopic)
        <livewire:forum.topic-tools :$forumTopic />
    @endcan

    <div class="flex justify-between mb-3">
        <div>
            @if ($numTotalComments > $commentsPerPage)
                <x-paginator
                    :$totalPages
                    :$currentPage
                />
            @endif
        </div>

        @auth
            <x-update-subscription-button
                :$isSubscribed
                :subjectType="SubscriptionSubjectType::ForumTopic"
                :subjectId="$forumTopic->id"
            />
        @endauth
    </div>

    <div class="mb-4">
        @php
            // Create a policy instance instead of using `can()`. We do this because the
            // policy logic changes based on whether the user is null.
            $policy = new ForumTopicCommentPolicy();
        @endphp

        @foreach ($allForumTopicCommentsForTopic as $index => $forumTopicComment)
            @if ($policy->view($user, $forumTopicComment))
                <x-forum.topic-comment
                    :$forumTopic
                    :$forumTopicComment
                    :threadPostNumber="$index + 1 + $offset"
                    :variant="$forumTopicComment->id === $targetCommentId ? 'highlight' : 'base'"
                >
                    {!! Shortcode::render($forumTopicComment->body) !!}
                </x-forum.topic-comment>
            @endif
        @endforeach
    </div>

    @if ($numTotalComments > $commentsPerPage)
        <div class="mb-3">
            <x-paginator
                :$totalPages
                :$currentPage
            />
        </div>
    @endif

    @guest
        <p class="text-center">
            You must log in before you can join this conversation.
        </p>
    @endguest

    @if ($user?->is_muted)
        <div class="flex justify-center bg-embed p-2 rounded-lg -mx-2 w-[calc(100%+16px)] sm:mx-0 sm:w-full">
            <p class="text-center text-muted">
                You are muted until
                {{ getNiceDate($user->muted_until->timestamp) }}.
            </p>
        </div>
    @endif

    @can('create', [App\Models\ForumTopicComment::class, $forumTopic])
        <x-section>
            <div class="flex bg-embed p-2 rounded-lg -mx-2 w-[calc(100%+16px)] sm:mx-0 sm:w-full">
                <div class="hidden sm:flex flex-col gap-1 justify-start items-center lg:border-r border-neutral-700 px-0.5 pb-2 lg:py-2 lg:w-44">
                    <x-user.avatar :user="$user" display="icon" iconSize="md" class="rounded-sm" />
                    <x-user.avatar :user="$user" />
                </div>

                <div class="grow lg:py-0 px-1 lg:px-6 pt-2 pb-4">
                    <livewire:forum.create-topic-comment-form :$forumTopic />
                </div>
            </div>
        </x-section>
    @endcan
</x-app-layout>
