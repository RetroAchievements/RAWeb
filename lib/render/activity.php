<?php

function RenderFeedComponent($user)
{
    echo "<div class='left'>";

    echo "<div style='float:right;'>";
    $feedFriendsPrefs = 0;
    if (isset($user)) {
        $feedFriendsPrefs = RA_ReadCookie("RAPrefs_Feed");
        $selGlobal = ($feedFriendsPrefs == 1) ? '' : 'checked';
        $selFriends = ($feedFriendsPrefs == 1) ? 'checked' : '';
        echo "<input type='radio' name='feedpref' $selGlobal onclick=\"refreshFeed(false);\" > Global<br>";
        echo "<input type='radio' name='feedpref' $selFriends onclick=\"refreshFeed(true);\" > Friends Only";
    }
    echo "</div>";

    echo "<h2 id='globalfeedtitle'>" . ($feedFriendsPrefs ? "Friends Feed" : "Global Feed") . "</h2>";

    echo "<div id='globalfeed'>";
    echo "Here's a breakdown of what's been happening recently:";
    echo "<table id='feed'><tbody>";
    echo "<tr id='feedloadingfirstrow'>";
    echo "<td class='feedcell'>";
    echo "<img src='" . getenv('ASSET_URL') . "/Images/loading.gif' width='16' height='16' alt='loading icon' />";
    echo "</td>";
    echo "<td class='feedcell'>";
    echo "</td>";
    echo "<td class='feedcellmessage'>";
    echo "loading feed...";
    echo "</td>";
    echo "</tr>";
    echo "</tbody></table>";
    echo "</div>";

    echo "</div>";
}

function getFeedItemTitle($feedData, $withHyperlinks = true, $site = null)
{
    $site = $site ?? getenv('APP_URL');

    $retHTML = '';

    $actType = $feedData['activitytype'];
    $user = $feedData['User'];
    $timestamp = $feedData['timestamp'];
    $achID = $feedData['data']; //    intentional: blind data, dual use
    $achTitle = $feedData['AchTitle'];
    $achPoints = $feedData['AchPoints'];
    $gameID = $feedData['GameID'];
    $gameTitle = $feedData['GameTitle'];
    $console = $feedData['ConsoleName'];

    //    LB only:
    $nextLBID = $feedData['data'];
    $nextLBScore = $feedData['data2'];
    $nextLBName = $feedData['LBTitle'];
    $nextLBFormat = $feedData['LBFormat'];

    //    Inject hyperlinks:
    if ($withHyperlinks) {
        $user = "<a href='$site/user/$user'>$user</a>";
        $achTitle = "<a href='$site/achievement/$achID'>$achTitle</a>";
        $gameTitle = "<a href='$site/game/$gameID'>$gameTitle</a>";
        $nextLBName = "<a href='$site/leaderboardinfo.php?i=$nextLBID'>$nextLBName</a>";
    }

    switch ($actType) {
        case 1: // earned achievement
            $retHTML .= "$user earned $achTitle ($achPoints) in $gameTitle";
            break;
        case 2: // login
            $retHTML .= "$user logged in";
            break;
        case 3: // start playing
            $retHTML .= "$user started playing $gameTitle ($console)";
            break;
        case 4: // upload achievement
            $retHTML = "$user uploaded a new achievement: $achTitle ($achPoints) for $gameTitle ($console)";
            break;
        case 5: // edit achievement
            $retHTML = "$user edited $achTitle ($achPoints) for $gameTitle ($console)";
            break;
        case 6: // complete game
            $retHTML = "$user completed $gameTitle ($console)";
            break;
        case 7: // submit new record
            $retHTML = "$user submitted " . GetFormattedLeaderboardEntry(
                $nextLBFormat,
                $nextLBScore
            ) . " for $nextLBName on $gameTitle ($console)";
            break;
        case 8: // update new record
            $entryType = (strcmp($nextLBFormat, 'TIME') == 0 || strcmp(
                $nextLBFormat,
                'TIMESECS'
            ) == 0) ? "time" : "score";
            $retHTML = "$user improved their $entryType: " . GetFormattedLeaderboardEntry(
                $nextLBFormat,
                $nextLBScore
            ) . " for $nextLBName on $gameTitle ($console)";
            break;
        case 9: // open ticket
            $retHTML = "$user opened a ticket for $achTitle ($achPoints) in $gameTitle ($console)";
            break;
        case 10: // close ticket
            $retHTML = "$user closed a ticket for $achTitle ($achPoints) in $gameTitle ($console)";
            break;
        case 0:
        default:
            $retHTML = 'Unknown';
    }

    return $retHTML;
}

function getFeedItemHTML($feedData, $user)
{
    $retHTML = '';

    //    WILL ALWAYS BE PRESENT in feedData. Note: in SQL order
    $nextID = $feedData['ID'];
    $nextTimestamp = $feedData['timestamp'];
    $nextActivityType = $feedData['activitytype'];
    $nextUser = $feedData['User'];
    $nextData = $feedData['data'];
    $nextData2 = $feedData['data2'];
    $nextGameTitle = $feedData['GameTitle'];
    $nextGameID = $feedData['GameID'];
    $nextGameIcon = $feedData['GameIcon'];
    $nextConsoleName = $feedData['ConsoleName'];
    $nextAchTitle = $feedData['AchTitle'];
    $nextAchDesc = $feedData['AchDesc'];
    $nextAchPoints = $feedData['AchPoints'];
    $nextAchBadge = $feedData['AchBadge'];
    $nextLBName = $feedData['LBTitle'];
    $nextLBDesc = $feedData['LBDesc'];
    $nextLBFormat = $feedData['LBFormat'];

    $rowID = "art_$nextID";
    $dateCell = "<td class='date'><small>&nbsp;" . date("H:i ", $nextTimestamp) . "&nbsp;</small></td>";

    switch ($nextActivityType) {
        case 0: //    misc
            $retHTML .= "<tr id='$rowID' class='feed_login'>";
            $retHTML .= $dateCell;

            $retHTML .= "<td class='icons'>";
            $retHTML .= "</td>";
            $retHTML .= "<td></td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('ASSET_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;

        case 1: //    achievement

            $retHTML .= "<tr id='$rowID' class='feed_won'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";
            $retHTML .= GetAchievementAndTooltipDiv(
                $nextData,
                $nextAchTitle,
                $nextAchDesc,
                $nextAchPoints,
                $nextGameTitle,
                $nextAchBadge,
                true,
                true
            );
            $retHTML .= GetUserAndTooltipDiv($nextUser, true);
            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_won'>";
            $retHTML .= GetUserAndTooltipDiv($nextUser, false);
            $retHTML .= " earned ";
            $retHTML .= GetAchievementAndTooltipDiv(
                $nextData,
                $nextAchTitle,
                $nextAchDesc,
                $nextAchPoints,
                $nextGameTitle,
                $nextAchBadge,
                false
            );

            if (isset($nextData2) && $nextData2 == 1) {
                $retHTML .= " (HARDCORE)";
            }

            $retHTML .= " in ";
            $retHTML .= GetGameAndTooltipDiv(
                $nextGameID,
                $nextGameTitle,
                $nextGameIcon,
                $nextConsoleName,
                false,
                32,
                true
            );

            $retHTML .= "</td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('ASSET_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";

            break;

        case 2: //    login

            $retHTML .= "<tr id='$rowID' class='feed_login'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";
            $retHTML .= "<img alt='$nextUser logged in' title='Logged in' src='/Images/LoginIcon32.png' width='32' height='32' class='badgeimg' />";
            $retHTML .= GetUserAndTooltipDiv($nextUser, true);
            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_login'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, false);
            $retHTML .= " logged in";

            $retHTML .= "</td>";
            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('ASSET_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;

        case 3: //    start playing game

            if ($nextGameTitle !== "UNRECOGNISED") {
                $retHTML .= "<tr id='$rowID' class='feed_startplay'>";
                $retHTML .= $dateCell;

                //    Images:
                $retHTML .= "<td class='icons'>";

                $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, true);
                $retHTML .= GetUserAndTooltipDiv($nextUser, true);

                $retHTML .= "</td>";

                //    Content:
                $retHTML .= "<td class='feed_startplay'>";

                $retHTML .= GetUserAndTooltipDiv($nextUser, false);
                $retHTML .= " started playing ";
                $retHTML .= GetGameAndTooltipDiv(
                    $nextGameID,
                    $nextGameTitle,
                    $nextGameIcon,
                    $nextConsoleName,
                    false,
                    32,
                    true
                );

                $retHTML .= "</td>";
                if ($user !== null) {
                    $retHTML .= "<td class='editbutton'><img src='" . getenv('ASSET_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
                }
                $retHTML .= "</tr>";
            }
            break;

        case 4: //    upload ach

            $retHTML .= "<tr id='$rowID' class='feed_dev1'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetAchievementAndTooltipDiv(
                $nextData,
                $nextAchTitle,
                $nextAchDesc,
                $nextAchPoints,
                $nextGameTitle,
                $nextAchBadge,
                true,
                true
            );
            $retHTML .= GetUserAndTooltipDiv($nextUser, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_dev1'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, false);
            $retHTML .= " uploaded a new achievement: ";
            $retHTML .= GetAchievementAndTooltipDiv(
                $nextData,
                $nextAchTitle,
                $nextAchDesc,
                $nextAchPoints,
                $nextGameTitle,
                $nextAchBadge,
                false
            );

            $retHTML .= "</td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('ASSET_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;

        case 5: //    modify ach

            $retHTML .= "<tr id='$rowID' class='feed_dev2'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetAchievementAndTooltipDiv(
                $nextData,
                $nextAchTitle,
                $nextAchDesc,
                $nextAchPoints,
                $nextGameTitle,
                $nextAchBadge,
                true,
                true
            );
            $retHTML .= GetUserAndTooltipDiv($nextUser, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_dev2'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, false);
            $retHTML .= " edited ";
            $retHTML .= GetAchievementAndTooltipDiv(
                $nextData,
                $nextAchTitle,
                $nextAchDesc,
                $nextAchPoints,
                $nextGameTitle,
                $nextAchBadge,
                false
            );

            $retHTML .= "</td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('ASSET_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;

        case 6: //    complete game
            $retHTML .= "<tr id='$rowID' class='feed_completegame'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, true);
            $retHTML .= GetUserAndTooltipDiv($nextUser, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_completegame'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, false);
            if ($nextData2 == 1) {
                $retHTML .= " MASTERED ";
            } else {
                $retHTML .= " completed ";
            }

            $retHTML .= GetGameAndTooltipDiv(
                $nextGameID,
                $nextGameTitle,
                $nextGameIcon,
                $nextConsoleName,
                false,
                32,
                true
            );

            if ($nextData2 == 1) {
                $retHTML .= " (HARDCORE)";
            }

            $retHTML .= "</td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('ASSET_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";

            break;

        case 7: //    submit new record
            $retHTML .= "<tr id='$rowID' class='feed_submitrecord'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, true);
            $retHTML .= GetUserAndTooltipDiv($nextUser, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_submitrecord'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, false);
            $retHTML .= " submitted ";
            $retHTML .= GetLeaderboardAndTooltipDiv(
                $nextData,
                $nextLBName,
                $nextLBDesc,
                $nextGameTitle,
                $nextGameIcon,
                GetFormattedLeaderboardEntry($nextLBFormat, $nextData2)
            );
            $retHTML .= " for ";
            $retHTML .= GetLeaderboardAndTooltipDiv(
                $nextData,
                $nextLBName,
                $nextLBDesc,
                $nextGameTitle,
                $nextGameIcon,
                $nextLBName
            );
            $retHTML .= " on ";
            $retHTML .= GetGameAndTooltipDiv(
                $nextGameID,
                $nextGameTitle,
                $nextGameIcon,
                $nextConsoleName,
                false,
                32,
                true
            );

            $retHTML .= "</td>";
            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('ASSET_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;

        case 8: //    updated record
            $retHTML .= "<tr id='$rowID' class='feed_updaterecord'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetGameAndTooltipDiv($nextGameID, $nextGameTitle, $nextGameIcon, $nextConsoleName, true);
            $retHTML .= GetUserAndTooltipDiv($nextUser, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_updaterecord'>";

            $entryType = (strcmp($nextLBFormat, 'TIME') == 0 || strcmp(
                $nextLBFormat,
                'TIMESECS'
            ) == 0) ? "time" : "score";

            $retHTML .= GetUserAndTooltipDiv($nextUser, false);
            $retHTML .= " improved their $entryType: ";
            $retHTML .= GetLeaderboardAndTooltipDiv(
                $nextData,
                $nextLBName,
                $nextLBDesc,
                $nextGameTitle,
                $nextGameIcon,
                GetFormattedLeaderboardEntry($nextLBFormat, $nextData2)
            );
            $retHTML .= " for ";
            $retHTML .= GetLeaderboardAndTooltipDiv(
                $nextData,
                $nextLBName,
                $nextLBDesc,
                $nextGameTitle,
                $nextGameIcon,
                $nextLBName
            );
            $retHTML .= " on ";
            $retHTML .= GetGameAndTooltipDiv(
                $nextGameID,
                $nextGameTitle,
                $nextGameIcon,
                $nextConsoleName,
                false,
                32,
                true
            );

            $retHTML .= "</td>";
            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('ASSET_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;

        case 9: // open ticket
        case 10: // close ticket
            $retHTML .= "<tr id='$rowID' class='feed_dev2'>";
            $retHTML .= $dateCell;

            //    Images:
            $retHTML .= "<td class='icons'>";

            $retHTML .= GetAchievementAndTooltipDiv(
                $nextData,
                $nextAchTitle,
                $nextAchDesc,
                $nextAchPoints,
                $nextGameTitle,
                $nextAchBadge,
                true,
                true
            );
            $retHTML .= GetUserAndTooltipDiv($nextUser, true);

            $retHTML .= "</td>";

            //    Content:
            $retHTML .= "<td class='feed_dev2'>";

            $retHTML .= GetUserAndTooltipDiv($nextUser, false);
            $retHTML .= ($nextActivityType == 9 ? " opened " : " closed ") . " a ticket for ";
            $retHTML .= GetAchievementAndTooltipDiv(
                $nextData,
                $nextAchTitle,
                $nextAchDesc,
                $nextAchPoints,
                $nextGameTitle,
                $nextAchBadge,
                false
            );

            $retHTML .= "</td>";

            if ($user !== null) {
                $retHTML .= "<td class='editbutton'><img src='" . getenv('ASSET_URL') . "/Images/Edit.png' width='16' height='16' style='cursor: pointer;' onclick=\"insertEditForm( '$rowID', '5' )\" /></td>";
            }
            $retHTML .= "</tr>";
            break;
    }

    return $retHTML;
}

function RenderFeedItem($feedData, $user)
{
    echo getFeedItemHTML($feedData, $user);
}

function RenderFeedComment($user, $comment, $submittedDate)
{
    echo "<tr class='feed_comment'>";

    $niceDate = date("d M\nG:i y ", strtotime($submittedDate));
    //$fullDateHover = date( "d M\nH:i yy", strtotime( $submittedDate ) );

    sanitize_outputs($comment);

    echo "<td class='smalldate'>$niceDate</td><td class='iconscomment'><a href='/user/$user'><img alt='$user' title='$user' class='badgeimg' src='/UserPic/$user" . ".png' width='32' height='32' /></a></td><td colspan='3'>$comment</td>";

    echo "</tr>";
}
