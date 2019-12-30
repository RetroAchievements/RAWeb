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
    $tooltip .= "<img src='" . getenv('APP_STATIC_URL') . "/Badge/$badgeName" . ".png' width='$tooltipIconSize' height='$tooltipIconSize' />";
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
        $smallBadgePath = "/Badge/$badgeName" . ".png";
        $smallBadge = "<img width='$smallBadgeSize' height='$smallBadgeSize' src=\"" . getenv('APP_STATIC_URL') . "$smallBadgePath\" alt='$achNameStr' title='$achNameStr' class='$imgclass' />";

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
