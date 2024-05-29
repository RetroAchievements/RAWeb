<?php

// TODO migrate to ForumController::show() pages/forum.blade.php

use App\Enums\Permissions;
use App\Models\Forum;
use App\Models\User;
use App\Support\Shortcode\Shortcode;

authenticateFromCookie($user, $permissions, $userDetails);

$userModel = Auth::user();

$requestedForumID = requestInputSanitized('f', null, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');
$count = requestInputSanitized('c', 25, 'integer');

$numUnofficialLinks = 0;
if ($permissions >= Permissions::Moderator) {
    $unofficialLinks = getUnauthorisedForumLinks();
    $numUnofficialLinks = is_countable($unofficialLinks) ? count($unofficialLinks) : 0;
}

$numTotalTopics = 0;

if ($requestedForumID == 0 && $permissions < Permissions::Moderator) {
    abort(404);
}

if ($requestedForumID == 0 && $permissions >= Permissions::Moderator) {
    $viewingUnauthorisedForumLinks = true;

    $thisForumID = 0;
    $thisForumTitle = "Forum Verification";
    $thisForumDescription = "Unverified Posts";
    $thisCategoryID = 0;
    $thisCategoryName = null;

    $topicList = $unofficialLinks;

    $requestedForum = "Forum Verification";
} else {
    $forum = Forum::find($requestedForumID);
    if (!$forum) {
        abort(404);
    }

    $thisForumID = $forum->id;
    $thisForumTitle = $forum->title;
    $thisForumDescription = $forum->description;
    $thisCategoryID = $forum->category->id;
    $thisCategoryName = $forum->category->title;

    $topicList = getForumTopics($requestedForumID, $offset, $count, $permissions, $numTotalTopics);

    $requestedForum = $thisForumTitle;
}

sanitize_outputs(
    $requestedForum,
    $thisForumTitle,
    $thisForumDescription,
    $thisCategoryName,
);
?>
<x-app-layout pageTitle="Forum: {{ $thisForumTitle }}">
    <?php
    echo "<div class='navpath'>";
    echo "<a href='/forum.php'>Forum Index</a>";
    if ($thisCategoryName) {
        echo " &raquo; <a href='/forum.php?c=$thisCategoryID'>$thisCategoryName</a>";
    }
    echo " &raquo; <b>$requestedForum</b></a>";
    echo "</div>";

    if ($numUnofficialLinks > 0) {
        echo "<div class='my-3 bg-embedded p-2 rounded-sm'>";
        echo "<b>Administrator Notice:</b> <a href='/viewforum.php?f=0'>$numUnofficialLinks unverified posts need authorising: please verify them!</a>";
        echo "</div>";
    }

    // echo "<h2><a href='/forum.php?c=$nextCategoryID'>$nextCategory</a></h2>";
    echo "<h2>$requestedForum</h2>";
    echo "<p class='mb-5'>$thisForumDescription</p>";

    echo "<div class='flex justify-between items-center'>";
    if ($numTotalTopics > $count) {
        echo "<div>";
        RenderPaginator($numTotalTopics, $count, $offset, "/viewforum.php?f=$requestedForumID&o=");
        echo "</div>";
    }
    if ($requestedForumID && $userModel?->can('create', [App\Models\ForumTopic::class, $forum])) {
        echo "<a class='btn btn-link' href='createtopic.php?forum=$thisForumID'>Create New Topic</a>";
    }
    echo "</div>";

    echo "<table class='table-highlight my-3'><tbody>";

    echo "<tr class='do-not-highlight'>";
    echo "<th></th>";
    echo "<th class='w-full xl:w-[60%]'>Topics</th>";
    echo "<th>Author</th>";
    echo "<th>Replies</th>";
    echo "<th class='whitespace-nowrap xl:text-right'>Last post</th>";
    echo "</tr>";

    $topicCount = is_countable($topicList) ? count($topicList) : 0;

    $fetchedUserCache = [];

    // Output all topics, and offer 'prev/next page'
    foreach ($topicList as $topicData) {
        // Output one forum, then loop
        $nextTopicID = $topicData['ForumTopicID'];
        $nextTopicTitle = $topicData['TopicTitle'];
        $nextTopicPreview = $topicData['TopicPreview'];
        $nextTopicPostedDate = $topicData['ForumTopicPostedDate'];
        $nextTopicLastCommentID = $topicData['LatestCommentID'];
        $nextTopicLastCommentPostedDate = $topicData['LatestCommentPostedDate'];
        $nextTopicNumReplies = $topicData['NumTopicReplies'];

        $nextTopicAuthorID = $topicData['AuthorID'];
        $nextTopicLastCommentAuthorID = $topicData['LatestCommentAuthorID'];
        $fetchedUserCache[$nextTopicAuthorID] ??= User::find($nextTopicAuthorID);
        $fetchedUserCache[$nextTopicLastCommentAuthorID] ??= User::find($nextTopicLastCommentAuthorID);
        $nextTopicAuthor = $fetchedUserCache[$nextTopicAuthorID] ?? null;
        $nextTopicLastCommentAuthor = $fetchedUserCache[$nextTopicLastCommentAuthorID] ?? null;

        sanitize_outputs($nextTopicTitle, $nextTopicPreview);

        $nextTopicPostedNiceDate = $nextTopicPostedDate !== null ? getNiceDate(strtotime($nextTopicPostedDate)) : "None";

        if ($nextTopicLastCommentPostedDate !== null) {
            $nextTopicLastCommentPostedNiceDate = getNiceDate(strtotime($nextTopicLastCommentPostedDate));
        } else {
            $nextTopicLastCommentPostedNiceDate = "None";
        }

        echo "<tr>";

        echo "<td class='p-1' aria-hidden='true'>";
        ?>
        <x-fas-arrow-alt-circle-right class="h-4 w-4" />
        <?php
        echo "</td>";
        echo "<td>";
        echo "<a href='/viewtopic.php?t=$nextTopicID'>$nextTopicTitle</a>";
        echo "<div class='mb-1' style='word-break:break-word'>";
        echo Shortcode::stripAndClamp("$nextTopicPreview...", previewLength: 57);
        echo "</div>";
        echo "</td>";
        echo "<td>";
        echo "<div>" . userAvatar($nextTopicAuthor ?? 'Deleted User', icon: false) . "<br><span class='smalldate'>$nextTopicPostedNiceDate</span></div>";
        echo "</td>";
        echo "<td>" . localized_number($nextTopicNumReplies) . "</td>";
        echo "<td>";
        echo "<div class='xl:flex xl:flex-col xl:items-end xl:gap-y-0.5'>" . userAvatar($nextTopicLastCommentAuthor ?? 'Deleted User', icon: false) . "<span class='smalldate'>$nextTopicLastCommentPostedNiceDate</span>";
        echo "<a class='text-2xs' href='viewtopic.php?t=$nextTopicID&amp;c=$nextTopicLastCommentID#$nextTopicLastCommentID' title='View latest post'>View</a>";
        echo "</div>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "<div class='flex justify-between items-center'>";
    if ($numTotalTopics > $count) {
        echo "<div>";
        RenderPaginator($numTotalTopics, $count, $offset, "/viewforum.php?f=$requestedForumID&o=");
        echo "</div>";
    }
    if ($requestedForumID && $userModel?->can('create', [App\Models\ForumTopic::class, $forum])) {
        echo "<a class='btn btn-link' href='createtopic.php?forum=$thisForumID'>Create New Topic</a>";
    }
    echo "</div>";
    ?>
    <x-slot name="sidebar">
        <x-forum-recent-activity :numToFetch="8" />
    </x-slot>
</x-app-layout>
