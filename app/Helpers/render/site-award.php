<?php

use App\Community\Enums\AwardType;
use App\Platform\Models\PlayerBadge;

function SeparateAwards(array $userAwards): array
{
    $gameAwards = array_values(array_filter($userAwards, fn ($award) => $award['AwardType'] == AwardType::Mastery && $award['ConsoleName'] != 'Events'));

    $eventAwards = array_filter($userAwards, fn ($award) => $award['AwardType'] == AwardType::Mastery && $award['ConsoleName'] == 'Events');

    $devEventsPrefix = "[Dev Events - ";
    $devEventsHub = "[Central - Developer Events]";
    $devEventAwards = [];
    foreach ($eventAwards as $eventAward) {
        $related = getGameAlternatives($eventAward['AwardData']);
        foreach ($related as $hub) {
            if ($hub['Title'] == $devEventsHub || str_starts_with($hub['Title'], $devEventsPrefix)) {
                $devEventAwards[] = $eventAward;
                break;
            }
        }
    }

    $eventAwards = array_values(array_filter($eventAwards, fn ($award) => !in_array($award, $devEventAwards)));

    $filterSiteAwards = function ($userAward) use ($devEventAwards) {
        $isNotMasteryOrGameBeaten = $userAward['AwardType'] != AwardType::Mastery && $userAward['AwardType'] != AwardType::GameBeaten;
        $isActiveAwardType = AwardType::isActive((int) $userAward['AwardType']);
        $isDevEventAward = in_array($userAward, $devEventAwards);

        return ($isNotMasteryOrGameBeaten && $isActiveAwardType) || $isDevEventAward;
    };
    $siteAwards = array_values(array_filter($userAwards, $filterSiteAwards));

    return [$gameAwards, $eventAwards, $siteAwards];
}

function RenderSiteAwards(array $userAwards, string $awardsOwnerUsername): void
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
        RenderAwardGroup($group[1], $group[2], $awardsOwnerUsername);
    }
}

function RenderAwardGroup(array $awards, string $title, string $awardsOwnerUsername): void
{
    $numItems = count($awards);
    $numHidden = 0;
    foreach ($awards as $award) {
        if ($award['DisplayOrder'] < 0) {
            $numHidden++;
        }
    }
    if ($numItems === $numHidden) {
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
        $numCompleted = 0;
        $numCompletedHidden = 0;
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

    echo "<div id='" . strtolower(str_replace(' ', '', $title)) . "'>";
    echo "<h3 class='flex justify-between gap-2'><span class='grow'>$title</span>$counters</h3>";
    echo "<div class='flex justify-center'>";
    echo "<div class='component flex flex-wrap gap-2 justify-start bg-embed w-full xl:rounded xl:py-2 xl:px-5'>";
    $imageSize = 48;
    foreach ($awards as $award) {
        if ($award['DisplayOrder'] >= 0) {
            RenderAward($award, $imageSize, $awardsOwnerUsername);
        }
    }
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

function RenderCounter(string $icon, string $text, int $numItems, int $numHidden): string
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

function RenderAward(array $award, int $imageSize, string $ownerUsername, bool $clickable = true): void
{
    $awardType = $award['AwardType'];
    $awardType = (int) $awardType;
    $awardData = $award['AwardData'];
    $awardDataExtra = $award['AwardDataExtra'];
    $awardGameTitle = $award['Title'];
    $awardGameConsole = $award['ConsoleName'];
    $awardGameImage = $award['ImageIcon'];
    $awardDate = getNiceDate((int) $award['AwardedAt']);
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

        $dataAttrGameId = $award['GameID'];
        // NOTE: If these data-* attributes are removed, userscripts will begin breaking.
        echo "<div data-gameid='$dataAttrGameId' data-date='$awardDate'>" . gameAvatar($award, label: false, iconSize: $imageSize, context: $ownerUsername, iconClass: $imgclass) . "</div>";

        return;
    }

    if ($awardType == AwardType::AchievementUnlocksYield) {
        // Developed a number of earned achievements
        $tooltip = "Awarded for being a hard-working developer and producing achievements that have been earned over " . PlayerBadge::getBadgeThreshold($awardType, $awardData) . " times!";
        $imagepath = asset("/assets/images/badge/contribYield-$awardData.png");
        $imgclass = 'goldimage';
        $linkdest = '';
        // TBD: developer sets page?
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
    }

    echo "<div><div>$displayable</div>$newOverlayDiv</div>";
}
