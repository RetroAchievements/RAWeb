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

function updateAllAwardsDisplayOrder() {
    showStatusMessage('Updating...');

    const awards = [];

    $('.displayorderedit').each(function (index, element) {
        const row = $(element).closest('tr');
        const awardType = row.find("input[type='hidden'][name='type']").val();
        const awardData = row.find("input[type='hidden'][name='data']").val();
        const awardDataExtra = row.find("input[type='hidden'][name='extra']").val();
        const displayOrder = parseInt($(element).val(), 10);

        awards.push({
            type: awardType,
            data: awardData,
            extra: awardDataExtra,
            number: displayOrder
        });
    });

    $.post('/request/user/update-site-awards.php', { awards })
        .done(function (response) {
            showStatusMessage('Awards updated successfully');
            $('#rightcontainer').html(response.updatedAwardsHTML);
        })
        .fail(function () {
            showStatusMessage('Error updating awards');
        });
}

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

    const dropTarget = event.target.closest('tr');

    if (currentGrabbedRow && dropTarget) {
        const draggedTable = currentGrabbedRow.closest('table');
        const dropTargetTable = dropTarget.closest('table');

        if (draggedTable === dropTargetTable) {
            const dropTableId = event.target.closest('table').id;
            const initialDisplayOrderValue = getInitialDisplayOrderValue(dropTableId);

            const draggedRowIndex = Array.from(currentGrabbedRow.parentNode.children).indexOf(currentGrabbedRow);
            const dropTargetIndex = Array.from(dropTarget.parentNode.children).indexOf(dropTarget);

            if (draggedRowIndex !== dropTargetIndex) {
                if (draggedRowIndex < dropTargetIndex) {
                    dropTarget.parentNode.insertBefore(currentGrabbedRow, dropTarget.nextSibling);
                } else {
                    dropTarget.parentNode.insertBefore(currentGrabbedRow, dropTarget);
                }

                updateDisplayOrderValues(dropTableId, initialDisplayOrderValue);
            }
        }
    }

    dropTarget.classList.remove('border');
    dropTarget.classList.remove('border-menu-link');
}

function getInitialDisplayOrderValue(dropTableId) {
    let initialDisplayOrderValue = 0;

    const tableRows = document.querySelectorAll(`#${dropTableId} tbody tr`);
    if (tableRows[0]) {
        const firstRowOrderInput = tableRows[0].querySelector('.displayorderedit');
        initialDisplayOrderValue = parseInt(firstRowOrderInput.value, 10);
    }

    return initialDisplayOrderValue;
}

function updateDisplayOrderValues(dropTableId, startingValue = 0) {
    const tableRows = document.querySelectorAll(`#${dropTableId} tbody tr`);

    tableRows.forEach((row,index) => {
        const displayOrderInput = row.querySelector('.displayorderedit');
        const currentValue = parseInt(displayOrderInput.value, 10);

        if (currentValue !== -1) {
            displayOrderInput.value = startingValue + (10 * index);
        }
    });
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
        HTML;

        $userAwards = getUsersSiteAwards($user, true);

        [$gameAwards, $eventAwards, $siteAwards] = SeparateAwards($userAwards);

        function RenderAwardOrderTable(string $title, array $awards, int &$counter): void
        {
            // "Game Awards" -> "game"
            $humanReadableAwardType = strtolower(strtok($title, " "));

            echo "<br><h4>$title</h4>";
            echo "<table id='$humanReadableAwardType-reorder-table' class='table-highlight mb-2'>";

            echo "<thead>";
            echo "<tr class='do-not-highlight'>";
            echo "<th>Badge</th>";
            echo "<th width=\"75%\">Site Award</th>";
            echo "<th width=\"25%\">Award Date</th>";
            echo "<th>Display Order</th>";
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

                echo "<tr draggable='true' class='cursor-grab transition' ondragstart='handleRowDragStart(event)' ondragenter='handleRowDragEnter(event)' ondragleave='handleRowDragLeave(event)' ondragover='handleRowDragOver(event)' ondragend='handleRowDragEnd(event)' ondrop='handleRowDrop(event)'>";
                echo "<td>";
                RenderAward($award, 48, false);
                echo "</td>";
                echo "<td>$awardTitle</td>";
                echo "<td style=\"white-space: nowrap\"><span class='smalldate'>$awardDate</span><br></td>";
                echo "<td><input class='displayorderedit' data-award-type='$humanReadableAwardType' id='$counter' type='text' value='$awardDisplayOrder' size='3' /></td>";
                echo "<input type='hidden' name='type' value='$awardType'>";
                echo "<input type='hidden' name='data' value='$awardData'>";
                echo "<input type='hidden' name='extra' value='$awardDataExtra'>";

                echo "</tr>\n";
                $counter++;
            }
            echo "</tbody></table>\n";

            echo "<div class='flex w-full justify-end'>";
            echo "<button onclick='updateAllAwardsDisplayOrder()'>Save</button>";
            echo "</div>";
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
