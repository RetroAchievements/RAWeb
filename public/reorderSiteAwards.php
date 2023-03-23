<?php

use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

function getInitialSectionOrders($gameAwards, $eventAwards, $siteAwards) {
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

RenderContentStart("Reorder Site Awards");
?>
<script>
let currentGrabbedRow = null;
let isFormDirty = false;

window.addEventListener('beforeunload', function (event) {
    if (isFormDirty) {
        event.preventDefault();
        // Most browsers will override this with their own "unsaved changes" message.
        event.returnValue = 'You have unsaved changes. Do you still want to leave?';
    }
});

function handleRowDragStart(event) {
    currentGrabbedRow = event.target;
    event.target.style.opacity = '0.3';
}

function handleRowDragEnd(event) {
    currentGrabbedRow = null;
    event.target.style.opacity = '1';
}

function handleRowDragEnter(event) {
    const targetRowEl = event.target.closest('tr');
    const isHiddenCheckboxEl = targetRowEl.querySelector('input[type="checkbox"]');

    const isHoveredRowInSameTable = currentGrabbedRow.parentNode === targetRowEl.parentNode;
    const isAwardHiddenChecked = isHiddenCheckboxEl.checked;

    if (targetRowEl && isHoveredRowInSameTable && !isAwardHiddenChecked) {
        targetRowEl.classList.add('border');
        targetRowEl.classList.add('border-menu-link');
    }
}

function handleRowDragOver(event) {
    event.preventDefault();
    return false;
}

function handleRowDragLeave(event) {
    const targetRow = event.target.closest('tr');
    if (targetRow) {
        targetRow.classList.remove('border');
        targetRow.classList.remove('border-menu-link');
    }
}

function handleRowDrop(event) {
    event.preventDefault();

    isFormDirty = true;

    const dropTarget = event.target.closest('tr');
    const isHiddenCheckboxEl = dropTarget.querySelector('input[type="checkbox"]');

    if (currentGrabbedRow && dropTarget) {
        const draggedTable = currentGrabbedRow.closest('table');
        const dropTargetTable = dropTarget.closest('table');

        if (draggedTable === dropTargetTable && !isHiddenCheckboxEl.checked) {
            const dropTableId = event.target.closest('table').id;

            const draggedRowIndex = Array.from(currentGrabbedRow.parentNode.children).indexOf(currentGrabbedRow);
            const dropTargetIndex = Array.from(dropTarget.parentNode.children).indexOf(dropTarget);

            if (draggedRowIndex !== dropTargetIndex) {
                if (draggedRowIndex < dropTargetIndex) {
                    dropTarget.parentNode.insertBefore(currentGrabbedRow, dropTarget.nextSibling);
                } else {
                    dropTarget.parentNode.insertBefore(currentGrabbedRow, dropTarget);
                }
            }
        }
    }

    dropTarget.classList.remove('border');
    dropTarget.classList.remove('border-menu-link');
}

function handleSaveAllClick() {
    const mappedTableRows = [];

    // Query and iterate over each table row on the page.
    // We'll invisibly compute the correct Display Order and
    // then send the values off to the back-end.
    $('.award-table-row').each(function (index, element) {
        const rowTableEl = $(element).closest('table');
        const rowParentTableId = rowTableEl.attr('id');

        const rowEl = $(element).closest('tr');
        const awardType = rowEl.find("input[type='hidden'][name='type']").val();
        const awardData = rowEl.find("input[type='hidden'][name='data']").val();
        const awardDataExtra = rowEl.find("input[type='hidden'][name='extra']").val();
        const awardKind = $(rowEl).data('awardKind');
        const isHidden = rowEl.find('input[type="checkbox"]').prop('checked');

        mappedTableRows.push({
            isHidden,
            type: awardType,
            data: awardData,
            extra: awardDataExtra,
            kind: awardKind,
        });
    });

    try {
        const withComputedDisplayOrderValues = computeDisplayOrderValues(mappedTableRows);

        postAllAwardsDisplayOrder(withComputedDisplayOrderValues);
        moveHiddenRowsToTop();
    } catch (error) {
        showStatusFailure(error);
    }
}

function moveHiddenRowsToTop() {
    const tableEls = document.querySelectorAll('table');

    tableEls.forEach(tableEl => {
        const tbodyEl = tableEl.querySelector('tbody') || tableEl;
        const rowEls = tableEl.querySelectorAll('tr');
        const hiddenRows = [];
        const visibleRows = [];

        rowEls.forEach(rowEl => {
            const checkboxEl = rowEl.querySelector('input[name$="-is-hidden"]');
            if (checkboxEl && checkboxEl.checked) {
                hiddenRows.push(rowEl);
            } else {
                visibleRows.push(rowEl);
            }
        });

        if (visibleRows.length > 0) {
            const firstVisibleRowParent = visibleRows[1]?.parentNode;
            if (firstVisibleRowParent) {
                hiddenRows.forEach(hiddenRow => {
                    firstVisibleRowParent.insertBefore(hiddenRow, visibleRows[1]);
                });
            }
        }
    });
}

function buildSectionsOrderList() {
    const sectionOrderSelectEls = document.querySelectorAll('select[data-award-kind]');
    const selectedValues = {};

    let hasDuplicates = false;
    let orderedArray = [];

    for (const sectionOrderSelectEl of sectionOrderSelectEls) {
        const awardKind = sectionOrderSelectEl.getAttribute('data-award-kind');
        const currentValue = sectionOrderSelectEl.value;

        if (selectedValues[currentValue]) {
            hasDuplicates = true;
        } else {
            selectedValues[currentValue] = awardKind;
        }
    }

    if (hasDuplicates) {
        throw new Error('Please ensure each section has a unique order number.')
    }

    // Build the order list.
    Object.keys(selectedValues).sort().forEach((key) => {
        orderedArray.push(selectedValues[key]);
    });

    return orderedArray;
}

function computeDisplayOrderValues(mappedTableRows) {
    const sectionsOrder = buildSectionsOrderList();

    const sortedBySectionsOrder = [];
    for (const targetSection of sectionsOrder) {
        const sectionRows = mappedTableRows.filter(row => row.kind === targetSection);
        sortedBySectionsOrder.push(...sectionRows);
    }

    const withDisplayOrderValues = sortedBySectionsOrder.map((row, rowIndex) => {
        let displayOrder = -1; // Hidden by default

        if (!row.isHidden) {
            // The first group will have an offset of 0.
            // The second group will have an offset of 3000.
            // The third group will have an offset of 6000.
            // etc...
            const groupOffsetBoost = sectionsOrder.findIndex(sectionName => sectionName === row.kind) * 3000;

            // Arbitrarily shift by 20 to account for group ordering.
            displayOrder = (rowIndex + 20) + groupOffsetBoost;
        }

        return {
            kind: row.kind,
            type: row.type,
            data: row.data,
            extra: row.extra,
            number: displayOrder,
        }
    });

    // Now properly order the sections by fixing the displayOrder value
    // of the first `number` for each visible section row.
    for (let i = 0; i < sectionsOrder.length; i += 1) {
        const currentSectionKind = sectionsOrder[i];

        for (const row of withDisplayOrderValues) {
            if (row.number !== -1 && row.kind === currentSectionKind) {
                row.number = i;
                break;
            }
        }
    }

    return withDisplayOrderValues;
}


function postAllAwardsDisplayOrder(awards) {
    showStatusMessage('Updating...');

    $.post('/request/user/update-site-awards.php', { awards })
        .done(function (response) {
            showStatusMessage('Awards updated successfully');
            $('#rightcontainer').html(response.updatedAwardsHTML);

            isFormDirty = false;
        })
        .fail(function () {
            showStatusMessage('Error updating awards');
        });
}

function handleRowHiddenCheckedChange(event, rowIndex) {
    const isHiddenChecked = event.target.checked;

    const targetRowEl = document.querySelector(`tr[data-row-index="${rowIndex}"]`);
    if (targetRowEl) {
        if (isHiddenChecked) {
            targetRowEl.classList.remove('cursor-grab');
            targetRowEl.setAttribute('draggable', false);
        } else {
            targetRowEl.classList.add('cursor-grab');
            targetRowEl.setAttribute('draggable', true);
        }

        const allTdEls = targetRowEl.querySelectorAll('td');
        allTdEls.forEach(tdEl => {
            if (isHiddenChecked && !tdEl.classList.contains('!opacity-100')) {
                tdEl.classList.add('opacity-40');
            } else {
                tdEl.classList.remove('opacity-40');
            }
        });
    } 
}

function handleDisplayOrderChange() {
    isFormDirty = true;
}
</script>
<div id="mainpage">
    <div id="leftcontainer">
        <?php
        echo "<h2>Reorder Site Awards</h2>";

        echo <<<HTML
            <div class="embedded grid gap-y-4">
                <p>
                    To rearrange your site awards, modify the 'Display Order' value in the rightmost 
                    column of each award row. The awards will be displayed on your user page in 
                    ascending order based on these values. To hide an award, change its 'Display Order' 
                    value to -1. Remember to save your changes by clicking the 'Save' button, and 
                    your updates will appear on your user page immediately.
                </p>

                <p>
                    You can also reorder the award categories, such as 'Site Awards' and 'Game Awards'. 
                    This is determined by the first 'Display Order' number within each category. 
                    For example, to show 'Site Awards' before 'Game Awards', set the first 'Display Order' 
                    value of 'Site Awards' to 0 and the first 'Display Order' value of 'Game Awards' to 1.
                </p>
            </div>

            <div class="flex w-full justify-end">
                <button onclick='handleSaveAllClick()' class='mt-2 mb-6 text-base'>Save all changes</button>
            </div>
        HTML;

        $userAwards = getUsersSiteAwards($user, true);

        [$gameAwards, $eventAwards, $siteAwards] = SeparateAwards($userAwards);

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

            echo "<table id='$humanReadableAwardKind-reorder-table' class='table-highlight mb-8'>";

            echo "<thead>";
            echo "<tr class='do-not-highlight'>";
            echo "<th>Badge</th>";
            echo "<th width=\"75%\">Site Award</th>";
            echo "<th width=\"25%\">Award Date</th>";
            echo "<th>Hidden</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            foreach ($awards as $award) {
                $awardType = $award['AwardType'];
                $awardData = $award['AwardData'];
                $awardDataExtra = $award['AwardDataExtra'];
                $awardTitle = $award['Title'];
                $awardDisplayOrder = $award['DisplayOrder'];
                $awardDate = getNiceDate($award['AwardedAt']);

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

                echo "<tr data-row-index='$awardCounter' data-award-kind='$humanReadableAwardKind' draggable='" . ($isHiddenPreChecked ? 'false' : 'true') . "' class='award-table-row select-none transition " . ($isHiddenPreChecked ? '' : 'cursor-grab') . "' ondragstart='handleRowDragStart(event)' ondragenter='handleRowDragEnter(event)' ondragleave='handleRowDragLeave(event)' ondragover='handleRowDragOver(event)' ondragend='handleRowDragEnd(event)' ondrop='handleRowDrop(event)'>";
                echo "<td class='$subduedOpacityClassName transition'>";
                RenderAward($award, 48, false);
                echo "</td>";
                echo "<td class='$subduedOpacityClassName transition'><span>$awardTitle</span></td>";
                echo "<td class='$subduedOpacityClassName whitespace-nowrap transition'><span class='smalldate'>$awardDate</span><br></td>";
                echo "<td class='text-center !opacity-100'><input name='$awardCounter-is-hidden' onchange='handleRowHiddenCheckedChange(event, $awardCounter)' type='checkbox' " . ($isHiddenPreChecked ? "checked" : "") . "></td>";
                echo "<input type='hidden' name='type' value='$awardType'>";
                echo "<input type='hidden' name='data' value='$awardData'>";
                echo "<input type='hidden' name='extra' value='$awardDataExtra'>";

                echo "</tr>\n";
                $awardCounter++;
            }
            echo "</tbody></table>";
        }

        $awardCounter = 0;
        $renderedSectionCount = 0;

        $renderedSectionCount += (!empty($gameAwards)) ? 1 : 0;
        $renderedSectionCount += (!empty($eventAwards)) ? 1 : 0;
        $renderedSectionCount += (!empty($siteAwards)) ? 1 : 0;

        $initialSectionOrders = getInitialSectionOrders($gameAwards, $eventAwards, $siteAwards);

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
    <div id="rightcontainer">
        <?php RenderSiteAwards(getUsersSiteAwards($user)) ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
