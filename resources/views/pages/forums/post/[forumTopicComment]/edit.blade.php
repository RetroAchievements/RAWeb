<?php

use App\Models\ForumTopicComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth']);
name('forum.post.edit');

render(function (View $view, ForumTopicComment $forumTopicComment) {
    if (!Auth::user()->can('update', $forumTopicComment)) {
        return abort('401');
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
    <div class="navpath">
        <a href="forum.php">Forum Index</a>
        &raquo; <a href="/forum.php?c={{ $category->id }}">{{ $category->title }}</a>
        &raquo; <a href="/viewforum.php?f={{ $forum->id }}">{{ $forum->title }}</a>
        &raquo; <a href="/viewtopic.php?t={{ $forumTopic->id }}">{{ $forumTopic->title }}</a>
        &raquo; <b>Edit Post</b></a>
    </div>

    <h1 class="text-h2">Edit post</h1>

    <x-section>
        <x-base.form action="{{ url('request/forum-topic-comment/update.php') }}">
            <div class="flex flex-col gap-y-3">
                <x-base.form.input type="hidden" name="comment" value="{{ $forumTopicComment->id }}" />

                <x-base.form.input label="{{ __res('forum', 1) }}" readonly value="{{ $forum->title }}" inline :fullWidth="false" />
                <x-base.form.input label="{{ __res('author', 1) }}" readonly value="{{ $forumTopic->user?->display_name ?? 'Deleted user' }}" inline :fullWidth="false" />
                <x-base.form.input label="{{ __res('forum-topic', 1) }}" readonly :value="$forumTopic->title" inline />

                <x-base.form.textarea
                    id="input_compose"
                    label="{{ __res('message', 1)}} "
                    :value="$forumTopicComment->body"
                    maxlength="60000"
                    name="body"
                    rows="22"
                    placeholder="Don't share links to copyrighted ROMs."
                    inline
                    required-silent
                    richText
                >
                    <x-slot name="formActions">
                        <x-base.form-actions />
                    </x-slot>
                </x-base.form.textarea>
            </div>
        </x-base.form>

        <div id="post-preview-input_compose"></div>
    </x-section>
</x-app-layout>
