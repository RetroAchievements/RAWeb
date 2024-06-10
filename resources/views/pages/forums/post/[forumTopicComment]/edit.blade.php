<?php

use App\Models\ForumTopicComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth']);
name('forum.post.edit');

render(function (View $view, ForumTopicComment $forumTopicComment) {
    if (!Auth::user()->can('update', $forumTopicComment)) {
        return abort(401);
    }
    
    return $view->with([
        'category' => $forumTopicComment->forumTopic->forum->category,
        'forum' => $forumTopicComment->forumTopic->forum,
        'forumTopic' => $forumTopicComment->forumTopic,
        'forumTopicComment' => $forumTopicComment,
    ]);
});

?>

@props([
    'category' => null, // ForumCategory
    'forum' => null, // Forum
    'forumTopic' => null, // ForumTopic
    'forumTopicComment' => null, // ForumTopicComment
])

<x-app-layout pageTitle="Edit post">
    {{-- TODO forum breadcrumbs component --}}
    <div class="navpath">
        <a href="forum.php">Forum Index</a>
        &raquo; <a href="/forum.php?c={{ $category->id }}">{{ $category->title }}</a>
        &raquo; <a href="/viewforum.php?f={{ $forum->id }}">{{ $forum->title }}</a>
        &raquo; <a href="{{ route('forum.topic', ['forumTopic' => $forumTopic]) }}">{{ $forumTopic->title }}</a>
        &raquo; <b>Edit Post</b></a>
    </div>

    <h1 class="text-h2">Edit post</h1>

    <x-section>
        <livewire:forum.edit-topic-comment-form :$forumTopicComment />
    </x-section>
</x-app-layout>
