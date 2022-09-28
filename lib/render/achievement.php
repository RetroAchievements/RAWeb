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
    sanitize_outputs(
        $achName,
        $consoleName,
        $gameName,
        $achPoints
    );

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

    return "<div class='inline' onmouseover=\"Tip(loadCard('achievement', $achID))\" onmouseout=\"UnTip()\" >" .
        "<a href='/achievement/$achID'>" .
        "$smallBadge" .
        "$displayable" .
        "</a>" .
        "</div>";
}

function renderAchievementCard(int $achievementId): string
{
    $achData = [];
    getAchievementMetadata($achievementId, $achData);

    $badgeName = $achData['BadgeName'];
    $achNameStr = $achData['AchievementTitle'];
    $achDescStr = $achData['Description'];
    $gameNameStr = $achData['GameTitle'];
    $achPoints = $achData['Points'];

    $tooltip = "<div class='tooltip-body flex items-start gap-2 p-2' style='max-width: 400px'>";
    $tooltip .= "<img src='" . media_asset("Badge/$badgeName.png") . "' width='64' height='64' />";
    $tooltip .= "<div>";
    $tooltip .= "<div><b>$achNameStr</b></div>";
    $tooltip .= "<div class='mb-1'>$achDescStr</div>";
    if ($achPoints) {
        $tooltip .= "<div>$achPoints Points</div>";
    }
    $tooltip .= "<div><i>$gameNameStr</i></div>";

    // TODO: extra text should tell if user has unlocked the achievement; use request()->user() for that
    // $tooltip .= $extraText;

    $tooltip .= "</div>";
    $tooltip .= "</div>";

    return $tooltip;
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
            echo "<td><div>";
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
