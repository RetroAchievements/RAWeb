<?php

use App\Enums\Permissions;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$requestedComment = (int) request()->query('comment');
if (empty($requestedComment)) {
    abort(404);
}

$foundPost = ForumTopicComment::find($requestedComment);
if (!$foundPost) {
    abort(404);
}

if ($user !== $foundPost->user->User && $permissions < Permissions::Moderator) {
    abort_with(back()->withErrors(__('legacy.error.permissions')));
}

$topic = ForumTopic::with('forum.category')->find($foundPost->ForumTopicID);
if (!$topic) {
    abort(404);
}

?>

<x-app-layout pageTitle="Edit post">
    <div class="navpath">
        <a href="forum.php">Forum Index</a>
        &raquo; <a href="/forum.php?c={{ $topic->forum->category->ID }}">{{ $topic->forum->category->Name }}</a>
        &raquo; <a href="/viewforum.php?f={{ $topic->ForumID }}">{{ $topic->forum->Title }}</a>
        &raquo; <a href="/viewtopic.php?t={{ $topic->ID }}">{{ $topic->Title }}</a>
        &raquo; <b>Edit Post</b></a>
    </div>

    <h1 class="text-h2">Edit post</h1>

    <x-section>
        <x-base.form action="{{ url('request/forum-topic-comment/update.php') }}">
            <div class="flex flex-col gap-y-3">
                <x-base.form.input type="hidden" name="comment" value="{{ $foundPost->ID }}" />

                <x-base.form.input label="{{ __res('forum', 1) }}" readonly value="{{ $topic->forum->Title }}" inline :fullWidth="false" />
                <x-base.form.input label="{{ __res('author', 1) }}" readonly value="{{ $topic->user->User }}" inline :fullWidth="false" />
                <x-base.form.input label="{{ __res('forum-topic', 1) }}" readonly :value="$topic->Title" inline />

                <x-base.form.textarea
                    id="input_compose"
                    label="{{ __res('message', 1)}} "
                    :value="$foundPost->Payload"
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
