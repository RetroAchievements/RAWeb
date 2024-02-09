<?php

use App\Community\Enums\AwardType;
use App\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$prefersSeeingSavedHiddenRows = request()->cookie('prefers_seeing_saved_hidden_rows_when_reordering') === 'true';

$userAwards = getUsersSiteAwards($user, true);
[$gameAwards, $eventAwards, $siteAwards] = SeparateAwards($userAwards);

$hasSomeAwards = !empty($gameAwards) || !empty($eventAwards) || !empty($siteAwards);

$awardCounter = 0;
$renderedSectionCount = 0;

$renderedSectionCount += (!empty($gameAwards)) ? 1 : 0;
$renderedSectionCount += (!empty($eventAwards)) ? 1 : 0;
$renderedSectionCount += (!empty($siteAwards)) ? 1 : 0;

$initialSectionOrders = getInitialSectionOrders($gameAwards, $eventAwards, $siteAwards);
?>
<x-app-layout pageTitle="Reorder Site Awards">
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
            $('aside').html(response.updatedAwardsHTML);

            reorderSiteAwards.store.isFormDirty = false;
            reorderSiteAwards.refreshVisibilityAfterSave();
        })
        .fail(function () {
            showStatusMessage('Error updating awards');
        });
}
</script>
    <?php
    if ($hasSomeAwards) {
        $showSavedHiddenRowsCheckedAttribute = $prefersSeeingSavedHiddenRows ? 'checked' : '';

        echo <<<HTML
            <h2 id='reorder-site-awards-header'>Reorder Site Awards</h2>

            <div class="embedded grid gap-y-4">
                <p>
                    To rearrange your site awards, drag and drop the award rows or use
                    the buttons within each row to move them up or down. Award categories
                    can be reordered using the dropdown menus next to each category name.
                    Remember to save your changes before leaving by clicking the
                    "Save All Changes" button.
                </p>
            </div>

            <div class="flex w-full items-center justify-between mt-3 mb-6">
                <div class="flex items-center gap-x-1">
                    <label class="flex items-center gap-x-1">
                        <input
                            type="checkbox"
                            onchange="reorderSiteAwards.handleShowSavedHiddenRowsChange(event)"
                            $showSavedHiddenRowsCheckedAttribute
                        >
                            Show previously hidden badges
                        </input>
                    </label>
                </div>

                <button onclick='handleSaveAllClick()' class='btn text-base'>Save All Changes</button>
            </div>
        HTML;
    } else {
        echo <<<HTML
            <p>You don't have any awards. Earn your first by <a href="/game/1">mastering a game</a>!</p>
        HTML;
    }

    if (!empty($gameAwards)) {
        RenderAwardOrderTable(
            "Game Awards",
            $gameAwards,
            $user,
            $awardCounter,
            $renderedSectionCount,
            $prefersSeeingSavedHiddenRows,
            $initialSectionOrders[0],
        );
    }

    if (!empty($eventAwards)) {
        RenderAwardOrderTable(
            "Event Awards",
            $eventAwards,
            $user,
            $awardCounter,
            $renderedSectionCount,
            $prefersSeeingSavedHiddenRows,
            $initialSectionOrders[1],
        );
    }

    if (!empty($siteAwards)) {
        RenderAwardOrderTable(
            "Site Awards",
            $siteAwards,
            $user,
            $awardCounter,
            $renderedSectionCount,
            $prefersSeeingSavedHiddenRows,
            $initialSectionOrders[2],
        );
    }
    ?>
    @if ($hasSomeAwards)
        <x-slot name="sidebar">
            <?php
            RenderSiteAwards(getUsersSiteAwards($user), $user);
            ?>
        </x-slot>
    @endif
</x-app-layout>
