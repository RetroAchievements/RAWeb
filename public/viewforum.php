<?php

use RA\Permissions;

authenticateFromCookie($user, $permissions, $userDetails);

$requestedForumID = requestInputSanitized('f', null, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');
$count = requestInputSanitized('c', 25, 'integer');

$numUnofficialLinks = 0;
if ($permissions >= Permissions::Admin) {
    $unofficialLinks = getUnauthorisedForumLinks();
    $numUnofficialLinks = is_countable($unofficialLinks) ? count($unofficialLinks) : 0;
}

$numTotalTopics = 0;

if ($requestedForumID == 0 && $permissions < Permissions::Admin) {
    abort(404);
}

if ($requestedForumID == 0 && $permissions >= Permissions::Admin) {
    $viewingUnauthorisedForumLinks = true;

    $thisForumID = 0;
    $thisForumTitle = "Unauthorised Links";
    $thisForumDescription = "Unauthorised Links";
    $thisCategoryID = 0;
    $thisCategoryName = "Unauthorised Links";

    $topicList = getUnauthorisedForumLinks();

    $requestedForum = "Unauthorised Links";
} else {
    if (!getForumDetails($requestedForumID, $forumDataOut)) {
        abort(404);
    }

    $thisForumID = $forumDataOut['ID'];
    $thisForumTitle = $forumDataOut['ForumTitle'];
    $thisForumDescription = $forumDataOut['ForumDescription'];
    $thisCategoryID = $forumDataOut['CategoryID'];
    $thisCategoryName = $forumDataOut['CategoryName'];

    $topicList = getForumTopics($requestedForumID, $offset, $count, $permissions, $numTotalTopics);

    $requestedForum = $thisForumTitle;
}

sanitize_outputs(
    $requestedForum,
    $thisForumTitle,
    $thisForumDescription,
    $thisCategoryName,
);

RenderContentStart("Forum: $thisForumTitle");
?>
<div id="mainpage">
    <div id="leftcontainer">
        <div id="forums">
            <?php
            echo "<div class='navpath'>";
            echo "<a href='/forum.php'>Forum Index</a>";
            echo " &raquo; <a href='/forum.php?c=$thisCategoryID'>$thisCategoryName</a>";
            echo " &raquo; <b>$requestedForum</b></a>";
            echo "</div>";

            if ($numUnofficialLinks > 0) {
                echo "<div class='my-3 bg-embedded p-2 rounded-sm'>";
                echo "<b>Administrator Notice:</b> <a href='/viewforum.php?f=0'>$numUnofficialLinks unofficial posts need authorising: please verify them!</a>";
                echo "</div>";
            }

            // echo "<h2 class='longheader'><a href='/forum.php?c=$nextCategoryID'>$nextCategory</a></h2>";
            echo "<h2>$requestedForum</h2>";
            echo "<p class='mb-5'>$thisForumDescription</p>";

            echo "<div class='flex justify-between items-center'>";
            if ($numTotalTopics > $count) {
                echo "<div>";
                RenderPaginator($numTotalTopics, $count, $offset, "/viewforum.php?f=$requestedForumID&o=");
                echo "</div>";
            }
            if ($permissions >= Permissions::Registered) {
                echo "<a class='btn btn-link' href='createtopic.php?forum=$thisForumID'>Create New Topic</a>";
            }
            echo "</div>";

            echo "<table class='my-3'><tbody>";
            echo "<tr class='forumsheader'>";
            echo "<th></th>";
            echo "<th class='w-full'>Topics</th>";
            echo "<th>Author</th>";
            echo "<th>Replies</th>";
            // echo "<th>Views</th>";
            echo "<th class='whitespace-nowrap'>Last post</th>";
            echo "</tr>";

            $topicCount = is_countable($topicList) ? count($topicList) : 0;

            // Output all topics, and offer 'prev/next page'
            foreach ($topicList as $topicData) {
                // Output one forum, then loop
                $nextTopicID = $topicData['ForumTopicID'];
                $nextTopicTitle = $topicData['TopicTitle'];
                $nextTopicPreview = $topicData['TopicPreview'];
                $nextTopicAuthor = $topicData['Author'];
                $nextTopicAuthorID = $topicData['AuthorID'];
                $nextTopicPostedDate = $topicData['ForumTopicPostedDate'];
                $nextTopicLastCommentID = $topicData['LatestCommentID'];
                $nextTopicLastCommentAuthor = $topicData['LatestCommentAuthor'];
                $nextTopicLastCommentAuthorID = $topicData['LatestCommentAuthorID'];
                $nextTopicLastCommentPostedDate = $topicData['LatestCommentPostedDate'];
                $nextTopicNumReplies = $topicData['NumTopicReplies'];

                sanitize_outputs(
                    $nextTopicTitle,
                    $nextTopicPreview,
                    $nextTopicAuthor,
                    $nextTopicLastCommentAuthor,
                );

                if ($nextTopicPostedDate !== null) {
                    $nextTopicPostedNiceDate = getNiceDate(strtotime($nextTopicPostedDate));
                } else {
                    $nextTopicPostedNiceDate = "None";
                }

                if ($nextTopicLastCommentPostedDate !== null) {
                    $nextTopicLastCommentPostedNiceDate = getNiceDate(strtotime($nextTopicLastCommentPostedDate));
                } else {
                    $nextTopicLastCommentPostedNiceDate = "None";
                }

                echo "<tr>";

                echo "<td class='unreadicon p-1'><img src='" . asset('assets/images/icon/forum-topic-unread.gif') . "' width='20' height='20' title='No unread posts' alt='No unread posts'></img></td>";
                echo "<td class='topictitle'><a alt='Posted $nextTopicPostedNiceDate' title='Posted on $nextTopicPostedNiceDate' href='/viewtopic.php?t=$nextTopicID'>$nextTopicTitle</a><br><div id='topicpreview'>$nextTopicPreview...</div></td>";
                echo "<td class='author'>";
                echo GetUserAndTooltipDiv($nextTopicAuthor);
                echo "</td>";
                // echo "<td class='author'><div class='author'><a href='/user/$nextTopicAuthor'>$nextTopicAuthor</a></div></td>";
                echo "<td class='replies'>$nextTopicNumReplies</td>";
                // echo "<td class='views'>$nextForumNumViews</td>";
                echo "<td class='lastpost'>";
                echo "<div class='lastpost'>";
                echo GetUserAndTooltipDiv($nextTopicLastCommentAuthor);
                echo "<br><span class='smalldate'>$nextTopicLastCommentPostedNiceDate</span>";
                // echo "<a href='/user/$nextTopicLastCommentAuthor'>$nextTopicLastCommentAuthor</a>";
                echo "<br><a class='btn btn-link' href='viewtopic.php?t=$nextTopicID&amp;c=$nextTopicLastCommentID#$nextTopicLastCommentID' title='View latest post'>View</a>";
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
            if ($permissions >= Permissions::Registered) {
                echo "<a class='btn btn-link' href='createtopic.php?forum=$thisForumID'>Create New Topic</a>";
            }
            echo "</div>";
            ?>
        </div>
    </div>
    <div id="rightcontainer">
        <?php
        RenderRecentForumPostsComponent($permissions, 8);
        ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
