<?php

// TODO migrate to ForumTopicController::show() pages/forum/topic.blade.php

use App\Community\Enums\SubscriptionSubjectType;
use App\Models\ForumTopicComment;
use App\Enums\Permissions;
use App\Support\Shortcode\Shortcode;

authenticateFromCookie($user, $permissions, $userDetails);
$userID = $userDetails['ID'] ?? 0;

// Fetch topic ID
$requestedTopicID = requestInputSanitized('t', 0, 'integer');

if ($requestedTopicID == 0) {
    abort(404);
}

getTopicDetails($requestedTopicID, $topicData);

if (empty($topicData)) {
    abort(404);
}

if ($permissions < $topicData['RequiredPermissions']) {
    abort(403);
}

// Fetch other params
$count = 15;
$offset = requestInputSanitized('o', 0, 'integer');
if ($offset < 0) {
    $offset = 0;
}

// Fetch 'goto comment' param if available
$gotoCommentID = requestInputSanitized('c', null, 'integer');
if (!empty($gotoCommentID)) {
    // Override $offset, just find this comment and go to it.
    getTopicCommentCommentOffset($requestedTopicID, $gotoCommentID, $count, $offset);
}

// Fetch comments
$numTotalComments = ForumTopicComment::where('ForumTopicID', $requestedTopicID)->count();
$commentList = ForumTopicComment::with('user')
    ->where('ForumTopicID', $requestedTopicID)
    ->orderBy('DateCreated', 'asc')
    ->offset($offset)
    ->limit($count)
    ->get();

// We CANNOT have a topic with no comments... this doesn't make sense.
if (empty($commentList)) {
    abort(404);
}

$thisTopicID = $topicData['ID'];
$thisTopicID = (int) $thisTopicID;
$thisTopicAuthor = $topicData['Author'];
$thisTopicAuthorID = $topicData['AuthorID'];
$thisTopicCategory = $topicData['Category'];
$thisTopicCategoryID = $topicData['CategoryID'];
$thisTopicForum = $topicData['Forum'];
$thisTopicForumID = $topicData['ForumID'];
$thisTopicTitle = $topicData['TopicTitle'];
$thisTopicPermissions = $topicData['RequiredPermissions'];

$pageTitle = "Topic: {$thisTopicForum} - {$thisTopicTitle}";

sanitize_outputs(
    $thisTopicAuthor,
    $thisTopicCategory,
    $thisTopicForum,
    $thisTopicTitle,
);

$isSubscribed = isUserSubscribedToForumTopic($thisTopicID, $userID);
?>
<x-app-layout :pageTitle="$pageTitle">
    <?php
    echo "<div class='navpath'>";
    echo "<a href='/forum.php'>Forum Index</a>";
    echo " &raquo; <a href='forum.php?c=$thisTopicCategoryID'>$thisTopicCategory</a>";
    echo " &raquo; <a href='viewforum.php?f=$thisTopicForumID'>$thisTopicForum</a>";
    echo " &raquo; <b>$thisTopicTitle</b></a>";
    echo "</div>";

    echo "<h2>$thisTopicTitle</h2>";

    if (isset($user) && ($thisTopicAuthor == $user || $permissions >= Permissions::Moderator)) {
        echo "<div class='devbox mb-3'>";
        echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Options ▼</span>";
        echo "<div id='devboxcontent' style='display: none'>";

        echo "<div>Change Topic Title:</div>";
        echo "<form class='mb-3' action='/request/forum-topic/update-title.php' method='post'>";
        echo csrf_field();
        echo "<input type='hidden' name='topic' value='$thisTopicID'>";
        echo "<input type='text' name='title' value='$thisTopicTitle' size='51' >";
        echo "<button class='btn'>Submit</button>";
        echo "</form>";

        if ($permissions >= Permissions::Moderator) {
            echo "<div>Restrict Topic:</div>";
            echo "<form class='mb-3' action='/request/forum-topic/update-permissions.php' method='post'>";
            echo csrf_field();
            echo "<input type='hidden' name='topic' value='$thisTopicID'>";
            echo "<select name='permissions'>";
            foreach (Permissions::assignable() as $selectablePermission) {
                $selected = ($thisTopicPermissions == $selectablePermission) ? ' selected' : '';
                echo "<option value='$selectablePermission'$selected>" .
                    Permissions::toString($selectablePermission) . "</option>";
            }
            echo "</select>";
            echo "<button class='btn'>Change Minimum Permissions</button>";
            echo "</form>";

            echo "<form action='/request/forum-topic/delete.php' method='post' onsubmit='return confirm(\"Are you sure you want to permanently delete this topic?\")'>";
            echo csrf_field();
            echo "<input type='hidden' name='topic' value='$thisTopicID' />";
            echo "<button class='btn btn-danger'>Delete Permanently</button>";
            echo "</form>";
        }

        // TBD: Report offensive content
        // TBD: Subscribe to this topic
        // TBD: Make top-post wiki
        // if( ( $thisTopicAuthor == $user ) || $permissions >= Permissions::Developer )
        // {
        // echo "<li>Delete Topic!</li>";
        // echo "<form action='requestmodifytopic.php' >";
        // echo csrf_field();
        // echo "<input type='hidden' name='i' value='$thisTopicID' />";
        // echo "<input type='hidden' name='f' value='1' />";
        // echo "&nbsp;";
        // echo "<button class='btn' name='submit'>Delete Permanently</button>";
        // echo "</form>";
        // }

        echo "</div>";
        echo "</div>";
    }

    echo "<div class='flex justify-between mb-3'>";
    echo "<div>";
    if ($numTotalComments > $count) {
        RenderPaginator($numTotalComments, $count, $offset, "/viewtopic.php?t=$requestedTopicID&o=");
    }
    echo "</div>";
    echo "<div>";
    RenderUpdateSubscriptionForm(
        "updatetopicsubscription",
        SubscriptionSubjectType::ForumTopic,
        $thisTopicID,
        $isSubscribed
    );
    echo "</div>";
    echo "</div>";

    echo "<div class='mb-4'>";
    // Output all posts, and offer 'prev/next page'
    foreach ($commentList as $index => $commentData) {
        $nextCommentID = $commentData->ID;
        $nextCommentPayload = $commentData->Payload;
        $nextCommentAuthor = $commentData->Author;
        $nextCommentIndex = ($index + 1) + $offset; // Account for the current page on the post #.

        $isOriginalPoster = $nextCommentAuthor === $thisTopicAuthor;
        $isHighlighted = isset($gotoCommentID) && $nextCommentID == $gotoCommentID;
        $parsedPostContent = Shortcode::render($nextCommentPayload);
        ?>
        <x-forum.post
            :commentData="$commentData"
            :currentUser="$user"
            :currentUserPermissions="$permissions"
            :forumTopicId="$thisTopicID"
            :isHighlighted="$isHighlighted"
            :isOriginalPoster="$isOriginalPoster"
            :parsedPostContent="$parsedPostContent"
            :threadPostNumber="$nextCommentIndex"
            :nextCommentIndex="$nextCommentIndex"
            :permissions="$permissions"
            :user="$user"
        />
        <?php
    }
    echo "</div>";

    if ($numTotalComments > $count) {
        echo "<div class='mb-3'>";
        RenderPaginator($numTotalComments, $count, $offset, "/viewtopic.php?t=$requestedTopicID&o=");
        echo "</div>";
    }
    ?>

    <?php
    $user = auth()->user();
    ?>
    @if ($thisTopicID != 0 && $user?->hasVerifiedEmail())
        <x-section>
            <div class="flex w-full bg-embed p-2 rounded-lg">
                @guest
                    You must log in before you can join this conversation.
                @endguest

                @auth
                    <div class="flex flex-col gap-1 justify-start items-center lg:border-r border-neutral-700 px-0.5 pb-2 lg:py-2 lg:w-44">
                        <x-user.avatar :user="request()->user()" display="icon" iconSize="md" />
                        <x-user.avatar :user="request()->user()" />
                    </div>
                    <div class="grow lg:py-0 px-1 lg:px-6 pt-2 pb-4">
                        <x-base.form action="{{ url('request/forum-topic-comment/create.php') }}" validate>
                            <div class="flex flex-col gap-y-3">
                                <x-base.form.input type="hidden" name="topic" value="{{ $thisTopicID }}" />
                                <x-base.form.textarea
                                    label="{{ __('Reply') }}"
                                    maxlength="60000"
                                    name="body"
                                    rows="10"
                                    richText
                                    required-silent
                                    help="Don't share links to copyrighted ROMs."
                                    placeholder="Don't share links to copyrighted ROMs."
                                />
                                <x-base.form-actions submitLabel="Submit reply" />
                            </div>
                        </x-base.form>
                    </div>
                @endauth
            </div>
        </x-section>
    @endif
</x-app-layout>
