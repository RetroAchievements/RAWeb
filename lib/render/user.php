<?php

use RA\LinkStyle;
use RA\Permissions;
use RA\Rank;
use RA\RankType;

/**
 * Create the tooltip div that is shown when you hover over a username or user avatar.
 */
function _BuildUserTooltipDiv(string $user, array $userCardInfo): string {
    $userSanitized = $user;
    sanitize_outputs($userSanitized);

    $userMotto = $userCardInfo['Motto'];
    $userHardcorePoints = $userCardInfo['HardcorePoints'];
    $userSoftcorePoints = $userCardInfo['SoftcorePoints'];
    $userTruePoints = $userCardInfo['TotalTruePoints'];
    $userAccountType = Permissions::toString($userCardInfo['Permissions']);
    $userUntracked = $userCardInfo['Untracked'];
    $lastLogin = $userCardInfo['LastActivity'] ? getNiceDate(strtotime($userCardInfo['LastActivity'])) : null;
    $memberSince = $userCardInfo['MemberSince'] ? getNiceDate(strtotime($userCardInfo['MemberSince']), true) : null;

    $tooltip = "<div id='objtooltip' class='flex items-start' style='max-width: 400px;'>";
    $tooltip .= "<table><tbody>";
    $tooltip .= "<tr>";
    $tooltip .= "<td><img width='128' height='128' src='" . media_asset('/UserPic/' . $userSanitized . '.png') . "'/>";
    $tooltip .= "<td class='usercard'>";
    $tooltip .= "<table><tbody>";
    $tooltip .= "<tr>";
    $tooltip .= "<td class='usercardusername'>$userSanitized</td>";
    $tooltip .= "<td class='usercardaccounttype'>$userAccountType</td>";
    $tooltip .= "</tr>";

    // Add the user motto if it's set
    $tooltip .= "<tr>";
    if ($userMotto !== null && mb_strlen($userMotto) > 2) {
        sanitize_outputs($userMotto);
        $tooltip .= "<td colspan='2' height='32px'><span class='usermotto tooltip'>$userMotto</span></td>";
    } else {
        // Insert blank row to add whitespace where motto would be
        $tooltip .= "<td height='24px'></td>";
    }
    $tooltip .= "</tr>";

    // Add the user points if there are any
    $tooltip .= "<tr>";
    if ($userHardcorePoints > $userSoftcorePoints) {
        $tooltip .= "<td colspan='2' class='usercardbasictext'><b>Points:</b> $userHardcorePoints ($userTruePoints)</td>";
        $userRank = $userHardcorePoints < Rank::MIN_POINTS ? 0 : getUserRank($user, RankType::Hardcore);
        $userRankLabel = 'Site Rank';
    } elseif ($userSoftcorePoints > 0) {
        $tooltip .= "<td colspan='2' class='usercardbasictext'><b>Softcore Points:</b> $userSoftcorePoints</td>";
        $userRank = $userSoftcorePoints < Rank::MIN_POINTS ? 0 : getUserRank($user, RankType::Softcore);
        $userRankLabel = 'Softcore Rank';
    } else {
        $tooltip .= "<td colspan='2' class='usercardbasictext'><b>Points:</b> 0</td>";
        $userRank = 0;
        $userRankLabel = 'Site Rank';
    }
    $tooltip .= "</tr>";

    // Add the other user information
    $tooltip .= "<tr>";
    if ($userUntracked) {
        $tooltip .= "<td colspan='2' class='usercardbasictext'><b>$userRankLabel:</b> Untracked</td>";
    } elseif ($userRank == 0) {
        $tooltip .= "<td colspan='2' class='usercardbasictext'><b>$userRankLabel:</b> Needs at least " . Rank::MIN_POINTS . " points </td>";
    } else {
        $tooltip .= "<td colspan='2' class='usercardbasictext'><b>$userRankLabel:</b> $userRank</td>";
    }
    $tooltip .= "</tr>";

    if ($lastLogin) {
        $tooltip .= "<tr>";
        $tooltip .= "<td colspan='2' class='usercardbasictext'><b>Last Activity:</b> $lastLogin</td>";
        $tooltip .= "</tr>";
    }
    if ($memberSince) {
        $tooltip .= "<tr>";
        $tooltip .= "<td colspan='2' class='usercardbasictext'><b>Member Since:</b> $memberSince</td>";
        $tooltip .= "</tr>";
    }
    $tooltip .= "</tbody></table>";
    $tooltip .= "</td>";
    $tooltip .= "</tr>";
    $tooltip .= "</tbody></table>";
    $tooltip .= "</div>";

    return $tooltip;
}

function RenderUserLink(?string $username, int $style, array &$cache = null): void
{
    if (!$username) {
        return;
    }

    echo GetUserLink($username, $style, $cache);
}

function GetUserLink(string $username, int $style, array &$cache = null): string
{
    $userCardInfo = null;

    if ($cache !== null && array_key_exists($username, $cache)) {
        $userCardInfo = $cache[$username];
    }
    else {
        getUserCardData($username, $userCardData);
        if ($userCardData) {
            $tooltip = _BuildUserTooltipDiv($username, $userCardData);

            $userCardInfo = [
                "DisplayName" => $username,
                "AvatarUrl" => media_asset("/UserPic/$username.png"),
                "ToolTip" => $tooltip,
            ];
        }

        if ($cache !== null) {
            $cache[$username] = $userCardInfo;
        }
    }

    if (!$userCardInfo) {
        // deleted or non-existant user
        if (!LinkStyle::hasText($style)) {
            // TODO: default image with <del> tooltip?
            return "";
        }

        $usernameSanitized = $username;
        sanitize_outputs($usernameSanitized);

        return '<del>' . $usernameSanitized . '</del>';
    }

    return _BuildLink($userCardInfo['DisplayName'], $userCardInfo['AvatarUrl'], "/user/$username", $style, $userCardInfo['ToolTip']);
}

function _BuildLink(string $text, string $image, string $link, int $style, string $tooltip)
{
    $tooltip = tipEscape($tooltip);

    $retval = "<span class='bb_inline' style='white-space:nowrap' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >";
    $retval .= "<a href='$link'>";

    $imageSize = LinkStyle::getImageSize($style);
    if ($imageSize) {
        $retval .= "<img loading='lazy' src='$image' width='$imageSize' height='$imageSize' alt='' title='$text' class='badgeimg' />";
    }

    if (LinkStyle::hasText($style)) {
        if ($imageSize) {
            $retval .= ' ';
        }

        $textSanitized = $text;
        sanitize_outputs($textSanitized);
        $retval .= $textSanitized;
    }

    $retval .= '</a>';
    $retval .= '</span>';
    return $retval;
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
