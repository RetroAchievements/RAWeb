<?php

use App\Enums\Permissions;
use App\Models\Forum;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$requestedForumID = (int) request()->query('forum');
$userModel = request()->user();

if (empty($requestedForumID)) {
    abort(404);
}

$forum = Forum::find($requestedForumID);
if (!$forum) {
    abort(404);
}
if (!$userModel->can('create', [App\Models\ForumTopic::class, $forum])) {
    abort(401);
}

$thisForumID = $forum->id;
$thisForumTitle = htmlentities($forum->title);
$thisCategoryID = $forum->category->id;
$thisCategoryName = htmlentities($forum->category->title);
?>
<x-app-layout pageTitle="Create topic: {{ $thisForumTitle }}">
    <div class="navpath">
        <a href="forum.php">Forum Index</a>
        &raquo; <a href='/forum.php?c={{ $thisCategoryID }}'>{{ $thisCategoryName }}</a>
        &raquo; <a href='/viewforum.php?f={{ $thisForumID }}'>{{ $thisForumTitle }}</a>
        &raquo; <b>Create Topic</b>
    </div>

    <h2>Create Topic: {{ $thisForumTitle }}</h2>

    <x-section>
        <x-base.form action="{{ url('request/forum-topic/create.php') }}" validate>
            <div class="flex flex-col gap-y-3">
                <x-base.form.input type="hidden" name="forum" value="{{ $requestedForumID }}" />
                <x-base.form.input label="{{ __res('forum', 1) }}" value="{{ $thisForumTitle }}" inline readonly :fullWidth="false" />
                <x-base.form.input label="{{ __res('author', 1) }}" value="{{ $user }}" inline readonly :fullWidth="false" />
                <x-base.form.input name="title" inline />
                <x-base.form.textarea
                    id="input_compose"
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
