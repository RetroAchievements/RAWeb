<?php

use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
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
    const targetRow = event.target.closest('tr');
    if (targetRow && currentGrabbedRow.parentNode === targetRow.parentNode) {
        targetRow.classList.add('border');
        targetRow.classList.add('border-menu-link');
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

    if (currentGrabbedRow && dropTarget) {
        const draggedTable = currentGrabbedRow.closest('table');
        const dropTargetTable = dropTarget.closest('table');

        if (draggedTable === dropTargetTable) {
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

    const withComputedDisplayOrderValues = computeDisplayOrderValues(mappedTableRows);

    postAllAwardsDisplayOrder(withComputedDisplayOrderValues);
    moveHiddenRowsToTop();
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
            const firstVisibleRowParent = visibleRows[1].parentNode;
            hiddenRows.forEach(hiddenRow => {
                firstVisibleRowParent.insertBefore(hiddenRow, visibleRows[1]);
            });
        }
    });
}

function computeDisplayOrderValues(mappedTableRows) {
    // TODO: This needs to be dynamic, decided by the user.
    const sectionsOrder = ['game', 'site'];

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
                <button onclick='handleSaveAllClick()' class='my-2 text-base'>Save all changes</button>
            </div>
        HTML;

        $userAwards = getUsersSiteAwards($user, true);

        [$gameAwards, $eventAwards, $siteAwards] = SeparateAwards($userAwards);

        function RenderAwardOrderTable(string $title, array $awards, int &$counter): void
        {
            // "Game Awards" -> "game"
            $humanReadableAwardKind = strtolower(strtok($title, " "));

            echo "<br><h4>$title</h4>";
            echo "<table id='$humanReadableAwardKind-reorder-table' class='table-highlight mb-4'>";

            echo "<thead>";
            echo "<tr class='do-not-highlight'>";
            echo "<th>Badge</th>";
            echo "<th width=\"75%\">Site Award</th>";
            echo "<th width=\"25%\">Award Date</th>";
            echo "<th class='hidden sm:table-cell'>Hidden</th>";
            echo "<th class='sm:hidden'>Display Order</th>";
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

                echo "<tr data-row-index='$counter' data-award-kind='$humanReadableAwardKind' draggable='true' class='award-table-row cursor-grab transition' ondragstart='handleRowDragStart(event)' ondragenter='handleRowDragEnter(event)' ondragleave='handleRowDragLeave(event)' ondragover='handleRowDragOver(event)' ondragend='handleRowDragEnd(event)' ondrop='handleRowDrop(event)'>";
                echo "<td class='$subduedOpacityClassName transition'>";
                RenderAward($award, 48, false);
                echo "</td>";
                echo "<td class='$subduedOpacityClassName transition'><span>$awardTitle</span></td>";
                echo "<td class='$subduedOpacityClassName whitespace-nowrap transition'><span class='smalldate'>$awardDate</span><br></td>";
                echo "<td class='hidden sm:table-cell text-center !opacity-100'><input name='$counter-is-hidden' onchange='handleRowHiddenCheckedChange(event, $counter)' type='checkbox' " . ($isHiddenPreChecked ? "checked" : "") . "></td>";
                echo "<td class='sm:hidden'><input class='displayorderedit' data-award-type='$humanReadableAwardKind' id='$counter' type='text' value='$awardDisplayOrder' size='3' onchange='handleDisplayOrderChange()' /></td>";
                echo "<input type='hidden' name='type' value='$awardType'>";
                echo "<input type='hidden' name='data' value='$awardData'>";
                echo "<input type='hidden' name='extra' value='$awardDataExtra'>";

                echo "</tr>\n";
                $counter++;
            }
            echo "</tbody></table>";
        }

        $counter = 0;
        if (!empty($gameAwards)) {
            RenderAwardOrderTable("Game Awards", $gameAwards, $counter);
        }

        if (!empty($eventAwards)) {
            RenderAwardOrderTable("Event Awards", $eventAwards, $counter);
        }

        if (!empty($siteAwards)) {
            RenderAwardOrderTable("Site Awards", $siteAwards, $counter);
        }
        ?>
    </div>
    <div id="rightcontainer">
        <?php RenderSiteAwards(getUsersSiteAwards($user)) ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
