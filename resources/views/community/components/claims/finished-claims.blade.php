<?php

declare(strict_types=1);

use App\Community\Enums\ClaimFilters;
use App\Community\Enums\ClaimSorting;

/**
 * Creates a unique key based on the given game title and user.
 * This key can then be used for convenient comparison of potential duplicates.
 */
$createClaimKey = function (string $gameTitle, string $user): string {
    return $gameTitle . '_' . $user;
};

/**
 * Compares the created timestamps of two claims to
 * determine their chronological order.
 */
$isOlderClaim = function (array $claim1, array $claim2): bool {
    return strtotime($claim1['Created']) < strtotime($claim2['Created']);
};

/**
 * Replaces an existing claim in the claim data array that is to
 * be rendered in the UI with a new claim. This function is used when
 * a new claim is found to be older than the existing claim (ie- a duplicate).
 */
$updateClaimData = function (array &$claimData, array $existingClaim, array $newClaim): void {
    foreach ($claimData as $index => $claim) {
        if ($claim['ID'] === $existingClaim['ID']) {
            $claimData[$index] = $newClaim;
            break;
        }
    }
};

 /**
  * Processes a batch of claims and updates the unique claims and claim
  * data arrays. This function is responsible for filtering out duplicate
  * claims and claims belonging to consoles that haven't been rolled out yet.
  * Ideally, if duplicates are detected, we want to keep the oldest duplicate.
  */
 $processClaimsBatch = function (
    array &$uniqueClaims,
    array &$claimData,
    array $claimsBatch,
    int &$remainingClaimsToFetch
) use (
    $createClaimKey,
    $isOlderClaim,
    $updateClaimData
): void {
    foreach ($claimsBatch as $batchedClaim) {
        if (isValidConsoleId($batchedClaim['ConsoleID'])) {
            $claimKey = $createClaimKey($batchedClaim['GameTitle'], $batchedClaim['User']);

            if (!isset($uniqueClaims[$claimKey])) {
                $uniqueClaims[$claimKey] = $batchedClaim;
                $claimData[] = $batchedClaim;
                $remainingClaimsToFetch--;
            } else {
                $existingClaim = $uniqueClaims[$claimKey];

                if ($isOlderClaim($batchedClaim, $existingClaim)) {
                    $uniqueClaims[$claimKey] = $batchedClaim;
                    $updateClaimData($claimData, $existingClaim, $batchedClaim);
                }
            }

            if ($remainingClaimsToFetch === 0) {
                break;
            }
        }
    }
};

$desiredClaimCount = (int) $count;
$claimData = [];
$remainingClaimsToFetch = (int) $desiredClaimCount;
$currentOffset = 0;

$uniqueClaims = [];

/**
 * Continue fetching and processing claims until the desired number of
 * unique claims is reached or no more claims are available.
 */
while ($remainingClaimsToFetch > 0) {
    $currentClaimsBatch = getFilteredClaims(
        claimFilter: ClaimFilters::AllCompletedPrimaryClaims,
        sortType: ClaimSorting::FinishedDateDescending,
        offset: $currentOffset,
        limit: (int) $count,
    );

    if ($currentClaimsBatch->isEmpty()) {
        break;
    }

    $processClaimsBatch($uniqueClaims, $claimData, $currentClaimsBatch->toArray(), $remainingClaimsToFetch);
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
