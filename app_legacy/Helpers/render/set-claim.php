<?php

use LegacyApp\Community\Enums\ClaimFilters;
use LegacyApp\Community\Enums\ClaimSetType;
use LegacyApp\Community\Enums\ClaimSorting;

/**
 * Creates the New Set Claims component.
 */
function renderNewClaimsComponent(int $count): void
{
    echo "<div class='component'>";
    echo "<h3>Sets in Progress</h3>";

    $claimData = getFilteredClaims(null, ClaimFilters::Open, ClaimSorting::ClaimDateDescending, false, null, 0, $count);

    echo "<table class='table-highlight mb-1'>";
    echo "<thead>";
    echo "<tr class='do-not-highlight'>";
    echo "<th class='pr-0'></th>";
    echo "<th>User</th>";
    echo "<th class='pr-0'></th>";
    echo "<th>Game</th>";
    echo "<th class='whitespace-nowrap'>Started</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    foreach ($claimData as $claim) {
        $claimUser = $claim['User'];
        echo "<tr>";
        echo "<td class='pr-0'>";
        echo userAvatar($claimUser, label: false);
        echo "</td>";
        echo "<td>";
        echo userAvatar($claimUser, label: true);
        echo "</td>";
        echo "<td class='pr-0'>";
        echo gameAvatar($claim, label: false);
        echo "</td>";
        echo "<td class='w-full'>";
        echo gameAvatar($claim, icon: false);
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

    $claimData = getFilteredClaims(null, ClaimFilters::AllCompletedPrimaryClaims, ClaimSorting::FinishedDateDescending, false, null, 0, $count);

    echo "<table class='table-highlight mb-1'>";
    echo "<thead>";
    echo "<tr class='do-not-highlight'>";
    echo "<th class='pr-0'></th>";
    echo "<th>User</th>";
    echo "<th class='pr-0'></th>";
    echo "<th>Game</th>";
    echo "<th>Type</th>";
    echo "<th>Finished</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    foreach ($claimData as $claim) {
        $claimUser = $claim['User'];
        echo "<tr>";
        echo "<td class='pr-0'>";
        echo userAvatar($claimUser, label: false);
        echo "</td>";
        echo "<td>";
        echo userAvatar($claimUser, icon: false);
        echo "</td>";
        echo "<td class='pr-0'>";
        echo gameAvatar($claim, label: false);
        echo "</td>";
        echo "<td class='w-full'>";
        echo gameAvatar($claim, icon: false);
        echo "</td>";
        echo "<td>" . ($claim['SetType'] == ClaimSetType::NewSet ? ClaimSetType::toString(ClaimSetType::NewSet) : ClaimSetType::toString(ClaimSetType::Revision)) . "</td>";
        echo "<td class='smalldate'>" . getNiceDate(strtotime($claim['DoneTime'])) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";

    echo "<div class='text-right'><a class='btn btn-link' href='/claimlist.php?s=" . ClaimSorting::FinishedDateDescending . "&f=" . ClaimFilters::AllCompletedPrimaryClaims . "'>more...</a></div>";
    echo "</div>";
}
