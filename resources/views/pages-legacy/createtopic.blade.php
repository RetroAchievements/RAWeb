<?php

use App\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$requestedForumID = (int) request()->query('forum');

if (empty($requestedForumID)) {
    abort(404);
}

if (!getForumDetails($requestedForumID, $forumData)) {
    abort(404);
}
if (empty($forumData)) {
    abort(404);
}

$thisForumID = $forumData['ID'];
$thisForumTitle = htmlentities($forumData['ForumTitle']);
$thisCategoryID = $forumData['CategoryID'];
$thisCategoryName = htmlentities($forumData['CategoryName']);
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
        <x-form action="{{ url('request/forum-topic/create.php') }}" validate>
            <x-base.form.input type="hidden" name="forum" value="{{ $requestedForumID }}" />
            <x-base.form.input label="{{ __res('forum', 1) }}" value="{{ $thisForumTitle }}" inline readonly :fullWidth="false" />
            <x-base.form.input label="{{ __res('author', 1) }}" value="{{ $user }}" inline readonly :fullWidth="false" />
            <x-base.form.input name="title" inline />
            <x-base.form.textarea
                maxlength="60000"
                name="body"
                rows="22"
                help="Don't share links to copyrighted ROMs."
                placeholder="Don't share links to copyrighted ROMs."
                inline
                required-silent
                richText
            />
            <x-form-actions submitLabel="Submit new topic" inline />
        </x-form>
    </x-section>
</x-app-layout>
