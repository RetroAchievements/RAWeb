<?php

use RA\ClaimFilters;
use RA\ClaimSetType;
use RA\ClaimSorting;
use RA\LinkStyle;

/**
 * Creates the New Set Claims component.
 */
function renderNewClaimsComponent(int $count): void
{
    echo "<div class='component'>";
    echo "<h3>Sets in Progress</h3>";

    $claimData = getFilteredClaimData(0, ClaimFilters::Default, ClaimSorting::ClaimDateDescending, false, null, false, 0, $count);

    echo "<table class='mb-1'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>User</th>";
    echo "<th>Game</th>";
    echo "<th class='whitespace-nowrap'>Started</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    foreach ($claimData as $claim) {
        $claimUser = $claim['User'];
        echo "<tr>";
        echo "<td>";
        RenderUserLink($claimUser, LinkStyle::MediumImageWithText);
        echo "</td>";
        echo "<td class='w-full'>";
        echo GetGameAndTooltipDiv($claim['GameID'], $claim['GameTitle'], $claim['GameIcon'], $claim['ConsoleName']);
        echo "</td>";
        echo "<td class='smalldate'>" . getNiceDate(strtotime($claim['Created'])) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "<div class='text-right'><a class='btn btn-link' href='/claimlist.php'>more...</a></div>";
    echo "</div>";
}

/**
 * Creates the Completed Set Claims component.
 */
function renderFinishedClaimsComponent(int $count): void
{
    echo "<div class='component'>";
    echo "<h3>New Sets/Revisions</h3>";

    $claimData = getFilteredClaimData(0, ClaimFilters::AllCompletedPrimaryClaims, ClaimSorting::FinishedDateDescending, false, null, false, 0, $count);

    echo "<table class='mb-1'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>User</th>";
    echo "<th>Game</th>";
    echo "<th>Type</th>";
    echo "<th>Finished</th>";
    echo "</tr>";
    echo "<tbody>";
    foreach ($claimData as $claim) {
        $claimUser = $claim['User'];
        echo "<tr>";
        echo "<td>";
        RenderUserLink($claimUser, LinkStyle::MediumImageWithText);
        echo "</td>";
        echo "<td class='w-full'>";
        echo GetGameAndTooltipDiv($claim['GameID'], $claim['GameTitle'], $claim['GameIcon'], $claim['ConsoleName']);
        echo "</td>";
        echo "<td>" . ($claim['SetType'] == ClaimSetType::NewSet ? ClaimSetType::toString(ClaimSetType::NewSet) : ClaimSetType::toString(ClaimSetType::Revision)) . "</td>";
        echo "<td class='smalldate'>" . getNiceDate(strtotime($claim['DoneTime'])) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "<div class='text-right'><a class='btn btn-link' href='/claimlist.php?s=" . ClaimSorting::FinishedDateDescending . "&f=" . ClaimFilters::AllCompletedPrimaryClaims . "'>more...</a></div>";
    echo "</div>";
}
