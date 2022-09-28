<?php

use RA\Permissions;
use RA\Rank;
use RA\RankType;

/**
 * Create the user and tooltip div that is shown when you hover over a username or user avatar.
 */
function GetUserAndTooltipDiv(
    ?string $user,
    $imageInstead = false,
    $customLink = null,
    $iconSizeDisplayable = 32,
    $iconClassDisplayable = 'badgeimg'
): string {
    getUserCardData($user, $userCardInfo);

    if (!$userCardInfo) {
        if ($imageInstead) {
            return '';
        }

        $userSanitized = $user;
        sanitize_outputs($userSanitized);

        return '<del>' . $userSanitized . '</del>';
    }

    $linkURL = '/user/' . $userCardInfo['User'];
    if (!empty($customLink)) {
        $linkURL = $customLink;
    }

    $displayable = $userCardInfo['User'];
    if ($imageInstead) {
        $displayable = "<img loading='lazy' src='" . media_asset('/UserPic/' . $user . '.png') . "' width='$iconSizeDisplayable' height='$iconSizeDisplayable' alt='$displayable' class='$iconClassDisplayable' />";
    }

    return "<span class='inline' onmouseover=\"Tip(loadCard('user', '$user'))\" onmouseout=\"UnTip()\">" .
        "<a href='$linkURL'>" .
        "$displayable" .
        "</a>" .
        "</span>";
}

function renderUserCard(string $user): string
{
    getUserCardData($user, $userCardInfo);

    $username = $userCardInfo['User'];
    $userMotto = $userCardInfo['Motto'];
    $userHardcorePoints = $userCardInfo['HardcorePoints'];
    $userSoftcorePoints = $userCardInfo['SoftcorePoints'];
    $userTruePoints = $userCardInfo['TotalTruePoints'];
    $userAccountType = Permissions::toString($userCardInfo['Permissions']);
    $userUntracked = $userCardInfo['Untracked'];
    $lastLogin = $userCardInfo['LastActivity'] ? getNiceDate(strtotime($userCardInfo['LastActivity'])) : null;
    $memberSince = $userCardInfo['MemberSince'] ? getNiceDate(strtotime($userCardInfo['MemberSince']), true) : null;

    $tooltip = "<div class='tooltip-body flex items-start gap-2 p-2' style='width: 400px'>";

    $tooltip .= "<img width='128' height='128' src='" . media_asset('/UserPic/' . $username . '.png') . "'>";

    $tooltip .= "<div class='grow' style='font-size: 8pt'>";

    $tooltip .= "<div class='flex justify-between mb-2'>";
    $tooltip .= "<div class='usercardusername'>$username</div>";
    $tooltip .= "<div class='usercardaccounttype'>$userAccountType</div>";
    $tooltip .= "</div>";

    // Add the user motto if it's set
    if ($userMotto !== null && mb_strlen($userMotto) > 2) {
        sanitize_outputs($userMotto);
        $tooltip .= "<div class='usermotto mb-1'>$userMotto</div>";
    }

    // Add the user points if there are any
    if ($userHardcorePoints > $userSoftcorePoints) {
        $tooltip .= "<div class='usercardbasictext'><b>Points:</b> $userHardcorePoints ($userTruePoints)</div>";
        $userRank = $userHardcorePoints < Rank::MIN_POINTS ? 0 : getUserRank($username, RankType::Hardcore);
        $userRankLabel = 'Site Rank';
    } elseif ($userSoftcorePoints > 0) {
        $tooltip .= "<div class='usercardbasictext'><b>Softcore Points:</b> $userSoftcorePoints</div>";
        $userRank = $userSoftcorePoints < Rank::MIN_POINTS ? 0 : getUserRank($username, RankType::Softcore);
        $userRankLabel = 'Softcore Rank';
    } else {
        $tooltip .= "<div class='usercardbasictext'><b>Points:</b> 0</div>";
        $userRank = 0;
        $userRankLabel = 'Site Rank';
    }

    // Add the other user information
    if ($userUntracked) {
        $tooltip .= "<div class='usercardbasictext'><b>$userRankLabel:</b> Untracked</div>";
    } elseif ($userRank == 0) {
        $tooltip .= "<div class='usercardbasictext'><b>$userRankLabel:</b> Needs at least " . Rank::MIN_POINTS . " points </div>";
    } else {
        $tooltip .= "<div class='usercardbasictext'><b>$userRankLabel:</b> $userRank</div>";
    }

    if ($lastLogin) {
        $tooltip .= "<div class='usercardbasictext'><b>Last Activity:</b> $lastLogin</div>";
    }
    if ($memberSince) {
        $tooltip .= "<div class='usercardbasictext'><b>Member Since:</b> $memberSince</div>";
    }
    $tooltip .= "</div>";
    $tooltip .= "</div>";

    return $tooltip;
}

function RenderCompletedGamesList($userCompletedGamesList): void
{
    echo "<div id='completedgames' class='component' >";

    echo "<h3>Completion Progress</h3>";
    echo "<div id='usercompletedgamescomponent'>";

    echo "<table><tbody>";

    $numItems = is_countable($userCompletedGamesList) ? count($userCompletedGamesList) : 0;
    for ($i = 0; $i < $numItems; $i++) {
        $nextGameID = $userCompletedGamesList[$i]['GameID'];
        $nextConsoleName = $userCompletedGamesList[$i]['ConsoleName'];
        $nextTitle = $userCompletedGamesList[$i]['Title'];
        $nextImageIcon = media_asset($userCompletedGamesList[$i]['ImageIcon']);

        sanitize_outputs(
            $nextConsoleName,
            $nextTitle,
        );

        $nextMaxPossible = $userCompletedGamesList[$i]['MaxPossible'];

        $nextNumAwarded = $userCompletedGamesList[$i]['NumAwarded'];
        if ($nextNumAwarded == 0 || $nextMaxPossible == 0) { // Ignore 0 (div by 0 anyway)
            continue;
        }

        $pctAwardedNormal = ($nextNumAwarded / $nextMaxPossible) * 100.0;

        $nextNumAwardedHC = $userCompletedGamesList[$i]['NumAwardedHC'] ?? 0;
        $pctAwardedHC = ($nextNumAwardedHC / $nextMaxPossible) * 100.0;
        $pctAwardedHCProportional = ($nextNumAwardedHC / $nextNumAwarded) * 100.0; // This is given as a proportion of normal completion!
        // $nextTotalAwarded = $nextNumAwarded + $nextNumAwardedHC;
        $nextTotalAwarded = max($nextNumAwardedHC, $nextNumAwarded); // Just take largest

        if (!isset($nextMaxPossible)) {
            continue;
        }

        $nextPctAwarded = $userCompletedGamesList[$i]['PctWon'] * 100.0;
        // $nextCompletionPct = sprintf( "%2.2f", $nextNumAwarded / $nextMaxPossible );

        echo "<tr>";

        $tooltipImagePath = "$nextImageIcon";
        $tooltipImageSize = 96;
        $tooltipTitle = attributeEscape($nextTitle);
        $tooltip = "Progress: $nextNumAwarded achievements won out of a possible $nextMaxPossible";
        $tooltip = sprintf("%s (%01.1f%%)", $tooltip, ($nextTotalAwarded / $nextMaxPossible) * 100);

        $displayable = "<a href=\"/game/$nextGameID\"><img alt=\"$tooltipTitle ($nextConsoleName)\" title=\"$tooltipTitle\" src=\"$nextImageIcon\" width=\"32\" height=\"32\" loading=\"lazy\" />";
        // $textWithTooltip = WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);
        $textWithTooltip = $displayable;

        echo "<td class='gameimage'>$textWithTooltip</td>";
        $displayable = "<a href=\"/game/$nextGameID\">$nextTitle</a>";
        // $textWithTooltip = WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);
        $textWithTooltip = $displayable;
        echo "<td class=''>$textWithTooltip</td>";
        echo "<td class='progress'>";

        echo "<div class='progressbar player'>";
        echo "<div class='completion' style='width:$pctAwardedNormal%'>";
        echo "<div class='completion-hardcore' style='width:$pctAwardedHCProportional%' title='Hardcore: $nextNumAwardedHC/$nextMaxPossible'></div>";
        echo "</div>";
        echo "</div>";
        echo "<div class='progressbar-label lg:text-center'>";
        echo "$nextTotalAwarded of $nextMaxPossible";
        echo "</div>";

        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";

    echo "</div>";
}

function RenderRecentlyAwardedComponent(): void
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
        // $badgeFullPath = media_asset("Badge/$badgeName.png");
        $gameTitle = $dataArray[$i]['GameTitle'];
        $gameID = $dataArray[$i]['GameID'];
        $gameIcon = $dataArray[$i]['GameIcon'];
        $consoleTitle = $dataArray[$i]['ConsoleTitle'];

        echo "<td>";
        echo "$dateAwarded $wonAt";
        echo "</td>";

        echo "<td>";
        // echo "<a href='/user/" . $nextUser . "'><img alt='$nextUser' title='$nextUser' src='/UserPic/$nextUser.png' width='32' height='32' /></a>";
        echo GetUserAndTooltipDiv($nextUser, true);
        echo "</td>";

        echo "<td><div>";
        echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, true);
        echo "</div></td>";

        echo "<td><div>";
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleTitle, true);
        echo "</div></td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "<br>";
    echo "</div>";
}
