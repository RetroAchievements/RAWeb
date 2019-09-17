<?php

use RA\Permissions;

require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$lbID = seekGET('i');
if (!isset($lbID)) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

$offset = seekGET('o', 0);
$count = seekGET('c', 50);
$friendsOnly = seekGET('f', 0);

settype($offset, 'integer');
settype($count, 'integer');
settype($friendsOnly, 'integer');

$lbData = GetLeaderboardData($lbID, $user, $count, $offset, $friendsOnly);
$numEntries = count($lbData['Entries']);

$lbTitle = $lbData['LBTitle'];
$lbDescription = $lbData['LBDesc'];
$lbFormat = $lbData['LBFormat'];

$gameID = $lbData['GameID'];
$gameTitle = $lbData['GameTitle'];
$gameIcon = $lbData['GameIcon'];

$sortDesc = $lbData['LowerIsBetter'];
$lbMemory = $lbData['LBMem'];

$consoleID = $lbData['ConsoleID'];
$consoleName = $lbData['ConsoleName'];
$forumTopicID = $lbData['ForumTopicID'];

$pageTitle = "Leaderboard: $lbTitle ($gameTitle)";
getCookie($user, $cookie);

$numLeaderboards = getLeaderboardsForGame($gameID, $allGameLBData, $user);
$numArticleComments = getArticleComments(6, $lbID, 0, 20, $commentData);

$errorCode = seekGET('e');

RenderDocType(true);
?>

<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader($user); ?>
    <?php RenderFBMetaData($pageTitle, "Leaderboard", "$gameIcon", "/leaderboardinfo.php?i=$lbID",
        "Leaderboard: $lbTitle ($gameTitle, $consoleName): "); ?>
    <?php RenderTitleTag($pageTitle, $user); ?>
    <?php RenderGoogleTracking(); ?>
</head>

<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>

<div id="mainpage">
    <div id='leftcontainer'>
        <?php RenderErrorCodeWarning('left', $errorCode); ?>

        <div id="lbinfo" class="left">
            <?php
            echo "<div class='navpath'>";
            echo "<a href='/gameList.php'>All Games</a>";
            echo " &raquo; <a href='/gameList.php?c=$consoleID'>$consoleName</a>";
            echo " &raquo; <a href='/Game/$gameID'>$gameTitle</a></b>";
            echo " &raquo; <b>Leaderboard</b>";
            echo "</div>";

            echo "<div style='float:left; padding: 4px;'>";
            echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, true, 96);

            echo "</div>";
            echo "<div>";
            echo "<h3 class='longheader'>$pageTitle</h3>";
            echo "</div>";

            echo "<br/>";
            echo "<br/>";
            echo "<br/>";

            if (isset($user) && $permissions >= 3) {
                echo "<div class='devbox'>";
                echo "<span onclick=\"$('#devboxcontent').toggle(500); return false;\">Dev (Click to show):</span><br/>";
                echo "<div id='devboxcontent'>";

                echo "<ul>";
                echo "<a href='/leaderboardList.php?g=$gameID'>Leaderboard Management for $gameTitle</a>";

                echo "<li>Manage Entries</li>";
                echo "<table><tbody>";
                if (count($lbData['Entries']) > 0) {
                    echo "<tr><td>";
                    echo "<form method='post' action='/request.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' name='r' value='removelbentry' />";
                    echo "<input type='hidden' name='l' value='$lbID' />";
                    echo "<input type='hidden' name='b' value='true' />";

                    echo "Entry to remove:";
                    echo "<select name='t'>";
                    echo "<option value='0' selected>-</option>";

                    //for( $i = 0; $i < $numEntries; $i++ )
                    foreach ($lbData['Entries'] as $nextLBEntry) {
                        //$nextLBEntry = $lbData['Entries'][$i];
                        //$nextRank = $nextLBEntry['Rank'];
                        $nextUser = $nextLBEntry['User'];
                        $nextScore = $nextLBEntry['Score'];

                        $nextScoreFormatted = GetFormattedLeaderboardEntry($lbFormat, $nextScore);

                        echo "<option value='$nextUser'>$nextUser ($nextScoreFormatted)</option>";
                    }
                    echo "</select>";
                    echo "<input type='submit' style='float: right;' value='Submit' size='37'/>";
                    echo "</form>";
                    echo "</td></tr>";
                }
                echo "</tbody></table>";


                echo "</div>";
                echo "</div>";
            }

            //    Not implemented
            //if( $friendsOnly )
            //    echo "<b>Friends Only</b> - <a href='leaderboardinfo.php?i=$lbID&amp;c=$count&amp;f=0'>Show All Results</a><br/><br/>";
            //else
            //    echo "<a href='leaderboardinfo.php?i=$lbID&amp;c=$count&amp;f=1'>Show Friends Only</a> - <b>All Results</b><br/><br/>";

            echo "<div class='larger'>$lbTitle: $lbDescription</div>";

            echo "<table class='smalltable'><tbody>";
            echo "<tr><th>Rank</th><th>User</th><th>Result</th><th>Date Won</th></tr>";

            $numActualEntries = 0;
            $localUserFound = false;
            $resultsDrawn = 0;

            $count = 0;
            //for( $i = 0; $i < $numEntries; $i++ )
            //var_dump( $lbData );
            foreach ($lbData['Entries'] as $nextEntry) {
                //$nextEntry = $lbData[$i];
                //var_dump( $nextEntry );

                $nextRank = $nextEntry['Rank'];
                $nextUser = $nextEntry['User'];
                $nextScore = $nextEntry['Score'];
                $nextScoreFormatted = GetFormattedLeaderboardEntry($lbFormat, $nextScore);
                $nextSubmitAt = $nextEntry['DateSubmitted'];
                $nextSubmitAtNice = getNiceDate($nextSubmitAt);

                $isLocal = (strcmp($nextUser, $user) == 0);
                $lastEntry = ($resultsDrawn + 1 == $numEntries);
                $userAppendedInResults = ($numEntries !== $count);

                //echo "$isLocal, $lastEntry, $userAppendedInResults ($numEntries, $count)</br>";

                if ($lastEntry && $isLocal && $userAppendedInResults) {
                    //    This is the local, outside-rank user at the end of the table
                    echo "<tr class='last'><td colspan='4' class='small'>&nbsp;</td></tr>"; //    Dirty!
                } else {
                    $numActualEntries++;
                }

                if ($isLocal) {
                    $localUserFound = true;
                }

                echo "<tr>";

                $injectFmt1 = $isLocal ? "<b>" : "";
                $injectFmt2 = $isLocal ? "</b>" : "";

                echo "<td class='lb_rank'>$injectFmt1$nextRank$injectFmt2</td>";

                echo "<td class='lb_user'>";
                echo GetUserAndTooltipDiv( $nextUser, TRUE );
                echo GetUserAndTooltipDiv( $nextUser, FALSE );
                echo "</td>";

                echo "<td class='lb_result'>$injectFmt1$nextScoreFormatted$injectFmt2</td>";

                echo "<td class='lb_date'>$injectFmt1$nextSubmitAtNice$injectFmt2</td>";

                echo "</tr>";

                $resultsDrawn++;
            }

            echo "</tbody></table><br/>";

            if (!$localUserFound && isset($user)) {
                echo "<div>You don't appear to be ranked for this leaderboard. Why not give it a go?</div><br/>";
            }

            echo "<div class='rightalign row'>";
            if ($offset > 0) {
                $prevOffset = $offset - $count;
                echo "<span class='clickablebutton'><a href='/leaderboardinfo.php?i=$lbID&amp;o=$prevOffset&amp;c=$count&amp;f=$friendsOnly'>&lt; Previous $count</a></span> - ";
            }

            //echo "$numActualEntries";

            if ($numActualEntries == $count) {
                //    Max number fetched, i.e. there are more. Can goto next 20.
                $nextOffset = $offset + $count;
                echo "<span class='clickablebutton'><a href='/leaderboardinfo.php?i=$lbID&amp;o=$nextOffset&amp;c=$count&amp;f=$friendsOnly'>Next $count &gt;</a></span>";
            }
            echo "</div>";

            //    Render article comments
            $forceAllowDeleteComments = $permissions >= Permissions::Admin;
            RenderCommentsComponent($user, $numArticleComments, $commentData, $lbID, 6, $forceAllowDeleteComments);

            echo "<b>Forum Topic: </b>";
            RenderLinkToGameForum($user, $cookie, $gameTitle, $gameID, $forumTopicID, $permissions);
            echo "<br><br>";
            ?>
        </div>

    </div>
    <div id='rightcontainer'>
        <?php
        RenderGameLeaderboardsComponent($gameID, $allGameLBData);
        ?>
    </div>

</div>

<?php RenderFooter(); ?>

</body>
</html>
