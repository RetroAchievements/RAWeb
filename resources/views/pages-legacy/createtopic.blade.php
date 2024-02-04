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

$existingComment = old('body');
?>
<x-app-layout pageTitle="Create topic: {{ $thisForumTitle }}">
    <div class="navpath">
        <a href="forum.php">Forum Index</a>
        &raquo; <a href='/forum.php?c={{ $thisCategoryID }}'>{{ $thisCategoryName }}</a>
        &raquo; <a href='/viewforum.php?f={{ $thisForumID }}'>{{ $thisForumTitle }}</a>
        &raquo; <b>Create Topic</b>
    </div>

    <h2>Create Topic: {{ $thisForumTitle }}</h2>

    <x-form action="/request/forum-topic/create.php">
        <input type="hidden" value="{{ $requestedForumID }}" name="forum">
        <table>
            <tbody x-data='{ isValid: true }'>
                <tr><td>Forum:</td><td><input type="text" readonly value="{{ $thisForumTitle }}"></td></tr>
                <tr><td>Author:</td><td><input type="text" readonly value="{{ $user }}"></td></tr>
                <tr><td>Topic:</td><td><input class="w-full" type="text" value="" name="title" value="{{ old('title') }}"></td></tr>
                <tr>
                    <td>Message:</td>
                    <td>
                        <?php
                        RenderShortcodeButtons();
                        ?>
                        <textarea
                            id="commentTextarea"
                            class="w-full"
                            style="height:300px"
                            rows="32" cols="32"
                            maxlength="60000"
                            name="body"
                            placeholder="Don't share links to copyrighted ROMs."
                            x-on:input='autoExpandTextInput($el); isValid = window.getStringByteCount($event.target.value) <= 60000;'
                        ><?= $existingComment ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <div class="flex justify-between items-center">
                            <div class="textarea-counter text-right" data-textarea-id="commentTextarea"></div>
                            <div>
                                <x-fas-spinner id="preview-loading-icon" class="opacity-0 transition-all duration-200" aria-hidden="true" />
                                <button id="preview-button" type="button" class="btn" onclick="window.loadPostPreview()" :disabled="!isValid">Preview</button>
                                <button class="btn" :disabled="!isValid">Submit new topic</button>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </x-form>
    <div id='post-preview'></div>
</x-app-layout>
