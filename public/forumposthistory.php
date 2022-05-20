<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$maxCount = 25;

$offset = requestInputSanitized('o', 0, 'integer');
$count = $maxCount;

authenticateFromCookie($user, $permissions, $userDetails);

$numPostsFound = getRecentForumPosts($offset, $count, 90, $permissions, $recentPostsData);

$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead("Forum Recent Posts");
?>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id='fullcontainer'>
        <div id="forums">
            <?php
            echo "<div class='navpath'>";
            echo "<a href='/forum.php'>Forum Index</a>";
            echo " &raquo; <b>Forum Post History</b></a>";
            echo "</div>";

            echo "<h3 class='longheader'>Forum Post History</h3>";

            // Output all forums fetched, by category

            $lastCategory = "_init";

            $forumIter = 0;

            echo "<div class='table-wrapper'>";
            echo "<table class='table-forum-history'>";
            echo "<tbody>";

            echo "<tr>";
            echo "<th></th>";
            echo "<th>Author</th>";
            echo "<th class='fullwidth'>Message</th>";
            echo "<th class='text-nowrap'>Posted At</th>";
            echo "</tr>";

            foreach ($recentPostsData as $topicPostData) {
                $postMessage = $topicPostData['ShortMsg'];
                $postAuthor = $topicPostData['Author'];
                $forumTopicID = $topicPostData['ForumTopicID'];
                $forumTopicTitle = $topicPostData['ForumTopicTitle'];
                $forumCommentID = $topicPostData['CommentID'];
                $postTime = $topicPostData['PostedAt'];
                $nicePostTime = getNiceDate(strtotime($postTime));

                sanitize_outputs($forumTopicTitle, $postMessage);

                echo "<tr>";

                echo "<td>";
                echo GetUserAndTooltipDiv($postAuthor, true);
                echo "</td>";
                echo "<td>";
                echo GetUserAndTooltipDiv($postAuthor, false);
                echo "</td>";

                echo "<td><a href='/viewtopic.php?t=$forumTopicID&c=$forumCommentID'>$forumTopicTitle</a><br>$postMessage...</td>";
                echo "<td class='smalldate'>$nicePostTime</td>";
                echo "</tr>";
            }

            echo "</tbody></table></div>";

            echo "<div class='rightalign row'>";
            if ($offset > 0) {
                $prevOffset = $offset - $maxCount;
                echo "<a href='/forumposthistory.php?o=$prevOffset'>&lt; Previous $maxCount</a> - ";
            }
            if ($numPostsFound == $maxCount) {
                // Max number fetched, i.e. there are more. Can goto next 25.
                $nextOffset = $offset + $maxCount;
                echo "<a href='/forumposthistory.php?o=$nextOffset'>Next $maxCount &gt;</a>";
            }
            echo "</div>";
            ?>
            <br>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
