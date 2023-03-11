<?php

use LegacyApp\Community\Enums\ClaimFilters;
use LegacyApp\Community\Enums\ClaimSorting;

$claimData = getFilteredClaims(
    null,
    ClaimFilters::AllCompletedPrimaryClaims,
    ClaimSorting::FinishedDateDescending,
    false,
    null,
    0,
    $count
);

$allFinishedClaimsHref = '/claimlist.php?s=' . ClaimSorting::FinishedDateDescending . '&f=' . ClaimFilters::AllCompletedPrimaryClaims;
?>

<div class="component">
    <h3>New Sets/Revisions</h3>

    @if(!$claimData || count($claimData) === 0)
        <div class="h-40 w-full flex flex-col items-center justify-center">
            <img src="assets/images/cheevo/thinking.webp" alt="No new sets/revisions">
            <p>Couldn't find any new sets/revisions.</p>
        </div>
    @else
        <div>
            <table class="table-highlight mb-1">
                <thead>
                    <tr class="do-not-highlight">
                        <th colspan="2">Game</th>
                        <th colspan="2">Dev</th>
                        <th>Type</th>
                        <th>Finished</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($claimData as $claim)
                        @if (isValidConsoleId($claim['ConsoleID']))
                            <x-claim-finished-table-row :claim="$claim" />
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-right">
            <a class="btn btn-link" href="{{ $allFinishedClaimsHref }}">more...</a>
        </div>
    @endif
</div>