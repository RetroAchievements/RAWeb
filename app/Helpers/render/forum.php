<?php

use App\Site\Enums\Permissions;
use App\Site\Enums\UserPreference;
use App\Site\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;

function RenderRecentForumPostsComponent(int $numToFetch = 4): void
{
    /** @var ?User $user */
    $user = auth()->user();
    $permissions = $user?->Permissions ?? Permissions::Unregistered;
    $preferences = $user?->websitePrefs ?? 0;

    echo "<div class='component'>";
    echo "<h3>Forum Activity</h3>";

    $recentPostData = getRecentForumPosts(0, $numToFetch, 100, $permissions);

    if ($recentPostData->isNotEmpty()) {
        foreach ($recentPostData as $nextData) {
            $postedAt =
                $preferences && BitSet($preferences, UserPreference::Forum_ShowAbsoluteDates)
                    ? getNiceDate(strtotime($nextData['PostedAt']))
                    : Carbon::parse($nextData['PostedAt'])->diffForHumans();

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
            echo " <span class='smalldate cursor-help' title='" . Carbon::parse($nextData['PostedAt'])->format('F j Y, g:ia') . "'>$postedAt</span></div>";
            echo "<div><a class='btn btn-link' href='/viewtopic.php?t=$forumTopicID&amp;c=$commentID#$commentID'>View</a></div>";
            echo "</div>";
            echo "in <a href='/viewtopic.php?t=$forumTopicID&amp;c=$commentID#$commentID'>$forumTopicTitle</a><br>";
            echo "<div class='comment text-overflow-wrap'>";
            echo Shortcode::stripAndClamp($shortMsg);
            echo "</div>";
            echo "</div>";
        }
    }

    echo "<div class='text-right'><a class='btn btn-link' href='/forumposthistory.php'>more...</a></div>";

    echo "</div>";
}
