<?php

return redirect(route('home'));

$offset = requestInputSanitized('o', null, 'integer');
$global = requestInputSanitized('g', null, 'integer');
$activityID = requestInputSanitized('a', null, 'integer');
$individual = requestInputSanitized('i', null, 'integer');

authenticateFromCookie($user, $permissions, $userDetails);

// Max: last 50 messages:
$maxMessages = 50;
$numFeedItems = 0;

$feedData = [];
$numFeedItems = 0;
if ($activityID) {
    $numFeedItems = getFeed($user, $maxMessages, $offset, $feedData, $activityID, 'activity');
} elseif (isset($global)) {
    $numFeedItems = getFeed($user, $maxMessages, $offset, $feedData, 0, 'global');
    $global = true;
} elseif (isset($user) && !isset($individual)) {
    $numFeedItems = getFeed($user, $maxMessages, $offset, $feedData, 0, 'friends');
} elseif (isset($individual)) {
    $numFeedItems = getFeed($user, $maxMessages, $offset, $feedData, 0, 'individual');
}

// This page is unusual, in that the later items should appear at the top
// $feedData = array_reverse($feedData);

if (isset($activityID)) {
    $pageTitle = "Activity";
} elseif ($global) {
    $pageTitle = "Global Activity Feed";
} elseif (isset($user)) {
    $pageTitle = $user . "'s Activity Feed";
} else {
    $pageTitle = "Activity Feed";
}

RenderContentStart($pageTitle);
?>
<link rel='alternate' type='application/rss+xml' title='Global Feed' href='<?= config('app.url') ?>/rss-activity'/>
<script>
  $(document).ready(function () {
    focusOnArticleID(getParameterByName('a'));
  });
</script>
<div id="mainpage">
    <div id="leftcontainer">

        <div id="globalfeed">
            <h2><?= $pageTitle ?></h2>
            <?php
            echo "<table width='550' id='feed' style='width:100%' ><tbody>";

            $lastID = 0;
            $lastKnownDate = 'Init';

            $userCache = [];
            for ($i = 0; $i < $numFeedItems; $i++) {
                $nextTime = $feedData[$i]['timestamp'];

                $dow = date("d/m", $nextTime);
                if ($lastKnownDate == 'Init') {
                    $lastKnownDate = $dow;
                    echo "<tr><td class='date' style='white-space: nowrap'>$dow:</td></tr>";
                } elseif ($lastKnownDate !== $dow) {
                    $lastKnownDate = $dow;
                    echo "<tr><td class='date'><br>$dow:</td></tr>";
                }

                if ($lastID != $feedData[$i]['ID']) {
                    $lastID = $feedData[$i]['ID'];
                    RenderFeedItem($feedData[$i], $user, $userCache);
                }

                if ($feedData[$i]['Comment'] !== null) {
                    while (($i < $numFeedItems) && $lastID == $feedData[$i]['ID']) {
                        RenderArticleComment(
                            $feedData[$i]['ID'],
                            $feedData[$i]['CommentUser'],
                            $feedData[$i]['Comment'],
                            $feedData[$i]['CommentPostedAt'],
                            $user,
                            0,
                            $feedData[$i]['CommentID'],
                            false
                        );
                        $i++;
                    }
                    $i--;    // Note: we will have incorrectly incremented this if we read comments - the first comment has the same ID!
                }
            }
            echo "</tbody></table>";

            echo "<div class='float-right row'>";

            if ($offset > 0) {
                echo "<a href='/feed.php?";
                if ($global) {
                    echo "g=1&amp;";
                }
                echo "o=" . ($offset - 50);
                echo "'>&lt; Previous 50</a> - ";
            }

            if ($activityID !== null) {
                echo "<a href='/feed.php?g=1'>Global Feed &gt;</a> ";
            } elseif ($numFeedItems > 0) {
                echo "<a href='/feed.php?";
                if ($global) {
                    echo "g=1&amp;";
                }
                echo "o=" . ($offset + 50);
                echo "'>Next 50 &gt;</a> ";
            }

            echo "</div>";

            ?>
        </div>

    </div>

    <div id="rightcontainer">
        <?php
        $yOffs = 0;
        RenderTwitchTVStream();
        ?>

        <div id="achievement" class="rightFeed">
        </div>

    </div>

</div>

<?php RenderContentEnd(); ?>
