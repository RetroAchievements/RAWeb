<?php

use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Platform\Models\PlayerBadge;

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

    $siteAwards = array_values(
        array_filter(
            $userAwards,
            fn ($award) => ($award['AwardType'] != AwardType::Mastery && AwardType::isActive((int) $award['AwardType'])) ||
            in_array($award, $devEventAwards)
        )
    );

    return [$gameAwards, $eventAwards, $siteAwards];
}

function RenderSiteAwards(array $userAwards): void
{
    [$gameAwards, $eventAwards, $siteAwards] = SeparateAwards($userAwards);

    $groups = [];

    $firstVisibleIndex = function (array $awards) use ($userAwards): int {
        foreach ($awards as $award) {
            if ($award['DisplayOrder'] >= 0) {
                return array_search($award, $userAwards);
            }
        }

        return -1;
    };

    if (!empty($gameAwards)) {
        $firstGameAward = $firstVisibleIndex($gameAwards);
        if ($firstGameAward >= 0) {
            $groups[] = [$firstGameAward, $gameAwards, "Game Awards"];
        }
    }

    if (!empty($eventAwards)) {
        $firstEventAward = $firstVisibleIndex($eventAwards);
        if ($firstEventAward >= 0) {
            $groups[] = [$firstEventAward, $eventAwards, "Event Awards"];
        }
    }

    if (!empty($siteAwards)) {
        $firstSiteAward = $firstVisibleIndex($siteAwards);
        if ($firstSiteAward >= 0) {
            $groups[] = [$firstSiteAward, $siteAwards, "Site Awards"];
        }
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
    $numHidden = 0;
    foreach ($awards as $award) {
        if ($award['DisplayOrder'] < 0) {
            $numHidden++;
        }
    }
    if ($numItems == $numHidden) {
        // No items to show
        return;
    }

    $icons = [
        "Game Awards" => "👑🎖️",
        "Event Awards" => "🌱",
        "Site Awards" => "🌐",
    ];
    if ($title == "Game Awards") {
        // Count and show # of completed/mastered games
        [$numCompleted, $numCompletedHidden] = [0, 0];
        foreach ($awards as $award) {
            if ($award['AwardDataExtra'] != 1) {
                $numCompleted++;
                if ($award['DisplayOrder'] < 0) {
                    $numCompletedHidden++;
                }
            }
        }
        $numMastered = $numItems - $numCompleted;
        $numMasteredHidden = $numHidden - $numCompletedHidden;
        $counters = "";
        if ($numMastered > 0) {
            $icon = mb_substr($icons[$title], 0, 1);
            $text = ($numMastered > 1 ? "games" : "game") . " mastered";
            $counters .= RenderCounter($icon, $text, $numMastered, $numMasteredHidden);
        }
        if ($numCompleted > 0) {
            $icon = mb_substr($icons[$title], 1, 1);
            $text = ($numCompleted > 1 ? "games" : "game") . " completed";
            $counters .= RenderCounter($icon, $text, $numCompleted, $numCompletedHidden);
        }
    } else {
        $icon = $icons[$title];
        $text = strtolower($title);
        if ($numItems == 1) {
            // Remove 's'
            $text = mb_substr($text, 0, -1);
        }
        $counters = RenderCounter($icon, $text, $numItems, $numHidden);
    }

    $visibleAwards = array_filter($awards, fn ($award) => $award['DisplayOrder'] >= 0);
    
    // We can be quite certain we'll need to expand the list if the player
    // has more than 120 masteries. We'll also check if the container is
    // overflowing on the client and apply the fade classes if so.
    $shouldAddOptimisticAwardsFade = count($visibleAwards) >= 120;
    $awardsFadeClassName = 'awards-fade';

    $optimisticAwardsFade = $shouldAddOptimisticAwardsFade ? $awardsFadeClassName : '';
    $initialAwardsButtonExpandClass = $shouldAddOptimisticAwardsFade ? '' : 'hidden';

    $awardsContainerId = $title . '-container';
    $awardsExpandButtonId = $title . '-expand-button';

    echo "<div class='awards-group'>";
    echo "<h3 class='flex justify-between gap-2'><span class='grow'>$title</span>$counters</h3>";
    echo "<div id='$awardsContainerId' class='component $optimisticAwardsFade' onscroll='handleAwardsScroll(event, \"$awardsExpandButtonId\")' x-init='shouldApplyAwardsGroupFade(\"$awardsContainerId\", \"$awardsExpandButtonId\", \"$awardsFadeClassName\")'>";

    $imageSize = 48;
    foreach ($visibleAwards as $award) {
        RenderAward($award, $imageSize);
    }

    echo "</div>";

    echo "<button id='$awardsExpandButtonId' class='awards-expand-button $initialAwardsButtonExpandClass' onclick='showFullAwards(event)'>Expand (" . count($visibleAwards) . ")</button>";

    echo "</div>";
}

function RenderCounter($icon, $text, $numItems, $numHidden): string
{
    $tooltip = "$numItems $text";
    if ($numHidden > 0) {
        $tooltip .= " ($numHidden hidden)";
    }
    $counter =
        "<div class='awardcounter' title='$tooltip'>
            <div class='icon'>$icon</div><div class='numitems'>$numItems</div>
        </div>";

    return $counter;
}

function RenderAward($award, $imageSize, $clickable = true): void
{
    $awardType = $award['AwardType'];
    settype($awardType, 'integer');
    $awardData = $award['AwardData'];
    $awardDataExtra = $award['AwardDataExtra'];
    $awardDate = getNiceDate($award['AwardedAt']);
    $awardButGameIsIncomplete = (isset($award['Incomplete']) && $award['Incomplete'] == 1);
    $imgclass = 'badgeimg siteawards';

    if ($awardType == AwardType::Mastery) {
        if ($awardDataExtra == '1') {
            $awarded = "Mastered on $awardDate";
            $imgclass = 'goldimage';
        } else {
            $awarded = "Completed on $awardDate";
        }
        if ($awardButGameIsIncomplete) {
            $awarded .= "...<br>but more achievements have been added!<br>Click here to find out what you're missing!";
        }
        $award['GameID'] = $award['AwardData'];
        $award['Mastery'] = "<br clear=all>$awarded";

        echo "<div>" . gameAvatar($award, label: false, iconSize: $imageSize, context: 'mastery', iconClass: $imgclass) . "</div>";

        return;
    } elseif ($awardType == AwardType::AchievementUnlocksYield) {
        // Developed a number of earned achievements
        $tooltip = "Awarded for being a hard-working developer and producing achievements that have been earned over " . PlayerBadge::getBadgeThreshold($awardType, $awardData) . " times!";
        $imagepath = asset("/assets/images/badge/contribYield-$awardData.png");
        $imgclass = 'goldimage';
        $linkdest = ''; // TBD: developer sets page?
    } elseif ($awardType == AwardType::AchievementPointsYield) {
        // Yielded an amount of points earned by players
        $tooltip = "Awarded for producing many valuable achievements, providing over " . PlayerBadge::getBadgeThreshold($awardType, $awardData) . " points to the community!";
        $imagepath = asset("/assets/images/badge/contribPoints-$awardData.png");
        $imgclass = 'goldimage';
        $linkdest = ''; // TBD: developer sets page?
    // } elseif ($awardType == AwardType::Referrals) {
    //     $tooltip = "Referred $awardData members";
    //     $imagepath = "/Badge/00083.png";
    //     $linkdest = ''; // TBD: referrals page?
    } elseif ($awardType == AwardType::PatreonSupporter) {
        $tooltip = 'Awarded for being a Patreon supporter! Thank-you so much for your support!';
        $imagepath = asset('/assets/images/badge/patreon.png');
        $imgclass = 'goldimage';
        $linkdest = 'https://www.patreon.com/retroachievements';
    } elseif ($awardType == AwardType::CertifiedLegend) {
        $tooltip = 'Specially Awarded to a Certified RetroAchievements Legend';
        $imagepath = asset('/assets/images/badge/legend.png');
        $imgclass = 'goldimage';
        $linkdest = '';
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
