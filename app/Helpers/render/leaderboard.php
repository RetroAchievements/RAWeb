<?php

use App\Models\ForumTopic;
use App\Platform\Enums\ValueFormat;

// TODO migrate to a Blade component
function RenderGameLeaderboardsComponent(array $lbData, ?int $forumTopicID): void
{
    $numLBs = count($lbData);
    echo "<div class='component'>";
    echo "<h2 class='text-h3'>Leaderboards</h2>";

    if ($numLBs == 0) {
        if (!empty($forumTopicID) && ForumTopic::where('ID', $forumTopicID)->exists()) {
            echo "No leaderboards found: why not <a href='/viewtopic.php?t=$forumTopicID'>suggest some</a> for this game? ";
        } else {
            echo "No leaderboards found: why not suggest some for this game? ";
        }
    } else {
        $count = 0;
        foreach ($lbData as $lbItem) {
            if ($lbItem['DisplayOrder'] < 0) {
                continue;
            }

            $lbID = $lbItem['LeaderboardID'];
            $lbTitle = $lbItem['Title'];
            $lbDesc = $lbItem['Description'];
            $bestScoreUser = $lbItem['User'];
            $bestScore = $lbItem['Score'];
            $scoreFormat = $lbItem['Format'];

            sanitize_outputs($lbTitle, $lbDesc);

            echo "<div class='odd:bg-embed hover:bg-embed-highlight border border-transparent hover:border-[rgba(128,128,128,.3)] flex flex-col gap-y-1 p-2'>";
            echo "<div>";
            echo "<a href='/leaderboardinfo.php?i=$lbID' class='leading-3'>$lbTitle</a>";
            echo "<p>$lbDesc</p>";
            echo "</div>";
            echo "<div class='flex justify-between'>";
            echo userAvatar($bestScoreUser, iconSize: 16);
            echo "<a href='/leaderboardinfo.php?i=$lbID'>";
            if ($bestScoreUser == '') {
                echo "No entries";
            } else {
                echo ValueFormat::format($bestScore, $scoreFormat);
            }
            echo "</a>";
            echo "</div>";
            echo "</div>";

            $count++;
        }

    }

    // echo "<div class='text-right'><a href='/forumposthistory.php'>more...</a></div>";

    echo "</div>";
}
