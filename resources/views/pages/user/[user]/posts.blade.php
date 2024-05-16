<?php

use App\Community\Services\ForumRecentPostsPageService;
use App\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{name, middleware, render};

name('user.posts');

render(function (View $view, User $user, ForumRecentPostsPageService $pageService) {
    $currentUser = Auth::user();


    if (!$currentUser->can('viewUserPosts', [\App\Models\ForumTopicComment::class, $user])) {
        // Traditionally this would probably be a 401. However, 
        // a 401 might imply the target user has blocked the current user.
        // Returning a 404 is a bit less presumptuous.
        abort(404);
    }

    return $view->with(
        $pageService->buildViewData(
            $currentUser,
            request()->input('offset', 0),
            $user,
        )
    );
});

?>

<x-app-layout
    pageTitle="Recent Posts - {{ $targetUser->display_name }}"
    pageDescription="A list of {{ $targetUser->display_name }}'s recent posts that have been made on the RetroAchievements forum."
>
    <x-user.breadcrumbs
        :targetUsername="$targetUser->display_name"
        currentPage="Recent Posts"
    />

    <div class="mt-3 mb-3 w-full relative flex gap-x-3">
        {!! userAvatar($targetUser->display_name, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $targetUser->display_name }}'s Recent Posts</h1>
    </div>

    <div class="lg:hidden">
        <x-forum.recent-posts.mobile-cards
            :isForSpecificUser="true"
            :recentForumPosts="$recentForumPosts"
        />
    </div>

    <div class="hidden lg:block">
        <x-forum.recent-posts.desktop-table
            :isForSpecificUser="true"
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
