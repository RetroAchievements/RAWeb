<?php

function RenderRecentForumPostsComponent($permissions, $numToFetch = 4): void
{
    echo "<div class='component'>";
    echo "<h3>Forum Activity</h3>";

    if (getRecentForumPosts(0, $numToFetch, 100, $permissions, $recentPostData) != 0) {
        foreach ($recentPostData as $nextData) {
            $timestamp = strtotime($nextData['PostedAt']);
            $datePosted = date("d M", $timestamp);

            if (date("d", $timestamp) == date("d")) {
                $datePosted = "Today";
            } elseif (date("d", $timestamp) == (date("d") - 1)) {
                $datePosted = "Y'day";
            }

            $postedAt = date("H:i", $timestamp);

            $shortMsg = trim($nextData['ShortMsg']);
            if ($nextData['IsTruncated']) {
                $shortMsg .= "...";
            }
            $author = $nextData['Author'];
            $commentID = $nextData['CommentID'];
            $forumTopicID = $nextData['ForumTopicID'];
            $forumTopicTitle = $nextData['ForumTopicTitle'];

            sanitize_outputs(
                $shortMsg,
                $author,
                $forumTopicTitle,
            );

            echo "<div class='embedded mb-1 flex justify-between items-center'>";
            echo "<div>";
            echo GetUserAndTooltipDiv($author, true, iconSizeDisplayable: 16);
            echo " ";
            echo GetUserAndTooltipDiv($author);
            echo " <span class='smalldate'>$datePosted $postedAt</span><br>";
            echo "in <a href='/viewtopic.php?t=$forumTopicID&amp;c=$commentID#$commentID'>$forumTopicTitle</a><br>";
            echo "<div>$shortMsg</div>";
            echo "</div>";
            echo "<a class='btn btn-link' href='/viewtopic.php?t=$forumTopicID&amp;c=$commentID#$commentID'>View</a>";
            echo "</div>";
        }
    }

    echo "<div class='text-right'><a class='btn btn-link' href='/forumposthistory.php'>more...</a></div>";

    echo "</div>";
}
