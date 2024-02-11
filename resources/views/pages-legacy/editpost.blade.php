<?php

use App\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$requestedComment = (int) request()->query('comment');

if (empty($requestedComment)) {
    abort(404);
}
if (!getSingleTopicComment($requestedComment, $commentData)) {
    abort(404);
}
if (empty($commentData)) {
    abort(404);
}

if ($user != $commentData['Author'] && $permissions < Permissions::Moderator) {
    abort_with(back()->withErrors(__('legacy.error.permissions')));
}

if (!getTopicDetails($commentData['ForumTopicID'], $topicData)) {
    abort(404);
}
if (empty($topicData)) {
    abort(404);
}

$thisForumID = $topicData['ForumID'];
$thisForumTitle = htmlentities($topicData['Forum']);
$thisCategoryID = $topicData['CategoryID'];
$thisCategoryName = htmlentities($topicData['Category']);

$thisForumTitle = htmlentities($topicData['Forum']);
$thisTopicTitle = htmlentities($topicData['TopicTitle']);
$thisTopicID = $commentData['ForumTopicID'];
$thisTopicAuthor = $topicData['Author'];
$thisAuthor = $commentData['Author'];
?>
<x-app-layout pageTitle="Edit post">
    <div class="navpath">
        <a href="forum.php">Forum Index</a>
        &raquo; <a href="/forum.php?c={{ $thisCategoryID }}">{{ $thisCategoryName }}</a>
        &raquo; <a href="/viewforum.php?f={{ $thisForumID }}">{{ $thisForumTitle }}</a>
        &raquo; <a href="/viewtopic.php?t={{ $thisTopicID }}">{{ $thisTopicTitle }}</a>
        &raquo; <b>Edit Post</b></a>
    </div>

    <h2>Edit post</h2>

    <x-section>
        <x-form action="{{ url('request/forum-topic-comment/update.php') }}">
            <x-input.text type="hidden" name="comment" value="{{ $commentData['ID'] }}" />
            <x-input.text label="{{ __res('forum', 1) }}" readonly value="{{ $thisForumTitle }}" inline :fullWidth="false" />
            <x-input.text label="{{ __res('author', 1) }}" readonly value="{{ $thisAuthor }}" inline :fullWidth="false" />
            <x-input.text label="{{ __res('forum-topic', 1) }}" readonly value="{{ $thisTopicTitle }}" inline />
            <x-input.textarea
                label="{{ __res('message', 1)}} "
                value="{{ $commentData['Payload'] }}"
                maxlength="60000"
                name="body"
                rows="22"
                help="Don't share links to copyrighted ROMs."
                placeholder="Don't share links to copyrighted ROMs."
                inline
                required-silent
                richText
            />
            <x-form-actions inline />
        </x-form>
    </x-section>
</x-app-layout>
