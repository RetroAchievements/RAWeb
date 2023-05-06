<?php

use App\Community\Enums\AwardType;
use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
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

    return <<<HTML
        <div class="$containerClassNames">
            <button
                title="$upA11yLabel" 
                aria-label="$upA11yLabel" 
                class="text-2xs"
                onclick="reorderSiteAwards.moveRow($awardCounter, $upValue, $autoScroll)"
            >
                ↑$upLabel
            </button>

            <button
                title="$downA11yLabel"
                aria-label="$downA11yLabel"
                class="text-2xs"
                onclick="reorderSiteAwards.moveRow($awardCounter, $downValue, $autoScroll)"
            >
                ↓$downLabel
            </button>
        </div>
    HTML;
}

$userAwards = getUsersSiteAwards($user, true);
[$gameAwards, $eventAwards, $siteAwards] = SeparateAwards($userAwards);

$hasSomeAwards = !empty($gameAwards) || !empty($eventAwards) || !empty($siteAwards);

$awardCounter = 0;
$renderedSectionCount = 0;

$renderedSectionCount += (!empty($gameAwards)) ? 1 : 0;
$renderedSectionCount += (!empty($eventAwards)) ? 1 : 0;
$renderedSectionCount += (!empty($siteAwards)) ? 1 : 0;

$initialSectionOrders = getInitialSectionOrders($gameAwards, $eventAwards, $siteAwards);

RenderContentStart("Reorder Site Awards");
?>
<script>
function handleSaveAllClick() {
    const mappedTableRows = [];

    const awardTableRowEls = document.querySelectorAll('.award-table-row');

    // Query and iterate over each table row on the page.
    // We'll invisibly compute the correct Display Order and
    // then send the values off to the back-end.
    awardTableRowEls.forEach((element) => {
        const parentTableEl = element.closest('table');
        const parentTableId = parentTableEl.getAttribute('id');

        const rowEl = element.closest('tr');
        const awardType = rowEl.querySelector("input[type='hidden'][name='type']").value;
        const awardData = rowEl.querySelector("input[type='hidden'][name='data']").value;
        const awardDataExtra = rowEl.querySelector("input[type='hidden'][name='extra']").value;
        const awardKind = rowEl.dataset.awardKind;
        const isHidden = rowEl.querySelector('input[type="checkbox"]').checked;

        mappedTableRows.push({
            isHidden,
            type: awardType,
            data: awardData,
            extra: awardDataExtra,
            kind: awardKind,
        });
    });

    try {
        const withComputedDisplayOrderValues = reorderSiteAwards.computeDisplayOrderValues(mappedTableRows);

        postAllAwardsDisplayOrder(withComputedDisplayOrderValues);
        reorderSiteAwards.moveHiddenRowsToTop();
    } catch (error) {
        showStatusFailure(error);
    }
}

function postAllAwardsDisplayOrder(awards) {
    showStatusMessage('Updating...');

    // We want the awards to occupy limited space over the network.
    const compressAwards = (award) => ([award.type, award.data, award.extra, award.number].join(','));

    // Pass both sets of awards as CSVs rather than array elements.
    const sortedAwards = awards.filter(award => award.number !== -1).map(compressAwards).join('|');
    const hiddenAwards = awards.filter(award => award.number === -1).map(compressAwards).join('|');

    $.post('/request/user/update-site-awards.php', { sortedAwards, hiddenAwards })
        .done(function (response) {
            showStatusMessage('Awards updated successfully');
            $('#rightcontainer').html(response.updatedAwardsHTML);

            reorderSiteAwards.state.isFormDirty = false;
        })
        .fail(function () {
            showStatusMessage('Error updating awards');
        });
}
</script>
<div id="mainpage">
    <div id="<?= $hasSomeAwards ? 'leftcontainer' : 'fullcontainer' ?>">
        <?php
        function RenderAwardOrderTable(string $title, array $awards, int &$awardCounter, int $renderedSectionCount, int $initialSectionOrder): void
        {
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
                    $awardGameConsole,
                    $awardType,
                    $awardData,
                    $awardDataExtra,
                );

                if ($awardType == AwardType::AchievementUnlocksYield) {
                    $awardTitle = "Achievements Earned by Others";
                } elseif ($awardType == AwardType::AchievementPointsYield) {
                    $awardTitle = "Achievement Points Earned by Others";
                } elseif ($awardType == AwardType::PatreonSupporter) {
                    $awardTitle = "Patreon Supporter";
                }

                $isHiddenPreChecked = $awardDisplayOrder === '-1';
                $subduedOpacityClassName = $isHiddenPreChecked ? 'opacity-40' : '';

                echo "<tr data-row-index='$awardCounter' data-award-kind='$humanReadableAwardKind' draggable='" . ($isHiddenPreChecked ? 'false' : 'true') . "' class='award-table-row select-none transition " . ($isHiddenPreChecked ? '' : 'cursor-grab') . "' ondragstart='reorderSiteAwards.handleRowDragStart(event)' ondragenter='reorderSiteAwards.handleRowDragEnter(event)' ondragleave='reorderSiteAwards.handleRowDragLeave(event)' ondragover='reorderSiteAwards.handleRowDragOver(event)' ondragend='reorderSiteAwards.handleRowDragEnd(event)' ondrop='reorderSiteAwards.handleRowDrop(event)'>";
                echo "<td class='$subduedOpacityClassName transition'>";
                RenderAward($award, 48, false);
                echo "</td>";
                echo "<td class='$subduedOpacityClassName transition'><span>$awardTitle</span></td>";
                echo "<td class='text-center !opacity-100'><input name='$awardCounter-is-hidden' onchange='reorderSiteAwards.handleRowHiddenCheckedChange(event, $awardCounter)' type='checkbox' " . ($isHiddenPreChecked ? "checked" : "") . "></td>";

                echo "<td>";
                echo "<div class='award-movement-buttons flex justify-end transition " . ($isHiddenPreChecked ? 'opacity-0' : 'opacity-100') . "'>";
                if (count($awards) > 50) {
                    echo generateManualMoveButtons($awardCounter, 99999, upLabel: ' Top', downLabel: ' Bottom', autoScroll: true);
                    echo generateManualMoveButtons($awardCounter, 50, upLabel: '50', downLabel: '50', autoScroll: true);
                    echo generateManualMoveButtons($awardCounter, 1);
                } elseif (count($awards) > 15) {
                    echo generateManualMoveButtons($awardCounter, 10, upLabel: '10', downLabel: '10', autoScroll: true);
                    echo generateManualMoveButtons($awardCounter, 1);
                } else {
                    echo generateManualMoveButtons($awardCounter, 1, orientation: 'horizontal');
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

        if ($hasSomeAwards) {
            echo "<h2 id='reorder-site-awards-header'>Reorder Site Awards</h2>";

            echo <<<HTML
                <div class="embedded grid gap-y-4">
                    <p>
                        To rearrange your site awards, drag and drop the award rows or use
                        the buttons within each row to move them up or down. Award categories
                        can be reordered using the dropdown menus next to each category name.
                        Remember to save your changes before leaving by clicking the 
                        "Save All Changes" button.
                    </p>
                </div>

                <div class="flex w-full">
                    <button onclick='handleSaveAllClick()' class='mt-2 mb-6 text-base'>Save All Changes</button>
                </div>
            HTML;
        } else {
            echo <<<HTML
                <p>You don't have any awards. Earn your first by <a href="/game/1">mastering a game</a>!</p>
            HTML;
        }

        if (!empty($gameAwards)) {
            RenderAwardOrderTable("Game Awards", $gameAwards, $awardCounter, $renderedSectionCount, $initialSectionOrders[0]);
        }

        if (!empty($eventAwards)) {
            RenderAwardOrderTable("Event Awards", $eventAwards, $awardCounter, $renderedSectionCount, $initialSectionOrders[1]);
        }

        if (!empty($siteAwards)) {
            RenderAwardOrderTable("Site Awards", $siteAwards, $awardCounter, $renderedSectionCount, $initialSectionOrders[2]);
        }
        ?>
    </div>

    <?php if ($hasSomeAwards): ?>
    <div id="rightcontainer">
        <?php RenderSiteAwards(getUsersSiteAwards($user)) ?>
    </div>
    <?php endif ?>
</div>
<?php RenderContentEnd(); ?>
