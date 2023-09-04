<?php

use App\Site\Enums\UserPreference;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;

$offset = requestInputSanitized('o', 0, 'integer');
$count = $maxCount = 25;

authenticateFromCookie($user, $permissions, $userDetails);

$websitePrefs = $userDetails['websitePrefs'] ?? 0;

$forUser = requestInputSanitized('u');
if (empty($forUser)) {
    $recentPosts = getRecentForumTopics($offset, $count, $permissions);
} else {
    $recentPosts = getRecentForumPosts($offset, $count, 90, $permissions, $forUser);
}

RenderContentStart("Forum Recent Posts");
?>
<article>
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
    echo "<table class='table-highlight'>";
    echo "<tbody>";

    echo "<tr class='do-not-highlight'>";
    if (empty($forUser)) {
        echo "<th>Last Post By</th>";
        echo "<th>Message</th>";
        echo "<th>Additional Posts</th>";
    } else {
        echo "<th>Author</th>";
        echo "<th>Message</th>";
    }
    echo "</tr>";

    $isShowAbsoluteDatesPreferenceSet = $websitePrefs && BitSet($websitePrefs, UserPreference::Forum_ShowAbsoluteDates);

    foreach ($recentPosts as $topicPostData) {
        $postMessage = $topicPostData['ShortMsg'];
        if ($topicPostData['IsTruncated']) {
            $postMessage .= '...';
        }
        $postAuthor = $topicPostData['Author'];
        $forumID = $topicPostData['ForumID'] ?? 0;
        $forumTitle = $topicPostData['ForumTitle'] ?? '';
        $forumTopicID = $topicPostData['ForumTopicID'];
        $forumTopicTitle = $topicPostData['ForumTopicTitle'];
        $commentID = $topicPostData['CommentID'];
        $commentID_1d = $topicPostData['CommentID_1d'] ?? 0;
        $count_1d = $topicPostData['Count_1d'] ?? 0;
        $commentID_7d = $topicPostData['CommentID_7d'] ?? 0;
        $count_7d = $topicPostData['Count_7d'] ?? 0;

        $postedAt =
            $isShowAbsoluteDatesPreferenceSet
                ? getNiceDate(strtotime($topicPostData['PostedAt']))
                : Carbon::parse($topicPostData['PostedAt'])->diffForHumans();

        sanitize_outputs($forumTopicTitle, $forumTitle, $postMessage);

        echo "<tr>";

        echo "<td class='py-2.5'>";
        echo userAvatar($postAuthor, iconSize: 24);
        echo "</td>";

        $tooltipClassName = $isShowAbsoluteDatesPreferenceSet ? "" : "cursor-help";
        $titleAttribute = $isShowAbsoluteDatesPreferenceSet
            ? ""
            : "title='" . Carbon::parse($topicPostData['PostedAt'])->format('F j Y, g:ia') . "'";

        echo "<td>";
        echo "<a href='/viewtopic.php?t=$forumTopicID&c=$commentID#$commentID'>$forumTopicTitle</a>";
        echo " <span class='smalldate $tooltipClassName' $titleAttribute>$postedAt</span>";
        echo "<div class='comment text-overflow-wrap'>";
        echo Shortcode::stripAndClamp($postMessage);
        echo "</div>";
        echo "</td>";

        echo "<td>";
        if (empty($forUser)) {
            if ($count_7d > 1) {
                echo "<span class='smalltext whitespace-nowrap'>";

                if ($count_1d > 1) {
                    echo "<a href='/viewtopic.php?t=$forumTopicID&c=$commentID_1d#$commentID_1d'>$count_1d posts in the last 24 hours";
                }

                if ($count_7d > $count_1d) {
                    if ($count_1d > 1) {
                        echo "<div class='mt-1'/>";
                    }
                    echo "<a href='/viewtopic.php?t=$forumTopicID&c=$commentID_7d#$commentID_7d'>$count_7d posts in the last 7 days";
                    if ($count_1d > 1) {
                        echo "</div>";
                    }
                }

                echo "</span>";
            }
        }
        echo "</td>";

        echo "</tr>";
    }

    echo "</tbody></table></div>";

    echo "<div class='text-right'>";
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
    if (count($recentPosts) === $maxCount) {
        // Max number fetched, i.e. there are more. Can goto next 25.
        $nextOffset = $offset + $maxCount;
        echo "<a href='$baseUrl$nextOffset'>Next $maxCount &gt;</a>";
    }
    echo "</div>";
    ?>
</article>
<?php RenderContentEnd(); ?>
