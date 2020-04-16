<?php
/**
 * @param $achID
 * @param $achName
 * @param $achDesc
 * @param $achPoints
 * @param $gameName
 * @param $badgeName
 * @param bool $inclSmallBadge
 * @param bool $smallBadgeOnly
 * @param string $extraText
 * @param int $smallBadgeSize
 * @param string $imgclass
 * @return string
 */
function GetAchievementAndTooltipDiv(
    $achID,
    $achName,
    $achDesc,
    $achPoints,
    $gameName,
    $badgeName,
    $inclSmallBadge = false,
    $smallBadgeOnly = false,
    $extraText = '',
    $smallBadgeSize = 32,
    $imgclass = 'badgeimg'
) {
    $tooltipIconSize = 64; //96;

    $achNameStr = $achName;
    $achDescStr = $achDesc;
    $gameNameStr = $gameName;

    $tooltip = "<div id='objtooltip'>";
    $tooltip .= "<img src='" . getenv('ASSET_URL') . "/Badge/$badgeName" . ".png' width='$tooltipIconSize' height='$tooltipIconSize' />";
    $tooltip .= "<b>$achNameStr";
    if ($achPoints) {
        $tooltip .= " ($achPoints)";
    }
    $tooltip .= "</b><br>";
    $tooltip .= "<i>($gameNameStr)</i><br>";
    $tooltip .= "<br>";
    $tooltip .= "$achDescStr<br>";
    $tooltip .= "$extraText";
    $tooltip .= "</div>";

    $tooltip = str_replace("'", "\'", $tooltip);
    $tooltip = htmlentities($tooltip);

    $smallBadge = '';
    $displayable = "$achName";
    if ($achPoints) {
        $displayable .= " ($achPoints)";
    }

    if ($inclSmallBadge) {
        $achNameAttr = htmlspecialchars($achName, ENT_QUOTES);
        $smallBadgePath = "/Badge/$badgeName" . ".png";
        $smallBadge = "<img width='$smallBadgeSize' height='$smallBadgeSize' src=\"" . getenv('ASSET_URL') . "$smallBadgePath\" alt='$achNameAttr' title='$achNameAttr' class='$imgclass' />";

        if ($smallBadgeOnly) {
            $displayable = "";
        }
    }

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='/Achievement/$achID'>" .
        "$smallBadge" .
        "$displayable" .
        "</a>" .
        "</div>";
}

function RenderRecentlyUploadedComponent($numToFetch)
{
    echo "<div class='component'>";
    echo "<h3>New Achievements</h3>";

    $numFetched = getLatestNewAchievements($numToFetch, $dataOut);
    if ($numFetched > 0) {
        echo "<table class='sidebar'><tbody>";
        echo "<tr><th>Achievement</th><th>Game</th><th>Added</th></tr>";

        foreach ($dataOut as $nextData) {
            $timestamp = strtotime($nextData['DateCreated']);
            $dateAwarded = date("d M", $timestamp);

            if (date("d", $timestamp) == date("d")) {
                $dateAwarded = "Today";
            } elseif (date("d", $timestamp) == (date("d") - 1)) {
                $dateAwarded = "Y'day";
            }

            $uploadedAt = date("H:i", $timestamp);
            $achID = $nextData['ID'];
            $achTitle = $nextData['Title'];
            $achDesc = $nextData['Description'];
            $achPoints = $nextData['Points'];
            $gameID = $nextData['GameID'];
            $gameTitle = $nextData['GameTitle'];
            $gameIcon = $nextData['GameIcon'];
            $achBadgeName = $nextData['BadgeName'];
            $consoleName = $nextData['ConsoleName'];

            echo "<tr>";
            echo "<td>";
            echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, true);
            echo "</td>";
            echo "<td><div class='fixheightcell'>";
            echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName);
            echo "<td class='smalldate'>$dateAwarded $uploadedAt</td>";
            echo "</div></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "<br>";
        echo "<div class='morebutton'><a href='/achievementList.php?s=17'>more...</a></div>";
    }
    echo "</div>";
}
