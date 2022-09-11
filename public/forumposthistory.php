<?php

$maxCount = 25;

$offset = requestInputSanitized('o', 0, 'integer');
$count = $maxCount;

authenticateFromCookie($user, $permissions, $userDetails);

$forUser = requestInputSanitized('u');
$numPostsFound = getRecentForumPosts($offset, $count, 90, $permissions, $recentPostsData, $forUser);

RenderContentStart("Forum Recent Posts");
?>
<div id="mainpage">
    <div id='fullcontainer'>
        <?php
        echo "<div class='navpath'>";
        echo "<a href='/forum.php'>Forum Index</a>";
        if ($forUser != null) {
            echo " &raquo; <a href='/forumposthistory.php'>Forum Post History</a>";
            echo " &raquo; <b>$forUser</b>";
        } else {
            echo " &raquo; <b>Forum Post History</b>";
        }
        echo "</div>";

        echo "<h3>Forum Post History</h3>";

        // Output all forums fetched, by category

        $lastCategory = "_init";

        $forumIter = 0;

        echo "<div class='table-wrapper'>";
        echo "<table>";
        echo "<tbody>";

        echo "<tr>";
        echo "<th></th>";
        echo "<th>Author</th>";
        echo "<th class='w-full'>Message</th>";
        echo "<th class='whitespace-nowrap'>Posted At</th>";
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

        echo "<div class='float-right row'>";
        $baseUrl = '/forumposthistory.php';
        if ($forUser != null) {
            $baseUrl .= "?u=$forUser&o=";
        } else {
            $baseUrl .= "?o=";
        }
        if ($offset > 0) {
            $prevOffset = $offset - $maxCount;
            echo "<a href='$baseUrl$prevOffset'>&lt; Previous $maxCount</a> - ";
        }
        if ($numPostsFound == $maxCount) {
            // Max number fetched, i.e. there are more. Can goto next 25.
            $nextOffset = $offset + $maxCount;
            echo "<a href='$baseUrl$nextOffset'>Next $maxCount &gt;</a>";
        }
        echo "</div>";
        ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
