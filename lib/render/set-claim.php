<?php

use RA\ClaimFilters;
use RA\ClaimSorting;

/**
 * Creates the Set Claims component.
 */
function renderClaimsComponent(int $count, int $claimFilter = ClaimFilters::Default): void
{
    echo "<div class='component'>";
    if ($claimFilter == ClaimFilters::CompletedFilter) {
        echo "<h3>Finished Set Claims</h3>";
    } else {
        echo "<h3>New Set Claims</h3>";
    }

    $claimData = getFilteredClaimData(0, $claimFilter, ClaimSorting::ClaimDateDescending, false, null, false, 0, $count);

    echo "<tbody><table>";
    echo "<th>User</th>";
    echo "<th>Game</th>";
    if ($claimFilter == ClaimFilters::CompletedFilter) {
        echo "<th nowrap>Type</th>";
        echo "<th nowrap>Finished On</th>";
    } else {
        echo "<th nowrap>Claimed On</th>";
    }
    foreach ($claimData as $claim) {
        $claimUser = $claim['User'];
        echo "<tr><td class='text-nowrap'>";
        echo GetUserAndTooltipDiv($claimUser, true);
        echo GetUserAndTooltipDiv($claimUser, false);
        echo "</td>";

        echo "<td>";
        echo GetGameAndTooltipDiv($claim['GameID'], $claim['GameTitle'], $claim['GameIcon'], $claim['ConsoleName']);
        echo "</td>";

        echo "<td class='smalldate'>" . ($claimFilter == ClaimFilters::CompletedFilter ? getNiceDate(strtotime($claim['DoneTime'])) : getNiceDate(strtotime($claim['Created']))) . " </td>";
    }
    echo "</tbody></table>";

    echo "<br>";
    echo "<div class='morebutton'><a href='/claimlist.php" . ($claimFilter == ClaimFilters::CompletedFilter ? "?f=" . ClaimFilters::CompletedFilter : "") . "''>more...</a></div>";

    echo "</div>";
}
