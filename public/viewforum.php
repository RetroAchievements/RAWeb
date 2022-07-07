<?php

use RA\Permissions;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

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
    header("location: " . getenv('APP_URL') . "/forum.php?e=unknownforum");
    exit;
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
        header("location: " . getenv('APP_URL') . "/forum.php?e=unknownforum");
        exit;
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

$errorCode = requestInputSanitized('e');
$mobileBrowser = IsMobileBrowser();

RenderHtmlStart();
RenderHtmlHead("View forum: $thisForumTitle");
?>
<body>
<?php RenderHeader($userDetails); ?>
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
                echo "<br><a href='/viewforum.php?f=0'><b>Administrator Notice:</b> $numUnofficialLinks unofficial posts need authorising: please verify them!</a><br><br>";
            }

            // echo "<h2 class='longheader'><a href='/forum.php?c=$nextCategoryID'>$nextCategory</a></h2>";
            echo "<h3 class='longheader'>$requestedForum</h3>";
            echo "$thisForumDescription<br><br>";

            if ($numTotalTopics > $count) {
                RenderPaginator($numTotalTopics, $count, $offset, "/viewforum.php?f=$requestedForumID&o=");
            }

            if ($permissions >= Permissions::Registered) {
                echo "<div class='createtopic'><a href='createtopic.php?f=$thisForumID'>Create New Topic</div></a>";
            } else {
                echo "<div class='rightfloat'><span class='unregisteredwarning'>Unregistered: please check your email registration link!</span></div>";
            }
            echo "<table><tbody>";
            echo "<tr class='forumsheader'>";
            echo "<th></th>";
            echo "<th class='fullwidth'>Topics</th>";
            echo "<th>Author</th>";
            echo "<th>Replies</th>";
            // echo "<th>Views</th>";
            echo "<th class='text-nowrap'>Last post</th>";
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

                echo "<td class='unreadicon p-1'><img src='" . asset('Images/ForumTopicUnread32.gif') . "' width='20' height='20' title='No unread posts' alt='No unread posts'></img></td>";
                echo "<td class='topictitle'><a alt='Posted $nextTopicPostedNiceDate' title='Posted on $nextTopicPostedNiceDate' href='/viewtopic.php?t=$nextTopicID'>$nextTopicTitle</a><br><div id='topicpreview'>$nextTopicPreview...</div></td>";
                echo "<td class='author'>";
                echo GetUserAndTooltipDiv($nextTopicAuthor, $mobileBrowser);
                echo "</td>";
                // echo "<td class='author'><div class='author'><a href='/user/$nextTopicAuthor'>$nextTopicAuthor</a></div></td>";
                echo "<td class='replies'>$nextTopicNumReplies</td>";
                // echo "<td class='views'>$nextForumNumViews</td>";
                echo "<td class='lastpost'>";
                echo "<div class='lastpost'>";
                echo "<span class='smalldate'>$nextTopicLastCommentPostedNiceDate</span><br>";
                echo GetUserAndTooltipDiv($nextTopicLastCommentAuthor, $mobileBrowser);
                // echo "<a href='/user/$nextTopicLastCommentAuthor'>$nextTopicLastCommentAuthor</a>";
                echo " <a href='viewtopic.php?t=$nextTopicID&amp;c=$nextTopicLastCommentID#$nextTopicLastCommentID' class='forumviewcompetitions' title='View latest post' alt='View latest post'>View</a>";
                echo "</div>";
                echo "</td>";
                echo "</tr>";
            }

            if ($topicCount % 2 == 1) {
                echo "<tr><td colspan=5 class='smalltext'></td></tr>";
            }
            echo "<tr><td colspan=5 class='smalltext'></td></tr>";

            echo "</tbody></table>";

            if ($numTotalTopics > $count) {
                RenderPaginator($numTotalTopics, $count, $offset, "/viewforum.php?f=$requestedForumID&o=");
            }

            if ($permissions >= Permissions::Registered) {
                echo "<div class='createtopic'><a href='createtopic.php?f=$thisForumID'>Create New Topic</a></div>";
            }

            echo "<br>";

            ?>
        </div>
    </div>
    <div id="rightcontainer">
        <?php
        RenderRecentForumPostsComponent($permissions, 8);
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
