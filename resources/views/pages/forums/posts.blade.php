<?php

use App\Community\Services\ForumRecentPostsPageService;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{name, middleware, render};

name('forum.posts');

render(function (View $view, ForumRecentPostsPageService $pageService) {
    $user = Auth::user();

    return $view->with(
        $pageService->buildViewData(
            $user,
            currentOffset: request()->input('offset', 0),
        )
    );
});

?>

@props([
    'maxPerPage' => 25,
    'nextPageUrl' => null, // ?string
    'previousPageUrl' => null, // ?string
    'recentForumPosts' => [],
])

<x-app-layout
    pageTitle="Recent Posts"
    pageDescription="A list of recent posts that have been made on the RetroAchievements forum."
>
    <x-forum.breadcrumbs currentPageLabel="Recent Posts" />

    <h1 class="w-full">Recent Posts</h1>

    <div class="lg:hidden">
        <x-forum.recent-posts.mobile-cards
            :recentForumPosts="$recentForumPosts"
        />
    </div>

    <div class="hidden lg:block">
        <x-forum.recent-posts.desktop-table
            :recentForumPosts="$recentForumPosts"
        />
    </div>

    @if ($previousPageUrl || $nextPageUrl)
        <x-forum.recent-posts.paginator
            :maxPerPage="$maxPerPage"
            :nextPageUrl="$nextPageUrl"
            :previousPageUrl="$previousPageUrl"
        />
    @endif
</x-app-layout>
