<?php

function RenderRecentForumPostsComponent($numToFetch = 4)
{
    echo "<div class='component'>";
    echo "<h3>Forum Activity</h3>";

    if (getRecentForumPosts(0, $numToFetch, 100, $recentPostData) != 0) {
        foreach ($recentPostData as $nextData) {
            $timestamp = strtotime($nextData['PostedAt']);
            $datePosted = date("d M", $timestamp);

            if (date("d", $timestamp) == date("d")) {
                $datePosted = "Today";
            } elseif (date("d", $timestamp) == (date("d") - 1)) {
                $datePosted = "Y'day";
            }

            $postedAt = date("H:i", $timestamp);


            $shortMsg = $nextData['ShortMsg'] . "...";
            $author = $nextData['Author'];
            $commentID = $nextData['CommentID'];
            $forumTopicID = $nextData['ForumTopicID'];
            $forumTopicTitle = $nextData['ForumTopicTitle'];

            echo "<div class='embedded mb-1'>";
            echo "<div style='line-height: 1em;'>";
            echo GetUserAndTooltipDiv($author, true, null, 16);
            echo " ";
            echo GetUserAndTooltipDiv($author, false);
            echo "<small>";
            echo " on <span class='smalldate' style='width: auto'>$datePosted $postedAt</span> in ";
            echo "</small>";
            echo "<a href='/viewtopic.php?t=$forumTopicID&amp;c=$commentID#$commentID'>$forumTopicTitle</a><br>";
            echo "</div>";
            echo "<div class=''>$shortMsg</div>";
            echo "<div class='text-right'><a href='/viewtopic.php?t=$forumTopicID&amp;c=$commentID#$commentID'>[view]</a></div>";
            echo "</div>";
        }
    }

    echo "<span class='morebutton'><a href='/forumposthistory.php'>more...</a></span>";

    echo "</div>";
}
