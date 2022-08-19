<?php

use RA\ClaimFilters;
use RA\ClaimSetType;
use RA\ClaimSorting;

/**
 * Creates the New Set Claims component.
 */
function renderNewClaimsComponent(int $count): void
{
    echo "<div class='component'>";
    echo "<h3>Sets in Progress</h3>";

    $claimData = getFilteredClaimData(0, ClaimFilters::Default, ClaimSorting::ClaimDateDescending, false, null, false, 0, $count);

    echo "<tbody><table>";
    echo "<th>User</th>";
    echo "<th>Game</th>";
    echo "<th nowrap>Started</th>";
    foreach ($claimData as $claim) {
        $claimUser = $claim['User'];
        echo "<tr><td class='text-nowrap'>";
        echo GetUserAndTooltipDiv($claimUser, true);
        echo GetUserAndTooltipDiv($claimUser, false);
        echo "</td>";

        echo "<td>";
        echo GetGameAndTooltipDiv($claim['GameID'], $claim['GameTitle'], $claim['GameIcon'], $claim['ConsoleName']);
        echo "</td>";

        echo "<td class='smalldate'>" . getNiceDate(strtotime($claim['Created'])) . "</td>";
    }
    echo "</tbody></table>";

    echo "<br>";
    echo "<div class='morebutton'><a href='/claimlist.php'>more...</a></div>";
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

    echo "<tbody><table>";
    echo "<th>User</th>";
    echo "<th>Game</th>";
    echo "<th nowrap>Type</th>";
    echo "<th nowrap>Finished</th>";
    foreach ($claimData as $claim) {
        $claimUser = $claim['User'];
        echo "<tr><td class='text-nowrap'>";
        echo GetUserAndTooltipDiv($claimUser, true);
        echo GetUserAndTooltipDiv($claimUser, false);
        echo "</td>";

        echo "<td>";
        echo GetGameAndTooltipDiv($claim['GameID'], $claim['GameTitle'], $claim['GameIcon'], $claim['ConsoleName']);
        echo "</td>";

        echo "<td>" . ($claim['SetType'] == ClaimSetType::NewSet ? ClaimSetType::toString(ClaimSetType::NewSet) : ClaimSetType::toString(ClaimSetType::Revision)) . "</td>";

        echo "<td class='smalldate'>" . getNiceDate(strtotime($claim['DoneTime'])) . "</td>";
    }
    echo "</tbody></table>";

    echo "<br>";
    echo "<div class='morebutton'><a href='/claimlist.php?f=" . ClaimFilters::AllCompletedPrimaryClaims . "'>more...</a></div>";
    echo "</div>";
}
