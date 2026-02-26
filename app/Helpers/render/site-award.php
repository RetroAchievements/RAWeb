<?php

use App\Community\Enums\AwardType;
use App\Models\Event;
use App\Models\EventAward;
use App\Models\PlayerBadge;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

function SeparateAwards(array $userAwards): array
{
    $awardEventGameIds = [];
    $awardEventIds = [];
    foreach ($userAwards as $award) {
        $type = (int) $award['AwardType'];
        if ($type === AwardType::Event->toLegacyInteger()) {
            $awardEventIds[] = (int) $award['AwardData'];
        } elseif (AwardType::isGame($type) && $award['ConsoleName'] === 'Events') {
            $awardEventGameIds[] = (int) $award['AwardData'];
        }
    }

    if (!empty($awardEventGameIds)) {
        $awardEventIds = array_merge($awardEventIds,
            Event::whereIn('legacy_game_id', $awardEventIds)->select('id')->pluck('id')->toArray()
        );
    }

    $eventData = new Collection();
    $eventAwardData = new Collection();
    if (!empty($awardEventIds)) {
        $eventData = Event::whereIn('id', $awardEventIds)->with('legacyGame')->get()->keyBy('id');
        $eventAwardData = EventAward::whereIn('event_id', $awardEventIds)->get()->groupBy('event_id');
    }

    $gameAwards = []; // Mastery awards that aren't Events.
    $eventAwards = []; // Event awards and Events mastery awards.
    $siteAwards = []; // Dev event awards and non-game active awards.

    foreach ($userAwards as $award) {
        $type = (int) $award['AwardType'];
        $id = (int) $award['AwardData'];

        if (AwardType::isGame($type)) {
            if ($award['ConsoleName'] === 'Events') {
                $eventAwards[] = $award;
            } elseif ($type !== AwardType::GameBeaten->toLegacyInteger()) {
                $gameAwards[] = $award;
            }
        } elseif ($type === AwardType::Event->toLegacyInteger()) {
            if ($eventData[$id]?->gives_site_award) {
                $siteAwards[] = $award;
            } else {
                $eventAwards[] = $award;
            }
        } elseif (AwardType::isActive($type)) {
            $siteAwards[] = $award;
        }
    }

    return [$gameAwards, $eventAwards, $siteAwards, $eventData, $eventAwardData];
}

function RenderSiteAwards(array $userAwards, string $awardsOwnerUsername): void
{
    [$gameAwards, $eventAwards, $siteAwards, $eventData, $eventAwardData] = SeparateAwards($userAwards);

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
        RenderAwardGroup($group[1], $group[2], $awardsOwnerUsername, $eventData, $eventAwardData);
    }
}

/**
 * @param Collection<int, Event> $eventData
 * @param SupportCollection<int, Collection<int, EventAward>> $eventAwardData
 */
function RenderAwardGroup(
    array $awards,
    string $title,
    string $awardsOwnerUsername,
    Collection $eventData,
    SupportCollection $eventAwardData,
): void {
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
            RenderAward($award, $imageSize, $awardsOwnerUsername, $eventData, $eventAwardData);
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
        "<div class='cursor-help flex gap-x-1 text-sm' title='$tooltip'>
            <div class='text-2xs'>$icon</div><div class='numitems'>$numItems</div>
        </div>";

    return $counter;
}

/**
 * @param Collection<int, Event> $eventData
 * @param SupportCollection<int, Collection<int, EventAward>> $eventAwardData
 */
function RenderAward(
    array $award,
    int $imageSize,
    string $ownerUsername,
    Collection $eventData,
    SupportCollection $eventAwardData,
    bool $clickable = true,
): void {
    $awardType = $award['AwardType'];
    $awardType = (int) $awardType;
    $awardData = $award['AwardData'];
    $awardDataExtra = $award['AwardDataExtra'];
    $awardGameTitle = $award['Title'];
    $awardGameConsole = $award['ConsoleName'];
    $awardGameImage = $award['ImageIcon'];
    $awardDate = getNiceDate((int) $award['AwardedAt'], justDay: true);
    $awardButGameIsIncomplete = (isset($award['Incomplete']) && $award['Incomplete'] == 1);
    $imgclass = 'badgeimg siteawards';

    if ($awardType === AwardType::Mastery->toLegacyInteger()) {
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

    if ($awardType === AwardType::Event->toLegacyInteger()) {
        $event = $eventData->find($awardData);
        if ($event) {
            $tooltipTitle = $event->title;
            $tooltipDescription = "Awarded for completing this event";
            $image = $event->image_asset_path;

            // Use the display preference for the badge image, but always
            // use the actual earned tier for the tooltip text. Otherwise,
            // it's very ambiguous what tier the player is actually on if
            // they have a saved tier preference.
            $displayTier = (int) ($award['display_award_tier'] ?? $awardDataExtra);
            $actualTier = (int) $awardDataExtra;

            $tierIndicesToFetch = array_unique([$displayTier, $actualTier]);
            $eventAwardsByTier = ($eventAwardData->get((int) $awardData) ?? collect())
                ->whereIn('tier_index', $tierIndicesToFetch)
                ->keyBy('tier_index');

            $displayEventAward = $eventAwardsByTier->get($displayTier);
            if ($displayEventAward) {
                $image = $displayEventAward->image_asset_path;
            }

            $actualEventAward = $eventAwardsByTier->get($actualTier);
            if ($actualEventAward && $actualEventAward->points_required < $event->legacyGame->points_total) {
                // Strip the event/game title prefix from the tier label to avoid duplication.
                $tierLabel = $actualEventAward->label;
                $gameTitle = $event->legacyGame->title ?? '';
                if ($tierLabel !== $gameTitle && str_starts_with($tierLabel, $gameTitle)) {
                    $tierLabel = ltrim(substr($tierLabel, strlen($gameTitle)), ' -:');
                }

                // Only append the tier label if the event title doesn't already contain it.
                if (!str_ends_with($event->title, $tierLabel)) {
                    $tooltipTitle = "{$event->title} - {$tierLabel}";
                }

                $tooltipDescription = "Awarded for earning at least {$actualEventAward->points_required} points";
            }

            echo avatar('event', $event->id,
                link: route('event.show', $event->id),
                tooltip: "<div class='p-2 max-w-[320px] text-pretty text-menu-link flex flex-col gap-1'><p class='font-bold'>{$tooltipTitle}</p><span>{$tooltipDescription}</span><p class='italic'>{$awardDate}</p></div>",
                iconUrl: media_asset($image),
                iconSize: $imageSize,
                iconClass: 'goldimage',
                context: $ownerUsername,
            );
        }

        return;
    }

    $awardTypeEnum = AwardType::fromLegacyInteger($awardType);

    if ($awardTypeEnum === AwardType::AchievementUnlocksYield) {
        // Developed a number of earned achievements
        $tooltip = "Awarded for being a hard-working developer and producing achievements that have been earned over " . PlayerBadge::getBadgeThreshold($awardTypeEnum, $awardData) . " times!";
        $imagepath = asset("/assets/images/badge/contribYield-$awardData.png");
        $imgclass = 'goldimage';
        $linkdest = '';
        // TBD: developer sets page?
    } elseif ($awardTypeEnum === AwardType::AchievementPointsYield) {
        // Yielded an amount of points earned by players
        $tooltip = "Awarded for producing many valuable achievements, providing over " . PlayerBadge::getBadgeThreshold($awardTypeEnum, $awardData) . " points to the community!";
        $imagepath = asset("/assets/images/badge/contribPoints-$awardData.png");
        $imgclass = 'goldimage';
        $linkdest = ''; // TBD: developer sets page?
    // } elseif ($awardTypeEnum === AwardType::Referrals) {
    //     $tooltip = "Referred $awardData members";
    //     $imagepath = "/Badge/00083.png";
    //     $linkdest = ''; // TBD: referrals page?
    } elseif ($awardTypeEnum === AwardType::PatreonSupporter) {
        $tooltip = 'Awarded for being a Patreon supporter! Thank-you so much for your support!';
        $imagepath = asset('/assets/images/badge/patreon.png');
        $imgclass = 'goldimage';
        $linkdest = route('patreon-supporter.index');
    } elseif ($awardTypeEnum === AwardType::CertifiedLegend) {
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

/**
 * @param Collection<int, Event> $eventData
 * @param SupportCollection<int, Collection<int, EventAward>> $eventAwardData
 */
function RenderAwardOrderTable(
    string $title,
    array $awards,
    string $awardOwnerUsername,
    int &$awardCounter,
    int $renderedSectionCount,
    bool $prefersSeeingSavedHiddenRows,
    int $initialSectionOrder,
    Collection $eventData,
    SupportCollection $eventAwardData,
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
        $awardDate = $award['AwardedAt'];
        $awardTitle = $award['Title'];
        $awardDisplayOrder = $award['DisplayOrder'];

        sanitize_outputs(
            $awardTitle,
            $awardType,
            $awardData,
            $awardDataExtra,
        );

        $awardTypeEnum = AwardType::fromLegacyInteger((int) $awardType);

        if ($awardTypeEnum === AwardType::Mastery) {
            $awardTitle = Blade::render('<x-game-title :rawTitle="$rawTitle" />', ['rawTitle' => $awardTitle]);
        } elseif ($awardTypeEnum === AwardType::AchievementUnlocksYield) {
            $awardTitle = "Achievements Earned by Others";
        } elseif ($awardTypeEnum === AwardType::AchievementPointsYield) {
            $awardTitle = "Achievement Points Earned by Others";
        } elseif ($awardTypeEnum === AwardType::PatreonSupporter) {
            $awardTitle = "Patreon Supporter";
        } elseif ($awardTypeEnum === AwardType::CertifiedLegend) {
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
                data-award-date='$awardDate'
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
        RenderAward($award, 32, $awardOwnerUsername, $eventData, $eventAwardData, false);
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
