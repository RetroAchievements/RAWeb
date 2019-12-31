<?php
/**
 * @param $lbID
 * @param $lbName
 * @param $lbDesc
 * @param $gameName
 * @param $gameIcon
 * @param $displayable
 * @return string
 */
function GetLeaderboardAndTooltipDiv($lbID, $lbName, $lbDesc, $gameName, $gameIcon, $displayable)
{
    $tooltipIconSize = 64; //96;

    $lbNameStr = str_replace("'", "\'", $lbName);
    $lbDescStr = str_replace("'", "\'", $lbDesc);
    $gameNameStr = str_replace("'", "\'", $gameName);

    $tooltip = "<div id=\'objtooltip\'>" .
        "<img src=\'$gameIcon\' width=\'$tooltipIconSize\' height=\'$tooltipIconSize\ />" .
        "<b>$lbNameStr</b><br>" .
        "<i>($gameNameStr)</i><br>" .
        "<br>" .
        "$lbDescStr<br>" .
        "</div>";

    $tooltip = str_replace('<', '&lt;', $tooltip);
    $tooltip = str_replace('>', '&gt;', $tooltip);

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='/leaderboardinfo.php?i=$lbID'>" .
        "$displayable" .
        "</a>" .
        "</div>";
}

/**
 * @param $gameID
 * @param $lbData
 */
function RenderGameLeaderboardsComponent($gameID, $lbData)
{
    $numLBs = count($lbData);
    echo "<div class='component'>";
    echo "<h3>Leaderboards</h3>";

    if ($numLBs == 0) {
        echo "No leaderboards found: why not suggest some for this game? ";
        echo "<div class='rightalign'><a href='/leaderboardList.php'>Leaderboard List</a></div>";
    } else {
        echo "<table><tbody>";

        $count = 0;
        foreach ($lbData as $lbItem) {
            $lbID = $lbItem['LeaderboardID'];
            $lbTitle = $lbItem['Title'];
            $lbDesc = $lbItem['Description'];
            $bestScoreUser = $lbItem['User'];
            $bestScore = $lbItem['Score'];
            $scoreFormat = $lbItem['Format'];

            //    Title
            echo "<tr>";
            echo "<td colspan='2'>";
            echo "<div class='fixheightcellsmaller'><a href='/leaderboardinfo.php?i=$lbID'>$lbTitle</a></div>";
            echo "<div class='fixheightcellsmaller'>$lbDesc</div>";
            echo "</td>";
            echo "</tr>";

            //    Score/Best entry
            echo "<tr class='altdark'>";
            echo "<td>";
            //echo "<a href='/User/" . $bestScoreUser . "'><img alt='$bestScoreUser' title='$bestScoreUser' src='/UserPic/$bestScoreUser.png' width='32' height='32' /></a>";
            echo GetUserAndTooltipDiv($bestScoreUser, true);
            echo GetUserAndTooltipDiv($bestScoreUser, false);
            echo "</td>";
            echo "<td>";
            echo "<a href='/leaderboardinfo.php?i=$lbID'>";
            echo GetFormattedLeaderboardEntry($scoreFormat, $bestScore);
            echo "</a>";
            echo "</td>";

            echo "</tr>";

            $count++;
        }

        echo "</tbody></table>";
    }

    //echo "<div class='rightalign'><a href='/forumposthistory.php'>more...</a></div>";

    echo "</div>";
}

function RenderScoreLeaderboardComponent($user, $points, $friendsOnly, $numToFetch = 10)
{
    $count = getTopUsersByScore($numToFetch, $dataArray, ($friendsOnly == true) ? $user : null);

    echo "<div id='leaderboard' class='component' >";

    if ($friendsOnly == true) {
        echo "<h3>Friends Leaderboard</h3>";
        if ($count == 0) {
            echo "You don't appear to have friends registered here yet. Why not leave a comment on the <a href='/forum.php'>forums</a> or <a href='/userList.php'>browse the user pages</a> to find someone to add to your friend list?<br>";
        }
    } else {
        echo "<h3>Global Leaderboard</h3>";
    }

    $userRank = ($user !== null) ? $userRank = getUserRank($user) : 0;

    echo "<table><tbody>";
    echo "<tr><th>Rank</th><th colspan='2'>User</th><th>Points</th></tr>";

    for ($i = 0; $i < $count; $i++) {
        if (!isset($dataArray[$i])) {
            continue;
        }

        $nextUser = $dataArray[$i][1];
        $nextPoints = $dataArray[$i][2];
        $nextTruePoints = $dataArray[$i][3];

        echo "<tr>";
        echo "<td class='rank'>" . ($i + 1) . "</td>";
        echo "<td>";
        echo GetUserAndTooltipDiv($nextUser, true);
        echo "</td>";

        echo "<td>";
        echo GetUserAndTooltipDiv($nextUser, false);
        echo "</td>";
        echo "<td class='points'>$nextPoints<span class='TrueRatio'>  ($nextTruePoints)</span></td>";
        echo "</tr>";
    }
    if ($user !== null && $friendsOnly == false) {
        echo "<tr>";
        echo "<td class='rank'> $userRank </td>";
        echo "<td>";
        echo GetUserAndTooltipDiv($user, true);
        echo "</td>";

        echo "<td>";
        echo GetUserAndTooltipDiv($user, false);
        echo "</td>";
        echo "<td class='points'>$points</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";

    if ($friendsOnly == false) {
        echo "<span class='morebutton'><a href='/userList.php?s=2'>more...</a></span>";
    } else {
        echo "<span class='morebutton'><a href='/friends.php'>more...</a></span>";
    }

    echo "</div>";
}

function RenderTopAchieversComponent($gameTopAchievers)
{
    $numItems = count($gameTopAchievers);

    echo "<div id='leaderboard' class='component' >";

    echo "<h3>High Scores</h3>";

    echo "<table><tbody>";
    echo "<tr><th>Pos</th><th colspan='2' style='max-width:30%'>User</th><th>Points</th></tr>";

    for ($i = 0; $i < $numItems; $i++) {
        if (!isset($gameTopAchievers[$i])) {
            continue;
        }

        $nextUser = $gameTopAchievers[$i]['User'];
        $nextPoints = $gameTopAchievers[$i]['TotalScore'];
        $nextLastAward = $gameTopAchievers[$i]['LastAward'];

        echo "<tr>";

        echo "<td class='rank'>";
        echo $i + 1;
        echo "</td>";

        echo "<td>";
        echo GetUserAndTooltipDiv($nextUser, true);
        echo "</td>";

        echo "<td class='user'>";
        echo GetUserAndTooltipDiv($nextUser, false);
        echo "</td>";

        echo "<td class='points'>";
        echo "<span class='hoverable' title='Latest awarded at $nextLastAward'>$nextPoints</span>";
        echo "</td>";

        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "</div>";
}
