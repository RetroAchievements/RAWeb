<?php

use Illuminate\Support\Carbon;

function RenderRecentForumPostsComponent($permissions, $numToFetch = 4): void
{
    echo "<div class='component'>";
    echo "<h3>Forum Activity</h3>";

    if (getRecentForumPosts(0, $numToFetch, 100, $permissions, $recentPostData) != 0) {
        foreach ($recentPostData as $nextData) {
            $timestamp = strtotime($nextData['PostedAt']);
            $postedAt = Carbon::createFromTimeStamp($timestamp)->diffForHumans();

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

            echo "<div class='embedded mb-1'>";
            echo "<div class='flex justify-between items-center'><div>";
            echo userAvatar($author, iconSize: 16);
            echo " <span class='smalldate'>$postedAt</span></div>";
            echo "<div><a class='btn btn-link' href='/viewtopic.php?t=$forumTopicID&amp;c=$commentID#$commentID'>View</a></div>";
            echo "</div>";
            echo "in <a href='/viewtopic.php?t=$forumTopicID&amp;c=$commentID#$commentID'>$forumTopicTitle</a><br>";
            echo "<div class='comment text-overflow-wrap'>$shortMsg</div>";
            echo "</div>";
        }
    }

    echo "<div class='text-right'><a class='btn btn-link' href='/forumposthistory.php'>more...</a></div>";

    echo "</div>";
}
