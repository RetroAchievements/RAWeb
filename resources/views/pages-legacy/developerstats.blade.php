<?php

use App\Community\Enums\TicketFilters;
use App\Enums\Permissions;

authenticateFromCookie($user, $permissions, $userDetails);

$type = requestInputSanitized('t', 0, 'integer');
$defaultFilter = 7; // set all 3 status' to enabled
$devFilter = requestInputSanitized('f', 7, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');
$resolvedForOthersTicketFilter = (
    TicketFilters::AllFilters
    & ~TicketFilters::StateOpen
    & ~TicketFilters::StateRequest
    & ~TicketFilters::StateClosed
);

$maxItemsPerPage = 25;
$devStatsList = GetDeveloperStatsFull($maxItemsPerPage, $offset, $type, $devFilter);
$filteredDevCount = sizeof($devStatsList);
$totalDevCount = getDeveloperStatsTotalCount($devFilter);
$totalPages = ceil($totalDevCount / $maxItemsPerPage);
$currentPage = ($offset / $maxItemsPerPage) + 1;

$previousPageHref = null;
$nextPageHref = null;
if ($currentPage > 1) {
    $previousOffset = $offset - $filteredDevCount;
    $previousPageHref = url('developerstats.php?t=' . $type . '&f=' . $devFilter . '&o=' . $previousOffset);
}
if ($currentPage < $totalPages) {
    $nextOffset = $offset + $filteredDevCount;
    $nextPageHref = url('developerstats.php?t=' . $type . '&f=' . $devFilter . '&o=' . $nextOffset);
}
?>

<x-app-layout pageTitle="Developer Stats">
    <h3>Developer Stats</h3>
    <?php
    echo "<div class='embedded mb-1'>";
    $activeDev = ($devFilter & (1 << 0));
    $juniorDev = ($devFilter & (1 << 1));
    $inactiveDev = ($devFilter & (1 << 2));

    $orderedByName = $type == 6 ? "*" : "";
    $orderedByOpenTickets = $type == 3 ? "*" : "";
    $orderedByAchievements = $type == 0 ? "*" : "";
    $orderedByResolvedTickets = $type == 4 ? "*" : "";
    $orderedByYieldedUnlocks = $type == 2 ? "*" : "";
    $orderedByYieldedPoints = $type == 1 ? "*" : "";
    $orderedByActiveClaims = $type == 7 ? "*" : "";

    echo "<div>";
    echo "<b>Developer Status:</b> ";
    if ($activeDev) {
        echo "<b><a href='/developerstats.php?t=$type&f=" . ($devFilter & ~(1 << 0)) . "'>*" . Permissions::toString(Permissions::Developer) . "</a></b> | ";
    } else {
        echo "<a href='/developerstats.php?t=$type&f=" . ($devFilter | (1 << 0)) . "'>" . Permissions::toString(Permissions::Developer) . "</a> | ";
    }

    if ($juniorDev) {
        echo "<b><a href='/developerstats.php?t=$type&f=" . ($devFilter & ~(1 << 1)) . "'>*" . Permissions::toString(Permissions::JuniorDeveloper) . "</a></b> | ";
    } else {
        echo "<a href='/developerstats.php?t=$type&f=" . ($devFilter | (1 << 1)) . "'>" . Permissions::toString(Permissions::JuniorDeveloper) . "</a> | ";
    }

    if ($inactiveDev) {
        echo "<b><a href='/developerstats.php?t=$type&f=" . ($devFilter & ~(1 << 2)) . "'>*Inactive</a></b>";
    } else {
        echo "<a href='/developerstats.php?t=$type&f=" . ($devFilter | (1 << 2)) . "'>Inactive</a>";
    }
    echo "<br>";
    echo "Filtered Developers: $filteredDevCount of $totalDevCount";
    echo "</div>";

    // Clear Filter
    if ($devFilter != $defaultFilter) {
        echo "<div>";
        echo "<a href='/developerstats.php?t=$type&f=" . $defaultFilter . "'>Clear Filter</a>";
        echo "</div>";
    }

    echo "</div>";

    echo "<div class='float-right'>* = ordered by</div>";
    echo "<br style='clear:both;'>";
    echo "<div class='table-wrapper mb-2'><table class='table-highlight'><tbody>";

    echo "<tr class='do-not-highlight'>";
    echo "<th></th>";
    echo "<th><a href='/developerstats.php?t=6&f=$devFilter'>User</a>$orderedByName</th>";
    echo "<th class='text-right whitespace-nowrap'><a href='/developerstats.php?t=3&f=$devFilter'>Open Tickets</a>$orderedByOpenTickets</th>";
    echo "<th class='text-right whitespace-nowrap'><a href='/developerstats.php?f=$devFilter'>Achievements</a>$orderedByAchievements</th>";
    echo "<th class='text-right' style='max-width: 120px'><a href='/developerstats.php?t=4&f=$devFilter' title='Ticket Resolved for Others'>Tickets Resolved For Others</a>$orderedByResolvedTickets</th>";
    echo "<th class='text-right'><a href='/developerstats.php?t=2&f=$devFilter' title='Achievements unlocked by others'>Yielded Unlocks</a>$orderedByYieldedUnlocks</th>";
    echo "<th class='text-right'><a href='/developerstats.php?t=1&f=$devFilter' title='Points gained by others through achievement unlocks'>Yielded Points</a>$orderedByYieldedPoints</th>";
    echo "<th class='text-right'><a href='/developerstats.php?t=7&f=$devFilter' title='Set claims currently active'>Active Claims</a>$orderedByActiveClaims</th>";
    echo "</tr>";

    $userCount = 0;
    foreach ($devStatsList as $devStats) {
        if ($userCount++ % 2 == 0) {
            echo "<tr>";
        } else {
            echo "<tr class=\"alt\">";
        }

        $dev = $devStats['Author'];
        echo "<td class='whitespace-nowrap w-[32px]'>";
        echo userAvatar($dev, label: false);
        echo "</td>";
        echo "<td class='whitespace-nowrap'><div>";
        echo userAvatar($dev, icon: false);
        echo "<br><small>";
        if ($devStats['Permissions'] == Permissions::JuniorDeveloper) {
            echo Permissions::toString(Permissions::JuniorDeveloper);
        } elseif ($devStats['Permissions'] <= Permissions::JuniorDeveloper) {
            echo "Inactive";
        }
        echo "</small>";
        echo "</div></td>";
        echo "<td class='text-right'><a href='/ticketmanager.php?u=" . $devStats['Author'] . "'>" . $devStats['OpenTickets'] . "</a></td>";
        echo "<td class='text-right'><a href='" . route('developer.sets', $devStats['Author']) . "'>" . localized_number($devStats['Achievements']) . "</a></td>";
        echo "<td class='text-right'><a href='/ticketmanager.php?r=" . $devStats['Author'] . "&t=" . $resolvedForOthersTicketFilter . "'>" . localized_number($devStats['TicketsResolvedForOthers']) . "</a></td>";
        echo "<td class='text-right'>" . localized_number($devStats['ContribCount']) . "</td>";
        echo "<td class='text-right'>" . localized_number($devStats['ContribYield']) . "</td>";
        echo "<td class='text-right'><a href='/claimlist.php?u=" . $devStats['Author'] . "'>" . $devStats['ActiveClaims'] . "</a></td>";
    }
    echo "</tbody></table></div>";
    ?>

    @if ($previousPageHref || $nextPageHref)
        <div class="flex flex-col gap-y-4 items-center justify-between sm:flex-row sm:justify-end md:gap-x-4">
            <div class="flex gap-x-4 items-center">
                @if ($previousPageHref)
                    <a class="btn transition-transform lg:active:scale-95" href="{{ $previousPageHref }}">< Previous</a>
                @endif

                <p>Viewing Page {{ $currentPage }} of {{ $totalPages }}</p>

                @if ($nextPageHref)
                    <a class="btn transition-transform lg:active:scale-95" href="{{ $nextPageHref }}">Next ></a>
                @endif
            </div>
        </div>
    @endif
</x-app-layout>
