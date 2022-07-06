<?php

use RA\AwardThreshold;
use RA\AwardType;

function SeparateAwards($userAwards): array
{
    $gameAwards = array_values(array_filter($userAwards, fn ($award) => $award['AwardType'] == AwardType::Mastery && $award['ConsoleName'] != 'Events'));

    $eventAwards = array_filter($userAwards, fn ($award) => $award['AwardType'] == AwardType::Mastery && $award['ConsoleName'] == 'Events');

    $devEventsPrefix = "[Dev Events - ";
    $devEventsHub = "[Central - Developer Events]";
    $devEventAwards = [];
    foreach ($eventAwards as $k => $eventAward) {
        $related = getGameAlternatives($eventAward['AwardData']);
        foreach ($related as $hub) {
            if ($hub['Title'] == $devEventsHub || str_starts_with($hub['Title'], $devEventsPrefix)) {
                $devEventAwards[] = $eventAward;
                break;
            }
        }
    }

    $eventAwards = array_values(array_filter($eventAwards, fn ($award) => !in_array($award, $devEventAwards)));

    $siteAwards = array_values(array_filter($userAwards, fn ($award) => ($award['AwardType'] != AwardType::Mastery && AwardType::isActive((int) $award['AwardType'])) ||
        in_array($award, $devEventAwards)
    ));

    return [$gameAwards, $eventAwards, $siteAwards];
}

function RenderSiteAwards($userAwards): void
{
    [$gameAwards, $eventAwards, $siteAwards] = SeparateAwards($userAwards);

    $groups = [];

    if (!empty($gameAwards)) {
        $firstGameAward = array_search($gameAwards[0], $userAwards);
        $groups[] = [$firstGameAward, $gameAwards, "Game Awards"];
    }

    if (!empty($eventAwards)) {
        $firstEventAward = array_search($eventAwards[0], $userAwards);
        $groups[] = [$firstEventAward, $eventAwards, "Event Awards"];
    }

    if (!empty($siteAwards)) {
        $firstSiteAward = array_search($siteAwards[0], $userAwards);
        $groups[] = [$firstSiteAward, $siteAwards, "Site Awards"];
    }

    if (empty($groups)) {
        $groups[] = [0, $gameAwards, "Game Awards"];
    }

    usort($groups, fn ($a, $b) => $a[0] - $b[0]);

    foreach ($groups as $group) {
        RenderAwardGroup($group[1], $group[2]);
    }
}

function RenderAwardGroup($awards, $title): void
{
    $numItems = is_countable($awards) ? count($awards) : 0;
    if ($numItems == 0) {
        return;
    }

    echo "<div id='" . strtolower(str_replace(' ', '', $title)) . "' class='component' >";
    echo "<h3 class='longheader'>$title</h3>";
    echo "<div class='siteawards'>";
    echo "<table class='siteawards'><tbody>";

    $imageSize = 48;
    $numCols = 5;
    for ($i = 0; $i < ceil($numItems / $numCols); $i++) {
        echo "<tr>";
        for ($j = 0; $j < $numCols; $j++) {
            $nOffs = ($i * $numCols) + $j;
            if ($nOffs >= $numItems) {
                echo "<td><div class='badgeimg'><div style='width:{$imageSize}px' /></div></td>";
                continue;
            }

            echo "<td>";
            RenderAward($awards[$nOffs], $imageSize);
            echo "</td>";
        }

        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "</div>";

    echo "</div>";
}

function RenderAward($award, $imageSize, $clickable = true): void
{
    $awardType = $award['AwardType'];
    settype($awardType, 'integer');
    $awardData = $award['AwardData'];
    $awardDataExtra = $award['AwardDataExtra'];
    $awardGameTitle = $award['Title'];
    $awardGameConsole = $award['ConsoleName'];
    $awardGameImage = $award['ImageIcon'];
    $awardDate = getNiceDate($award['AwardedAt']);
    $awardButGameIsIncomplete = (isset($award['Incomplete']) && $award['Incomplete'] == 1);
    $imgclass = 'badgeimg siteawards';

    if ($awardType == AwardType::Mastery) {
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
    } elseif ($awardType == AwardType::AchievementUnlocksYield) {
        // Developed a number of earned achievements
        $tooltip = "Awarded for being a hard-working developer and producing achievements that have been earned over " . AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$awardData] . " times!";
        $imagepath = "/Images/_Trophy" . AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$awardData] . ".png";
        $linkdest = ''; // TBD: referrals page?
    } elseif ($awardType == AwardType::AchievementPointsYield) {
        // Yielded an amount of points earned by players
        $tooltip = "Awarded for producing many valuable achievements, providing over " . AwardThreshold::DEVELOPER_POINT_BOUNDARIES[$awardData] . " points to the community!";
        if ($awardData == 0) {
            $imagepath = "/Images/trophy-green.png";
        } elseif ($awardData == 1) {
            $imagepath = "/Images/trophy-bronze.png";
        } elseif ($awardData == 2) {
            $imagepath = "/Images/trophy-platinum.png";
        } elseif ($awardData == 3) {
            $imagepath = "/Images/trophy-silver.png";
        } elseif ($awardData == 4) {
            $imagepath = "/Images/trophy-gold.png";
        } else {
            $imagepath = "/Images/trophy-gold.png";
        }
        $linkdest = ''; // TBD: referrals page?
    } elseif ($awardType == AwardType::Referrals) {
        $tooltip = "Referred $awardData members";
        $imagepath = "/Badge/00083.png";
        $linkdest = ''; // TBD: referrals page?
    } elseif ($awardType == AwardType::PatreonSupporter) {
        $tooltip = 'Awarded for being a Patreon supporter! Thank-you so much for your support!';
        $imagepath = '/Images/PatreonBadge.png';
        $linkdest = 'https://www.patreon.com/retroachievements';
    } else {
        // Unknown or inactive award type
        return;
    }

    $tooltip .= "\r\nAwarded on $awardDate";
    $tooltip = attributeEscape($tooltip);

    $displayable = "<img class=\"$imgclass\" alt=\"$tooltip\" title=\"$tooltip\" src=\"$imagepath\" width=\"$imageSize\" height=\"$imageSize\" />";
    $newOverlayDiv = '';

    if ($clickable && !empty($linkdest)) {
        $displayable = "<a href=\"$linkdest\">$displayable</a>";
        $tooltipImagePath = "$imagepath";
        $tooltipImageSize = 96;
        $tooltipTitle = "Site Award";

        // $textWithTooltip = WrapWithTooltip($displayable, $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);

        // if ($awardButGameIsIncomplete) {
        //     $newOverlayDiv = WrapWithTooltip("<a href=\"$linkdest\"><div class=\"trophyimageincomplete\"></div></a>", $tooltipImagePath, $tooltipImageSize, $tooltipTitle, $tooltip);
        // }
    }

    echo "<div><div>$displayable</div>$newOverlayDiv</div>";
}
