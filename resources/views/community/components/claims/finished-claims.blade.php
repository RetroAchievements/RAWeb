<?php

use App\Community\Enums\ClaimFilters;
use App\Community\Enums\ClaimSorting;

$desiredClaimCount = $count;
$claimData = [];
$remainingClaimsToFetch = $desiredClaimCount;
$currentOffset = 0;

// Continue fetching claims until the desired number of claims
// have been added to `$claimData`.
while ($remainingClaimsToFetch > 0) {
    $currentClaimsBatch = getFilteredClaims(
        claimFilter: ClaimFilters::AllCompletedPrimaryClaims,
        sortType: ClaimSorting::FinishedDateDescending,
        offset: $currentOffset,
        limit: $count,
    );

    if ($currentClaimsBatch->isEmpty()) {
        break;
    }

    foreach ($currentClaimsBatch as $batchedClaim) {
        if (isValidConsoleId($batchedClaim['ConsoleID'])) {
            $claimData[] = $batchedClaim;
            $remainingClaimsToFetch--;

            if ($remainingClaimsToFetch === 0) {
                break;
            }
        }
    }

    $currentOffset += $count;
}

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
        <div class="overflow-x-auto sm:overflow-x-hidden">
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
                        <x-claims.finished-claim-table-row :claim="$claim" />
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-right">
            <a class="btn btn-link" href="{{ $allFinishedClaimsHref }}">more...</a>
        </div>
    @endif
</div>
