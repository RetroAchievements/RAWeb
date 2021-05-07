<?php
/**
 * Create the user and tooltip div that is shown when you hover over a username or user avatar.
 *
 * @param string $user the user to get information on
 * @param bool $imageInstead if true return the div for the user avatar, if false return the div for the username
 * @param string|null $customLink custom link if passed in
 * @param int $iconSizeDisplayable custom avatar size if passed in
 * @param string $iconClassDisplayable custom icon display class if passed in
 * @return string
 */
function GetUserAndTooltipDiv(
    $user,
    $imageInstead = false,
    $customLink = null,
    $iconSizeDisplayable = 32,
    $iconClassDisplayable = 'badgeimg'
) {
    getUserCardData($user, $userCardInfo);

    if (!$userCardInfo) {
        if ($imageInstead) {
            return '';
        }
        return '<del>' . $user . '</del>';
    }

    $userMotto = $userCardInfo['Motto'];
    $userPoints = $userCardInfo['TotalPoints'];
    $userTruePoints = $userCardInfo['TotalTruePoints'];
    $userAccountType = PermissionsToString($userCardInfo['Permissions']);
    $userRank = $userCardInfo['Rank'];
    $lastLogin = $userCardInfo['LastActivity'] ? getNiceDate(strtotime($userCardInfo['LastActivity'])) : null;
    $memberSince = $userCardInfo['MemberSince'] ? getNiceDate(strtotime($userCardInfo['MemberSince']), true) : null;

    $tooltip = "<div id=\'objtooltip\' class=\'usercard\'>";
    $tooltip .= "<table><tbody>";
    $tooltip .= "<tr>";
    $tooltip .= "<td class=\'usercardavatar\'><img src=\'/UserPic/" . $user . ".png\'/>";
    $tooltip .= "<td class=\'usercard\'>";
    $tooltip .= "<table><tbody>";
    $tooltip .= "<tr>";
    $tooltip .= "<td class=\'usercardusername\'>$user</td>";
    $tooltip .= "<td class=\'usercardaccounttype\'>$userAccountType</td>";
    $tooltip .= "</tr>";

    //Add the user motto if it's set
    if ($userMotto !== null && mb_strlen($userMotto) > 2) {
        $userMotto = str_replace('\'', '\\\'', $userMotto);
        $userMotto = str_replace('"', '\\\'\\\'', $userMotto);
        $tooltip .= "<tr>";
        $tooltip .= "<td colspan=\'2\' height=\'32px\'><span class=\'usermotto tooltip\'>$userMotto</span></td>";
        $tooltip .= "</tr>";
    } else {
        //Insert blank row to add whitespace where motto would be
        $tooltip .= "<tr>";
        $tooltip .= "<td height=\'24px\'></td>";
        $tooltip .= "</tr>";
    }

    //Add the user points if there are any
    if ($userPoints !== null) {
        $tooltip .= "<tr>";
        $tooltip .= "<td class=\'usercardbasictext\'><b>Points:</b> $userPoints ($userTruePoints)</td>";
        $tooltip .= "</tr>";
    } else {
        $tooltip .= "<tr>";
        $tooltip .= "<td class=\'usercardbasictext\'><b>Points:</b> 0</td>";
        $tooltip .= "</tr>";
    }

    //Add the other user informaiton
    $tooltip .= "<tr>";
    $tooltip .= "<td class=\'usercardbasictext\'><b>Site Rank:</b> $userRank</td>";
    $tooltip .= "</tr>";
    if ($lastLogin) {
        $tooltip .= "<tr>";
        $tooltip .= "<td class=\'usercardbasictext\'><b>Last Activity:</b> $lastLogin</td>";
        $tooltip .= "</tr>";
    }
    if ($memberSince) {
        $tooltip .= "<tr>";
        $tooltip .= "<td class=\'usercardbasictext\'><b>Member Since:</b> $memberSince</td>";
        $tooltip .= "</tr>";
    }
    $tooltip .= "</tbody></table>";
    $tooltip .= "</td>";
    $tooltip .= "</tr>";
    $tooltip .= "</tbody></table>";
    $tooltip .= "</div>";

    $tooltip = htmlspecialchars($tooltip);

    $linkURL = "/user/$user";
    if (!empty($customLink)) {
        $linkURL = $customLink;
    }

    $displayable = $user;
    if ($imageInstead == true) {
        $displayable = "<img loading='lazy' src='/UserPic/$user" . ".png' width='$iconSizeDisplayable' height='$iconSizeDisplayable' alt='' title='$user' class='$iconClassDisplayable' />";
    }

    return "<span class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='$linkURL'>" .
        "$displayable" .
        "</a>" .
        "</span>";
}

function RenderSiteAwards($userAwards)
{
    $imageSize = 48;
    $numCols = 5;

    $numItems = count($userAwards);
    //var_dump( $userAwards );

    echo "<div id='siteawards' class='component' >";

    echo "<h3>Site Awards</h3>";

    echo "<div class='siteawards'>";

    echo "<table class='siteawards'><tbody>";

    for ($i = 0; $i < $numItems / 3; $i++) {
        echo "<tr>";
        for ($j = 0; $j < $numCols; $j++) {
            $nOffs = ($i * $numCols) + $j;
            if ($nOffs >= $numItems) {
                continue;
            }

            $elem = $userAwards[$nOffs];

            //$awardedAt = $elem[ 'AwardedAt' ];
            $awardType = $elem['AwardType'];
            settype($awardType, 'integer');
            $awardData = $elem['AwardData'];
            $awardDataExtra = $elem['AwardDataExtra'];
            $awardGameTitle = $elem['Title'];
            $awardGameConsole = $elem['ConsoleName'];
            $awardGameImage = $elem['ImageIcon'];
            $awardDate = getNiceDate($elem['AwardedAt']);
            //$awardGameFlags = $elem[ 'Flags' ];
            $awardButGameIsIncomplete = (isset($elem['Incomplete']) && $elem['Incomplete'] == 1);
            $imgclass = 'badgeimg siteawards';

            sanitize_outputs(
                $awardGameTitle,
                $awardGameConsole,
            );

            if ($awardType == 1) {
                //echo $awardDataExtra;
                if ($awardDataExtra == '1') {
                    $tooltip = "MASTERED $awardGameTitle ($awardGameConsole)";
                    $imgclass = 'goldimage';
                } else {
                    $tooltip = "Completed $awardGameTitle ($awardGameConsole)";
                }

                if ($awardButGameIsIncomplete) {
                    $tooltip .= "...<br>but more achievements have been added!<br>Click here to find out what you're missing!";
                }

                $imagepath = $awardGameImage;
                $linkdest = "/game/$awardData";
            } elseif ($awardType == 2) { //    Developed a number of earned achievements
                $tooltip = "Awarded for being a hard-working developer and producing achievements that have been earned over " . RA\AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$awardData] . " times!";

                $imagepath = getenv('ASSET_URL') . "/Images/_Trophy" . RA\AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$awardData] . ".png";

                $linkdest = ''; //    TBD: referrals page?
            } elseif ($awardType == 3) { //    Yielded an amount of points earned by players
                $tooltip = "Awarded for producing many valuable achievements, providing over " . RA\AwardThreshold::DEVELOPER_POINT_BOUNDARIES[$awardData] . " points to the community!";

                if ($awardData == 0) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00133.png";
                } elseif ($awardData == 1) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00134.png";
                } elseif ($awardData == 2) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00137.png";
                } elseif ($awardData == 3) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00135.png";
                } elseif ($awardData == 4) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00136.png";
                } else {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00136.png";
                }

                $linkdest = ''; //    TBD: referrals page?
            } elseif ($awardType == 4) { //    Referrals
                $tooltip = "Referred $awardData members";

                if ($awardData < 2) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                } elseif ($awardData < 3) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                } elseif ($awardData < 5) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                } elseif ($awardData < 10) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                } elseif ($awardData < 15) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                } else {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                }

                $linkdest = ''; //    TBD: referrals page?
            } elseif ($awardType == 5) { //    Signed up for facebook!
                $tooltip = "Awarded for associating their account with Facebook! Thanks for spreading the word!";

                $imagepath = getenv('ASSET_URL') . "/Images/_FBAssoc.png";
                $linkdest = "/controlpanel.php#facebook";
            } elseif ($awardType == 6) {  //  Patreon Supporter
                $tooltip = 'Awarded for being a Patreon supporter! Thank-you so much for your support!';

                $imagepath = getenv('ASSET_URL') . '/Badge/PatreonBadge.png';
                $linkdest = 'https://www.patreon.com/retroachievements';
            } else {
                // error_log("Unknown award type" . $awardType);
                continue;
            }

            $tooltip .= "\r\nAwarded on $awardDate";
            $displayable = "<a href=\"$linkdest\"><img class=\"$imgclass\" alt=\"$tooltip\" title=\"$tooltip\" src=\"$imagepath\" width=\"$imageSize\" height=\"$imageSize\" /></a>";
            $tooltipImagePath = "$imagepath";
            $tooltipImageSize = 96; //64;    //    screw that, lets make it big!
            $tooltipTitle = "Site Award";

            // $textWithTooltip = WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);
            $textWithTooltip = $displayable;

            $newOverlayDiv = '';
            // if ($awardButGameIsIncomplete) {
            //     $newOverlayDiv = WrapWithTooltip("<a href=\"$linkdest\"><div class=\"trophyimageincomplete\"></div></a>", $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);
            // }

            echo "<td><div class='trophycontainer'><div class='trophyimage'>$textWithTooltip</div>$newOverlayDiv</div></td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "</div>";

    //echo "<br>";

    echo "</div>";
}

function RenderCompletedGamesList($user, $userCompletedGamesList)
{
    echo "<div id='completedgames' class='component' >";

    echo "<h3>Completion Progress</h3>";
    echo "<div id='usercompletedgamescomponent'>";

    echo "<table><tbody>";
    echo "<tr><th colspan='2'>Game</th><th>Completion</th></tr>";

    $numItems = count($userCompletedGamesList);
    for ($i = 0; $i < $numItems; $i++) {
        $nextGameID = $userCompletedGamesList[$i]['GameID'];
        $nextConsoleName = $userCompletedGamesList[$i]['ConsoleName'];
        $nextTitle = $userCompletedGamesList[$i]['Title'];
        $nextImageIcon = $userCompletedGamesList[$i]['ImageIcon'];

        sanitize_outputs(
            $nextConsoleName,
            $nextTitle,
        );

        $nextMaxPossible = $userCompletedGamesList[$i]['MaxPossible'];

        $nextNumAwarded = $userCompletedGamesList[$i]['NumAwarded'];
        if ($nextNumAwarded == 0 || $nextMaxPossible == 0) { //    Ignore 0 (div by 0 anyway)
            continue;
        }

        $pctAwardedNormal = ($nextNumAwarded / $nextMaxPossible) * 100.0;

        $nextNumAwardedHC = isset($userCompletedGamesList[$i]['NumAwardedHC']) ? $userCompletedGamesList[$i]['NumAwardedHC'] : 0;
        $pctAwardedHC = ($nextNumAwardedHC / $nextMaxPossible) * 100.0;
        $pctAwardedHCProportional = ($nextNumAwardedHC / $nextNumAwarded) * 100.0; //    This is given as a proportion of normal completion!
        //$nextTotalAwarded = $nextNumAwarded + $nextNumAwardedHC;
        $nextTotalAwarded = $nextNumAwardedHC > $nextNumAwarded ? $nextNumAwardedHC : $nextNumAwarded; //    Just take largest

        if (!isset($nextMaxPossible)) {
            continue;
        }

        $nextPctAwarded = $userCompletedGamesList[$i]['PctWon'] * 100.0;
        //$nextCompletionPct = sprintf( "%2.2f", $nextNumAwarded / $nextMaxPossible );

        echo "<tr>";

        $tooltipImagePath = "$nextImageIcon";
        $tooltipImageSize = 96; //64;    //    screw that, lets make it big!
        $tooltipTitle = "$nextTitle";
        //$tooltipTitle = str_replace( "'", "\'", $tooltipTitle );
        $tooltip = "Progress: $nextNumAwarded achievements won out of a possible $nextMaxPossible";
        $tooltip = sprintf("%s (%01.1f%%)", $tooltip, ($nextTotalAwarded / $nextMaxPossible) * 100);

        $displayable = "<a href=\"/game/$nextGameID\"><img alt=\"$tooltipTitle ($nextConsoleName)\" title=\"$tooltipTitle\" src=\"$nextImageIcon\" width=\"32\" height=\"32\" />";
        // $textWithTooltip = WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);
        $textWithTooltip = $displayable;

        echo "<td class='gameimage'>$textWithTooltip</td>";
        $displayable = "<a href=\"/game/$nextGameID\">$nextTitle</a>";
        // $textWithTooltip = WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);
        $textWithTooltip = $displayable;
        echo "<td class=''>$textWithTooltip</td>";
        echo "<td class='progress'>";

        //if( $nextNumAwardedHC > 0 )

        echo "<div class='progressbar completedgames'>";
        echo "<div class='completion' style='width:$pctAwardedNormal%'>";
        echo "<div class='completionhardcore' style='width:$pctAwardedHCProportional%' title='Hardcore earned: $nextNumAwardedHC/$nextMaxPossible'>";
        echo "&nbsp;";
        echo "</div>";
        echo "</div>";
        echo "$nextTotalAwarded/$nextMaxPossible won<br>";
        echo "</div>";

        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";

    echo "</div>";
}

function RenderRecentlyAwardedComponent($user, $points)
{
    $componentHeight = 514;
    $style = "height:$componentHeight" . "px";
    echo "<div class='component' style='$style'>";
    echo "<h3>recently awarded</h3>";

    $count = getRecentlyEarnedAchievements(10, null, $dataArray);

    $lastDate = '';

    echo "<table><tbody>";
    echo "<tr><th>Date</th><th>User</th><th>Achievement</th><th>Game</th></tr>";

    $iter = 0;

    for ($i = 0; $i < $count; $i++) {
        $timestamp = strtotime($dataArray[$i]['DateAwarded']);
        $dateAwarded = date("d M", $timestamp);

        if (date("d", $timestamp) == date("d")) {
            $dateAwarded = "Today";
        } elseif (date("d", $timestamp) == (date("d") - 1)) {
            $dateAwarded = "Y'day";
        }

        if ($lastDate !== $dateAwarded) {
            $lastDate = $dateAwarded;
        }

        echo "<tr>";

        $wonAt = date("H:i", $timestamp);
        $nextUser = $dataArray[$i]['User'];
        $achID = $dataArray[$i]['AchievementID'];
        $achTitle = $dataArray[$i]['Title'];
        $achDesc = $dataArray[$i]['Description'];
        $achPoints = $dataArray[$i]['Points'];
        $badgeName = $dataArray[$i]['BadgeName'];
        //$badgeFullPath = getenv('ASSET_URL')."/Badge/" . $badgeName . ".png";
        $gameTitle = $dataArray[$i]['GameTitle'];
        $gameID = $dataArray[$i]['GameID'];
        $gameIcon = $dataArray[$i]['GameIcon'];
        $consoleTitle = $dataArray[$i]['ConsoleTitle'];

        echo "<td>";
        echo "$dateAwarded $wonAt";
        echo "</td>";

        echo "<td>";
        //echo "<a href='/user/" . $nextUser . "'><img alt='$nextUser' title='$nextUser' src='/UserPic/$nextUser.png' width='32' height='32' /></a>";
        echo GetUserAndTooltipDiv($nextUser, true);
        echo "</td>";

        echo "<td><div class='fixheightcell'>";
        echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, true);
        echo "</div></td>";

        echo "<td><div class='fixheightcell'>";
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleTitle, true);
        echo "</div></td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "<br>";
    echo "</div>";
}
