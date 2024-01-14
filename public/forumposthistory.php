<?php

use App\Site\Enums\UserPreference;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;

$offset = requestInputSanitized('o', 0, 'integer');
$count = $maxCount = 25;

authenticateFromCookie($user, $permissions, $userDetails);

$websitePrefs = $userDetails['websitePrefs'] ?? 0;

$messageLength = 120;

$forUser = requestInputSanitized('u');
if (empty($forUser)) {
    $recentPosts = getRecentForumTopics($offset, $count, $permissions, $messageLength);
} else {
    $messageLength = 150;
    $recentPosts = getRecentForumPosts($offset, $count, $messageLength, $permissions, $forUser);
}

RenderContentStart("Forum Post History");
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

    $isShowAbsoluteDatesPreferenceSet = $websitePrefs && BitSet($websitePrefs, UserPreference::Forum_ShowAbsoluteDates);

    echo "<div class='flex flex-col gap-y-1 mb-4'>";
    foreach ($recentPosts as $topicPostData) {
        $forumTopicId = $topicPostData['ForumTopicID'];
        $commentId = $topicPostData['CommentID'];
        $postHref = "/viewtopic.php?t=$forumTopicId&c=$commentId#$commentId";

        $postMessage = $topicPostData['ShortMsg'];
        if ($topicPostData['IsTruncated']) {
            $postMessage .= '...';
        }

        $postedAt =
            $isShowAbsoluteDatesPreferenceSet
                ? getNiceDate(strtotime($topicPostData['PostedAt']))
                : Carbon::parse($topicPostData['PostedAt'])->diffForHumans();

        $commentId_1d = $topicPostData['CommentID_1d'] ?? 0;
        $count_1d = $topicPostData['Count_1d'] ?? 0;
        $commentId_7d = $topicPostData['CommentID_7d'] ?? 0;
        $count_7d = $topicPostData['Count_7d'] ?? 0;

        $viewLabel = '';
        $view2Label = null;
        $viewHref = null;
        $view2Href = null;
        if (empty($forUser)) {
            if ($count_7d > 1) {
                if ($count_1d > 1) {
                    $viewLabel = "{$count_1d} posts in the last 24 hours";
                    $viewHref = "/viewtopic.php?t=$forumTopicId&c=$commentId_1d#$commentId_1d";
                }

                if ($count_7d > $count_1d) {
                    $view2Label = "{$count_7d} posts in the last 7 days";
                    $view2Href = "/viewtopic.php?t=$forumTopicId&c=$commentId_7d#$commentId_7d";
                }
            }
        }

        echo Blade::render('
            <x-forum.recent-post-item
                :authorUsername="$authorUsername"
                :forumTopicTitle="$forumTopicTitle"
                :hasDateTooltip="$hasDateTooltip"
                :href="$href"
                :postedAt="$postedAt"
                :summary="$summary"
                :tooltipLabel="$tooltipLabel"
                :viewLabel="$viewLabel"
                :viewHref="$viewHref"
                :view2Label="$view2Label"
                :view2Href="$view2Href"
            />
        ', [
            'authorUsername' => $topicPostData['Author'],
            'forumTopicTitle' => $topicPostData['ForumTopicTitle'],
            'hasDateTooltip' => $isShowAbsoluteDatesPreferenceSet,
            'href' => $postHref,
            'postedAt' => $postedAt,
            'summary' => Shortcode::stripAndClamp($postMessage, $messageLength),
            'tooltipLabel' => Carbon::parse($topicPostData['PostedAt'])->format('F j Y, g:ia'),
            'viewLabel' => $viewLabel,
            'viewHref' => $viewHref,
            'view2Label' => $view2Label,
            'view2Href' => $view2Href,
        ]);
    }
    echo "</div>";

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
