<?php
function RenderRecentForumPostsComponent($numToFetch = 4)
{
    echo "<div class='component' >";
    echo "<h3>Forum Activity</h3>";

    if (getRecentForumPosts(0, $numToFetch, 45, $recentPostData) != 0) {
        echo "<table class='recentforumposts'><tbody>";
        echo "<tr><th>At</th><th>User</th><th>Message</th><th>Topic</th></tr>";

        $lastDate = '';

        foreach ($recentPostData as $nextData) {
            $timestamp = strtotime($nextData['PostedAt']);
            $datePosted = date("d M", $timestamp);

            if (date("d", $timestamp) == date("d")) {
                $datePosted = "Today";
            } elseif (date("d", $timestamp) == (date("d") - 1)) {
                $datePosted = "Y'day";
            }

            $postedAt = date("H:i", $timestamp);

            if ($lastDate !== $datePosted) {
                $lastDate = $datePosted;
            }

            echo "<tr>";

            $shortMsg = $nextData['ShortMsg'] . "...";
            $author = $nextData['Author'];
            $commentID = $nextData['CommentID'];
            $forumTopicID = $nextData['ForumTopicID'];
            $forumTopicTitle = $nextData['ForumTopicTitle'];

            echo "<td>$datePosted $postedAt</td>";

            echo "<td>";
            echo GetUserAndTooltipDiv($author, true);
            echo "</td>";

            echo "<td class='recentforummsg'>$shortMsg<a href='/viewtopic.php?t=$forumTopicID&amp;c=$commentID'>[view]</a></td>";

            echo "<td><div class='fixheightcelllarger recentforumname'>";
            echo "<a href='/viewtopic.php?t=$forumTopicID&amp;c='>$forumTopicTitle</a>";
            echo "</div></td>";

            echo "</tr>";
        }

        echo "</tbody></table>";
    } else {
        error_log(__FUNCTION__);
        error_log("Cannot get latest forum posts!");
    }

    echo "<span class='morebutton'><a href='/forumposthistory.php'>more...</a></span>";

    echo "</div>";
}
