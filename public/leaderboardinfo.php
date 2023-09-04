<?php

use App\Community\Enums\ArticleType;
use App\Site\Enums\Permissions;
use Illuminate\Support\Facades\Blade;

authenticateFromCookie($user, $permissions, $userDetails);

$lbID = requestInputSanitized('i', null, 'integer');
if (empty($lbID)) {
    $lbID = (int) request('leaderboard');
    if (empty($lbID)) {
        abort(404);
    }
}

$offset = requestInputSanitized('o', 0, 'integer');
$count = requestInputSanitized('c', 50, 'integer');
$friendsOnly = requestInputSanitized('f', 0, 'integer');

$lbData = GetLeaderboardData($lbID, $user, $count, $offset);

if (empty($lbData['LBID'] ?? null)) {
    abort(404);
}

$numEntries = is_countable($lbData['Entries']) ? count($lbData['Entries']) : 0;

$lbTitle = $lbData['LBTitle'];
$lbDescription = $lbData['LBDesc'];
$lbFormat = $lbData['LBFormat'];
$lbAuthor = $lbData['LBAuthor'];
$lbCreated = $lbData['LBCreated'];
$lbUpdated = $lbData['LBUpdated'];

$gameID = $lbData['GameID'];
$gameTitle = $lbData['GameTitle'];
$gameIcon = $lbData['GameIcon'];

$sortDesc = $lbData['LowerIsBetter'];
$lbMemory = $lbData['LBMem'];

$consoleID = $lbData['ConsoleID'];
$consoleName = $lbData['ConsoleName'];
$forumTopicID = $lbData['ForumTopicID'];

$pageTitle = "Leaderboard: $lbTitle ($gameTitle)";

$numLeaderboards = getLeaderboardsForGame($gameID, $allGameLBData, $user);
$numArticleComments = getRecentArticleComments(ArticleType::Leaderboard, $lbID, $commentData);

function ExplainLeaderboardTrigger(string $name, string $triggerDef, array $codeNotes): void
{
    echo "<div class='devbox'>";
    echo "<span onclick=\"$('#devbox{$name}content').toggle(); return false;\">$name ▼</span>";
    echo "<div id='devbox{$name}content' style='display: none'>";

    echo "<div>";

    echo "<li>Mem:</li>";
    echo "<code>" . htmlspecialchars($triggerDef) . "</code>";

    if ($name === 'Value') {
        $triggerDef = ValueToTrigger($triggerDef);
    }

    echo "<li>Mem explained:</li>";
    echo "<code>" . getAchievementPatchReadableHTML($triggerDef, $codeNotes) . "</code>";
    echo "</div>";

    echo "</div>"; // devboxcontent
    echo "</div>"; // devbox
}

RenderOpenGraphMetadata(
    $pageTitle,
    "Leaderboard",
    media_asset($gameIcon),
    "Leaderboard: $lbTitle ($gameTitle, $consoleName): "
);
RenderContentStart('Leaderboard');
?>
<article>
    <div id="lbinfo">
        <?php
        echo "<div class='navpath'>";
        echo renderGameBreadcrumb($lbData);
        echo " &raquo; <b>$lbTitle</b>";
        echo "</div>";

        $systemIconUrl = getSystemIconUrl($consoleID);
        echo Blade::render('
            <x-game.heading
                :consoleName="$consoleName"
                :gameTitle="$gameTitle"
                :iconUrl="$iconUrl"
            />
        ', [
            'consoleName' => $consoleName,
            'gameTitle' => $gameTitle,
            'iconUrl' => $systemIconUrl,
        ]);

        echo "<table class='nicebox'><tbody>";

        echo "<tr>";
        echo "<td style='width:70px' class='p-0'>";
        echo gameAvatar($lbData, label: false, iconSize: 96);
        echo "</td>";

        echo "<td class='px-3'>";
        echo "<div class='flex justify-between'>";
        echo "<div>";
        echo "<a href='/leaderboard/$lbID'><strong>$lbTitle</strong></a><br>";
        echo "$lbDescription";
        echo "</div>";
        echo "</div>";
        echo "</td>";

        echo "</tr>";
        echo "</tbody></table>";

        $niceDateCreated = date("d M, Y H:i", strtotime($lbCreated));
        $niceDateModified = date("d M, Y H:i", strtotime($lbUpdated));

        echo "<p class='embedded smalldata my-2'>";
        echo "<small>";
        if (is_null($lbAuthor)) {
            echo "Created by Unknown on: $niceDateCreated<br>Last modified: $niceDateModified<br>";
        } else {
            echo "Created by " . userAvatar($lbAuthor, icon: false) . " on: $niceDateCreated<br>Last modified: $niceDateModified<br>";
        }
        echo "</small>";
        echo "</p>";

        if (isset($user) && $permissions >= Permissions::JuniorDeveloper) {
            echo "<div class='devbox'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Dev ▼</span>";
            echo "<div id='devboxcontent' style='display: none'>";

            echo "<ul>";
            echo "<a href='/leaderboardList.php?g=$gameID'>Leaderboard Management for $gameTitle</a>";

            echo "<li>Manage Entries</li>";
            echo "<div>";
            if (!empty($lbData['Entries'])) {
                echo "<tr><td>";
                echo "<form method='post' action='/request/leaderboard/remove-entry.php' onsubmit='return confirm(\"Are you sure you want to permanently delete this leaderboard entry?\")'>";
                echo csrf_field();
                echo "<input type='hidden' name='leaderboard' value='$lbID' />";
                echo "Remove Entry:";
                echo "<select name='user'>";
                echo "<option selected>-</option>";
                foreach ($lbData['Entries'] as $nextLBEntry) {
                    // Display all entries for devs, display only own entry for jr. devs
                    if (($user == $nextLBEntry['User'] && $permissions == Permissions::JuniorDeveloper) || $permissions >= Permissions::Developer) {
                        $nextUser = $nextLBEntry['User'];
                        $nextScore = $nextLBEntry['Score'];
                        $nextScoreFormatted = GetFormattedLeaderboardEntry($lbFormat, $nextScore);
                        echo "<option value='$nextUser'>$nextUser ($nextScoreFormatted)</option>";
                    }
                }
                echo "</select>";
                echo "</br>";
                echo "Reason:";
                echo "<input type='text' name='reason' maxlength='200' style='width: 50%;' placeholder='Please enter reason for removal'>";
                echo "<button class='btn btn-danger'>Remove entry</button>";
                echo "</form>";
                echo "</td></tr>";
            }
            echo "</div>";

            echo "<br/>";

            $memStart = "";
            $memCancel = "";
            $memSubmit = "";
            $memValue = "";
            $memChunks = explode("::", $lbMemory);
            foreach ($memChunks as &$memChunk) {
                $part = substr($memChunk, 0, 4);
                if ($part == 'STA:') {
                    $memStart = substr($memChunk, 4);
                } elseif ($part == 'CAN:') {
                    $memCancel = substr($memChunk, 4);
                } elseif ($part == 'SUB:') {
                    $memSubmit = substr($memChunk, 4);
                } elseif ($part == 'VAL:') {
                    $memValue = substr($memChunk, 4);
                }
            }

            getCodeNotes($gameID, $codeNotes);
            ExplainLeaderboardTrigger('Start', $memStart, $codeNotes);
            ExplainLeaderboardTrigger('Cancel', $memCancel, $codeNotes);
            ExplainLeaderboardTrigger('Submit', $memSubmit, $codeNotes);
            ExplainLeaderboardTrigger('Value', $memValue, $codeNotes);

            echo "</div>";
            echo "</div>";
        }

        // Not implemented
        // if( $friendsOnly )
        //    echo "<b>Friends Only</b> - <a href='leaderboardinfo.php?i=$lbID&amp;c=$count&amp;f=0'>Show All Results</a><br><br>";
        // else
        //    echo "<a href='leaderboardinfo.php?i=$lbID&amp;c=$count&amp;f=1'>Show Friends Only</a> - <b>All Results</b><br><br>";

        echo "<div class='larger'>$lbTitle: $lbDescription</div>";

        echo "<table class='table-highlight'><tbody>";
        echo "<tr class='do-not-highlight'><th>Rank</th><th>User</th><th class='text-right'>Result</th><th class='text-right'>Date Submitted</th></tr>";

        $numActualEntries = 0;
        $localUserFound = false;
        $resultsDrawn = 0;
        $nextRank = 1;

        // for( $i = 0; $i < $numEntries; $i++ )
        foreach ($lbData['Entries'] as $nextEntry) {
            // $nextEntry = $lbData[$i];

            $nextUser = $nextEntry['User'];
            $nextScore = $nextEntry['Score'];
            $nextRank = $nextEntry['Rank'];
            $nextScoreFormatted = GetFormattedLeaderboardEntry($lbFormat, $nextScore);
            $nextSubmitAt = $nextEntry['DateSubmitted'];
            $nextSubmitAtNice = getNiceDate($nextSubmitAt);

            $isLocal = (strcmp($nextUser, $user) == 0);
            $lastEntry = ($resultsDrawn + 1 == $numEntries);
            $userAppendedInResults = ($numEntries > $count);

            // echo "$isLocal, $lastEntry, $userAppendedInResults ($numEntries, $count)<br>";

            if ($lastEntry && $isLocal && $userAppendedInResults) {
                // This is the local, outside-rank user at the end of the table
                echo "<tr class='last'><td colspan='4' class='small'>&nbsp;</td></tr>"; // Dirty!
            } else {
                $numActualEntries++;
            }

            if ($isLocal) {
                $localUserFound = true;
                echo "<tr style='outline: thin solid'>";
            } else {
                echo "<tr>";
            }

            $injectFmt1 = $isLocal ? "<b>" : "";
            $injectFmt2 = $isLocal ? "</b>" : "";

            echo "<td class='lb_rank'>$injectFmt1$nextRank$injectFmt2</td>";

            echo "<td class='lb_user'>";
            echo userAvatar($nextUser);
            echo "</td>";

            echo "<td class='lb_result text-right'>$injectFmt1$nextScoreFormatted$injectFmt2</td>";

            echo "<td class='lb_date text-right'>$injectFmt1$nextSubmitAtNice$injectFmt2</td>";

            echo "</tr>";

            $resultsDrawn++;
        }

        echo "</tbody></table><br>";

        if (!$localUserFound && isset($user)) {
            echo "<div>You don't appear to be ranked for this leaderboard. Why not give it a go?</div><br>";
        }

        echo "<div class='text-right'>";
        if ($offset > 0) {
            $prevOffset = $offset - $count;
            echo "<a class='btn btn-link' href='/leaderboardinfo.php?i=$lbID&amp;o=$prevOffset&amp;c=$count&amp;f=$friendsOnly'>&lt; Previous $count</a> - ";
        }

        // echo "$numActualEntries";

        if ($numActualEntries == $count) {
            // Max number fetched, i.e. there are more. Can goto next 20.
            $nextOffset = $offset + $count;
            echo "<a class='btn btn-link' href='/leaderboardinfo.php?i=$lbID&amp;o=$nextOffset&amp;c=$count&amp;f=$friendsOnly'>Next $count &gt;</a>";
        }
        echo "</div>";

        // Render article comments
        RenderCommentsComponent(
            $user,
            $numArticleComments,
            $commentData,
            $lbID,
            ArticleType::Leaderboard,
            $permissions
        );

        RenderLinkToGameForum($gameTitle, $gameID, $forumTopicID, $permissions);
        echo "<br><br>";
        ?>
    </div>
</article>
<?php view()->share('sidebar', true) ?>
<aside>
    <?php
    RenderGameLeaderboardsComponent($allGameLBData, null);
    ?>
</aside>
<?php RenderContentEnd(); ?>
