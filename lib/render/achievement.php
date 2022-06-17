<?php

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
): string {
    $tooltipIconSize = 64; // 96;

    sanitize_outputs(
        $achName,
        $consoleName,
        $gameName,
        $achPoints
    );

    $achNameStr = $achName;
    $achDescStr = $achDesc;
    $gameNameStr = $gameName;

    $tooltip = "<div id='objtooltip' style='display:flex;max-width:400px'>";
    $tooltip .= "<img style='margin-right:5px' src='" . media_asset("Badge/$badgeName.png") . "' width='$tooltipIconSize' height='$tooltipIconSize' />";
    $tooltip .= "<div>";
    $tooltip .= "<b>$achNameStr</b><br>";
    $tooltip .= "$achDescStr<br>";
    if ($achPoints) {
        $tooltip .= "<br>$achPoints Points<br>";
    }
    $tooltip .= "<i>$gameNameStr</i><br>";
    $tooltip .= $extraText;
    $tooltip .= "</div>";
    $tooltip .= "</div>";

    $tooltip = tipEscape($tooltip);

    $smallBadge = '';
    $displayable = "$achName";
    if ($achPoints) {
        $displayable .= " ($achPoints)";
    }

    if ($inclSmallBadge) {
        $achNameAttr = attributeEscape($achName);
        $smallBadgePath = "/Badge/$badgeName" . ".png";
        $smallBadge = "<img loading='lazy' width='$smallBadgeSize' height='$smallBadgeSize' src='" . media_asset($smallBadgePath) . "' alt='$achNameAttr' title='$achNameAttr' class='$imgclass'>";

        if ($smallBadgeOnly) {
            $displayable = "";
        } else {
            $smallBadge .= ' ';
        }
    }

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='/achievement/$achID'>" .
        "$smallBadge" .
        "$displayable" .
        "</a>" .
        "</div>";
}

function RenderRecentlyUploadedComponent($numToFetch): void
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

            sanitize_outputs($achTitle, $achDesc, $gameTitle, $consoleName);

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
        echo "<div class='text-right'><a class='btn btn-link' href='/achievementList.php?s=17'>more...</a></div>";
    }
    echo "</div>";
}
