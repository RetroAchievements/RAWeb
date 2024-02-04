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

    $siteAwards = array_values(array_filter($userAwards, function ($userAward) use ($devEventAwards) {
        $isNotMasteryOrGameBeaten = !AwardType::isGame((int) $userAward['AwardType']);
        $isActiveAwardType = AwardType::isActive((int) $userAward['AwardType']);
        $isDevEventAward = in_array($userAward, $devEventAwards);

        return ($isNotMasteryOrGameBeaten && $isActiveAwardType) || $isDevEventAward;
    }));

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
    echo "<div class='component w-full place-content-center bg-embed gap-2 grid grid-cols-[repeat(auto-fill,minmax(52px,52px))] xl:rounded xl:py-2'>";
    $imageSize = 48;
    foreach ($awards as $award) {
        if ($award['DisplayOrder'] >= 0) {
            RenderAward($award, $imageSize, $awardsOwnerUsername);
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

function RenderAwardOrderTable(
    string $title,
    array $awards,
    string $awardOwnerUsername,
    int &$awardCounter,
    int $renderedSectionCount,
    bool $prefersSeeingSavedHiddenRows,
    int $initialSectionOrder,
): void {
    // "Game Awards" -> "game"
    $humanReadableAwardKind = strtolower(strtok($title, " "));

    echo "<div class='flex w-full items-center justify-between'>";
    echo "<h4>$title</h4>";
    echo "<select data-award-kind='$humanReadableAwardKind'>";
    for ($i = 1; $i <= $renderedSectionCount; $i++) {
        if ($initialSectionOrder === $i) {
            echo "<option value='$i' selected>$i</option>";
        } else {
            echo "<option value='$i'>$i</option>";
        }
    }
    echo "</select>";
    echo "</div>";

    echo "<table id='$humanReadableAwardKind-reorder-table' class='mb-8'>";

    echo "<thead>";
    echo "<tr class='do-not-highlight'>";
    echo "<th>Badge</th>";
    echo "<th width=\"60%\">Site Award</th>";
    echo "<th class='text-center'>Hidden</th>";
    echo "<th class='text-right' width=\"20%\">Manual Move</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($awards as $award) {
        $awardType = $award['AwardType'];
        $awardData = $award['AwardData'];
        $awardDataExtra = $award['AwardDataExtra'];
        $awardTitle = $award['Title'];
        $awardDisplayOrder = $award['DisplayOrder'];

        sanitize_outputs(
            $awardTitle,
            $awardType,
            $awardData,
            $awardDataExtra,
        );

        if ($awardType == AwardType::Mastery) {
            $awardTitle = Blade::render('<x-game-title :rawTitle="$rawTitle" />', ['rawTitle' => $awardTitle]);
        } elseif ($awardType == AwardType::AchievementUnlocksYield) {
            $awardTitle = "Achievements Earned by Others";
        } elseif ($awardType == AwardType::AchievementPointsYield) {
            $awardTitle = "Achievement Points Earned by Others";
        } elseif ($awardType == AwardType::PatreonSupporter) {
            $awardTitle = "Patreon Supporter";
        } elseif ($awardType == AwardType::CertifiedLegend) {
            $awardTitle = "Certified Legend";
        }

        $isHiddenPreChecked = $awardDisplayOrder === -1;
        $subduedOpacityClassName = $isHiddenPreChecked ? 'opacity-40' : '';
        $isDraggable = $isHiddenPreChecked ? 'false' : 'true';

        $cursorGrabClass = $isHiddenPreChecked ? '' : 'cursor-grab';
        $savedHiddenClass = $isHiddenPreChecked ? 'saved-hidden' : '';
        $hiddenClass = (!$prefersSeeingSavedHiddenRows && $isHiddenPreChecked) ? 'hidden' : '';

        $rowClassNames = "award-table-row select-none transition {$cursorGrabClass} {$savedHiddenClass} {$hiddenClass}";

        echo <<<HTML
            <tr
                data-row-index='$awardCounter'
                data-award-kind='$humanReadableAwardKind'
                draggable='$isDraggable'
                class='$rowClassNames'
                ondragstart='reorderSiteAwards.handleRowDragStart(event)'
                ondragenter='reorderSiteAwards.handleRowDragEnter(event)'
                ondragleave='reorderSiteAwards.handleRowDragLeave(event)'
                ondragover='reorderSiteAwards.handleRowDragOver(event)'
                ondragend='reorderSiteAwards.handleRowDragEnd(event)'
                ondrop='reorderSiteAwards.handleRowDrop(event)'
            >
        HTML;

        echo "<td class='$subduedOpacityClassName transition'>";
        RenderAward($award, 32, $awardOwnerUsername, false);
        echo "</td>";
        echo "<td class='$subduedOpacityClassName transition'><span>$awardTitle</span></td>";
        echo "<td class='text-center !opacity-100'><input name='$awardCounter-is-hidden' onchange='reorderSiteAwards.handleRowHiddenCheckedChange(event, $awardCounter)' type='checkbox' " . ($isHiddenPreChecked ? "checked" : "") . "></td>";

        echo "<td>";
        echo "<div class='award-movement-buttons flex justify-end transition " . ($isHiddenPreChecked ? 'opacity-0' : 'opacity-100') . "'>";
        if (count($awards) > 50) {
            echo generateManualMoveButtons($awardCounter, 99999, upLabel: ' Top', downLabel: ' Bottom', autoScroll: true, isHiddenPreChecked: $isHiddenPreChecked);
            echo generateManualMoveButtons($awardCounter, 50, upLabel: '50', downLabel: '50', autoScroll: true, isHiddenPreChecked: $isHiddenPreChecked);
            echo generateManualMoveButtons($awardCounter, 1, isHiddenPreChecked: $isHiddenPreChecked);
        } elseif (count($awards) > 15) {
            echo generateManualMoveButtons($awardCounter, 10, upLabel: '10', downLabel: '10', autoScroll: true, isHiddenPreChecked: $isHiddenPreChecked);
            echo generateManualMoveButtons($awardCounter, 1, isHiddenPreChecked: $isHiddenPreChecked);
        } else {
            echo generateManualMoveButtons($awardCounter, 1, orientation: 'horizontal', isHiddenPreChecked: $isHiddenPreChecked);
        }
        echo "</div>";
        echo "</td>";

        echo "<input type='hidden' name='type' value='$awardType'>";
        echo "<input type='hidden' name='data' value='$awardData'>";
        echo "<input type='hidden' name='extra' value='$awardDataExtra'>";

        echo "</tr>\n";
        $awardCounter++;
    }
    echo "</tbody></table>";
}

function getInitialSectionOrders(array $gameAwards, array $eventAwards, array $siteAwards): array
{
    $awardsArrays = [
        'gameAwards' => $gameAwards,
        'eventAwards' => $eventAwards,
        'siteAwards' => $siteAwards,
    ];

    $firstDisplayOrders = [];

    foreach ($awardsArrays as $key => $awardsArray) {
        $firstDisplayOrder = null;

        foreach ($awardsArray as $award) {
            if ($award['DisplayOrder'] >= 0) {
                $firstDisplayOrder = $award['DisplayOrder'];
                break;
            }
        }

        $firstDisplayOrders[$key] = $firstDisplayOrder;
    }

    asort($firstDisplayOrders); // Sort the array while maintaining the key association

    // Assign unique order values
    $order = 1;
    foreach ($firstDisplayOrders as $key => $value) {
        if ($value !== null) {
            $firstDisplayOrders[$key] = $order++;
        }
    }

    // Replace null values with unique incrementing values, unless the array is empty
    foreach ($firstDisplayOrders as $key => $value) {
        if ($value === null && count($awardsArrays[$key]) > 0) {
            $firstDisplayOrders[$key] = $order++;
        }
    }

    return [
        $firstDisplayOrders['gameAwards'],
        $firstDisplayOrders['eventAwards'],
        $firstDisplayOrders['siteAwards'],
    ];
}

function generateManualMoveButtons(
    int $awardCounter,
    int $moveValue,
    string $upLabel = '',
    string $downLabel = '',
    bool $autoScroll = false,
    string $orientation = 'vertical',
    bool $isHiddenPreChecked = false,
): string {
    $downValue = $moveValue;
    $upValue = $moveValue * -1;

    $containerClassNames = $orientation === 'vertical' ? 'flex flex-col' : 'flex';

    $rowsPlural = $moveValue === 1 ? "row" : "rows";
    $upA11yLabel = "Move up $moveValue $rowsPlural";
    $downA11yLabel = "Move down $moveValue $rowsPlural";

    if ($moveValue > 10000) {
        $upA11yLabel = "Move to top";
        $downA11yLabel = "Move to bottom";
    }

    $disabledAttribute = $isHiddenPreChecked ? "disabled" : "";

    return <<<HTML
        <div class="$containerClassNames">
            <button
                title="$upA11yLabel"
                aria-label="$upA11yLabel"
                class="btn text-2xs py-0.5"
                onclick="reorderSiteAwards.moveRow($awardCounter, $upValue, $autoScroll)"
                $disabledAttribute
            >
                ↑$upLabel
            </button>

            <button
                title="$downA11yLabel"
                aria-label="$downA11yLabel"
                class="btn text-2xs py-0.5"
                onclick="reorderSiteAwards.moveRow($awardCounter, $downValue, $autoScroll)"
                $disabledAttribute
            >
                ↓$downLabel
            </button>
        </div>
    HTML;
}
