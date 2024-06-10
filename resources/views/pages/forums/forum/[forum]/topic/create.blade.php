<?php

use App\Models\Forum;
use Illuminate\View\View;

use function Laravel\Folio\{name, middleware, render};

name('forum-topic.create');
middleware(['auth']);

render(function (View $view, Forum $forum) {
    $user = Auth::user();

    if (!$user->can('create', [App\Models\ForumTopic::class, $forum])) {
        return abort(401);
    }

    return $view->with([
        'forum' => $forum,
        'user' => $user,
    ]);
});

?>

<x-app-layout
    pageTitle="Start new topic"
    pageDescription="Start a new forum topic for {{ $forum->title }}"
>
    {{-- TODO forum breadcrumbs component --}}
    <div class="navpath">
        <a href="/forum.php">Forum Index</a>
        &raquo; <a href='/forum.php?c={{ $forum->category->id }}'>{{ $forum->category->title }}</a>
        &raquo; <a href='/viewforum.php?f={{ $forum->id }}'>{{ $forum->title }}</a>
        &raquo; <b>Start new topic</b>
    </div>

    <h1 class="text-h2">Start new topic</h1>

    <x-section>
        <livewire:forum.create-topic-form :$forum />
    </x-section>
</x-app-layout>
