<?php

use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Platform\Models\PlayerBadge;

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

    $siteAwards = array_values(array_filter($userAwards, fn ($award) => ($award['AwardType'] != AwardType::Mastery && AwardType::isActive((int) $award['AwardType'])) ||
        in_array($award, $devEventAwards)
    ));

    return [$gameAwards, $eventAwards, $siteAwards];
}

function findGameInUserCompletedGames(array $userCompletedGamesList, int $gameId): ?array
{
    $index = array_search($gameId, array_column($userCompletedGamesList, 'GameID'));

    return $index !== false ? $userCompletedGamesList[$index] : null;
}

function markIncompleteAwards(array $awards, array $userCompletedGamesList): array
{
    foreach ($awards as &$award) {
        $award['Incomplete'] = 0;
        $awardGameId = $award['AwardData'];

        $foundGameInCompletedGamesList = findGameInUserCompletedGames($userCompletedGamesList, $awardGameId);
        if ($foundGameInCompletedGamesList) {
            $isMasteryAward = $award['AwardDataExtra'] == 1;

            $pctWon = $isMasteryAward ? $foundGameInCompletedGamesList['PctWonHC'] : $foundGameInCompletedGamesList['PctWon'];
            if ($pctWon < 1.0) {
                $award['Incomplete'] = 1;
            }
        }
    }

    return $awards;
}

function RenderSiteAwards(array $userAwards, ?array $userCompletedGamesList = null): void
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
            if (isset($userCompletedGamesList)) {
                $gameAwards = markIncompleteAwards($gameAwards, $userCompletedGamesList);
            }
            
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

function RenderAwardGroup(array $awards, string $title): void
{
    $numItems = count($awards);
    $numHidden = count(array_filter($awards, function ($award) { return $award['DisplayOrder'] < 0; }));
    $iconCount = 0;

    if ($numItems === $numHidden) {
        // No items to show
        return;
    }

    $icons = [
        "Game Awards" => "👑🎖️💡",
        "Event Awards" => "🌱",
        "Site Awards" => "🌐",
    ];

    $counters = "";

    if ($title == "Game Awards") {
        $awardKindCounts = array_reduce($awards, function ($carry, $award) {
            $carry['numCompleted'] += ($award['AwardDataExtra'] != 1);
            $carry['numCompletedHidden'] += ($award['AwardDataExtra'] != 1 && $award['DisplayOrder'] < 0);
            $carry['numOutdated'] += (isset($award['Incomplete']) && $award['Incomplete'] == 1);
            $carry['numOutdatedHidden'] += (isset($award['Incomplete']) && $award['Incomplete'] == 1 && $award['DisplayOrder'] < 0);

            return $carry;
        }, ['numCompleted' => 0, 'numCompletedHidden' => 0, 'numOutdated' => 0, 'numOutdatedHidden' => 0]);

        $numCompleted = $awardKindCounts['numCompleted'];
        $numCompletedHidden = $awardKindCounts['numCompletedHidden'];
        $numOutdated = $awardKindCounts['numOutdated'];
        $numOutdatedHidden = $awardKindCounts['numOutdatedHidden'];
        $numMastered = $numItems - $numCompleted;
        $numMasteredHidden = $numHidden - $numCompletedHidden;

        $counterData = [
            ["text" => "games mastered", "count" => $numMastered, "hiddenCount" => $numMasteredHidden],
            ["text" => "games completed", "count" => $numCompleted, "hiddenCount" => $numCompletedHidden],
            ["text" => "games with new achievements", "count" => $numOutdated, "hiddenCount" => $numOutdatedHidden],
        ];

        foreach ($counterData as $index => $data) {
            if ($data['count'] > 0) {
                $iconCount++;
                // Use the correct position for the emoji within the string, accounting for the light bulb emoji's 2 character length.
                $iconOffset = $index == 2 ? 1 : 0;
                $icon = mb_substr($icons[$title], $index + $iconOffset, 1);
                $text = ($data['count'] > 1 ? $data['text'] : str_replace("games", "game", $data['text']));
                $counters .= RenderCounter($icon, $text, $data['count'], $data['hiddenCount']);
            }
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

    // Now that all the necessary values for the award group have been calculated,
    // we can finally render the award group.
    $awardGroupId = strtolower(str_replace(' ', '', $title));
    $fontSizeStyle = $iconCount > 2 ? "style='font-size: 0.8em'" : "";

    echo "<div id={$awardGroupId}'>";
    echo "<h3 class='flex justify-between gap-2'><span class='grow' {$fontSizeStyle}>{$title}</span>{$counters}</h3>";
    echo "<div class='component flex flex-wrap justify-start gap-2'>";
    $imageSize = 48;
    foreach ($awards as $award) {
        if ($award['DisplayOrder'] >= 0) {
            RenderAward($award, $imageSize);
        }
    }
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

function RenderAward(array $award, int $imageSize, bool $clickable = true): void
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

    $awarded = $awardDataExtra == '1' ? "Mastered on $awardDate" : "Completed on $awardDate";

    if ($awardType == AwardType::Mastery) {
        if ($awardDataExtra == '1') {
            $imgclass = 'goldimage' . ($awardButGameIsIncomplete ? ' border-dashed' : '');
        } elseif ($awardButGameIsIncomplete) {
            // TODO: Add a Tailwind palette variable for #0B71C1. It is the global "softcore" color.
            $imgclass = 'badgeimg !border-[#0B71C1] !border-dashed';
        }

        if ($awardButGameIsIncomplete) {
            $awarded .= ",<br>but more achievements have been added!";
        }

        $award['GameID'] = $award['AwardData'];
        $award['Mastery'] = "<br clear=all>$awarded";

        echo "<div>" . gameAvatar($award, label: false, iconSize: $imageSize, context: 'mastery', iconClass: $imgclass) . "</div>";

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
