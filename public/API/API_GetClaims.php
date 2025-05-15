<?php

/*
 *  API_GetClaims - returns information about 1000 max set claims, sorted by latest `Created` date.
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 100, max: 500)
 *    k : claim kind - 1 for completed, 2 for dropped, 3 for expired (default: 1)
 *  int        Count                number of claims returned in the response
 *  int        Total                number of claims that actually exist
 *  array      Results
 *   object     [value]
 *    int        ID                 unique ID of the claim
 *    string     User               user who made the claim
 *    string     ULID               queryable stable unique identifier of the user
 *    int        GameID             id of the claimed game
 *    string     GameTitle          title of the claimed game
 *    string     GameIcon           site-relative path to the game's icon image
 *    string     ConsoleName        console name of the claimed game
 *    int        ClaimType          claim type: 0 - primary, 1 - collaboration
 *    int        SetType            set type claimed: 0 - new set, 1 - revision
 *    int        Status             claim status: 0 - active, 1 - complete, 2 - dropped
 *    int        Extension          number of times the claim has been extended
 *    int        Special            flag indicating a special type of claim
 *    string     Created            date the claim was made
 *    string     DoneTime           date the claim is done
 *                                    Expiration date for active claims
 *                                    Completion date for complete claims
 *                                    Dropped date for dropped claims
 *    string     Updated            date the claim was updated
 *    int        UserIsJrDev        0 - user is not a junior dev, 1 - user is a junior dev
 *    int        MinutesLeft        time in minutes left until the claim expires
 */

use App\Community\Enums\ClaimFilters;
use App\Models\AchievementSetClaim;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'k' => [
        'nullable',
        Rule::in(['1', '2', '3']),
    ],
    'o' => 'nullable|integer|min:0',
    'c' => 'nullable|integer|min:1|max:500',
], [
    'k.in' => 'k must be set to one of the following values: :values',
]);

$completedClaims = '1';
$droppedClaims = '2';
$expiredClaims = '3';

$claimKind = request()->query('k', $completedClaims);
$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

$claimFilter = ClaimFilters::AllCompletedPrimaryClaims;
if ($claimKind === $droppedClaims) {
    $claimFilter = ClaimFilters::AllDroppedClaims;
} elseif ($claimKind === $expiredClaims) {
    $claimFilter = ClaimFilters::AllActiveClaims;
}

$claimsResults = getFilteredClaims(
    claimFilter: $claimFilter,
    offset: $offset,
    limit: $count
);

if ($claimKind === $expiredClaims) {
    // For expired claims, we need to fetch all results first to filter by MinutesLeft < 0.
    $allResults = getFilteredClaims(claimFilter: $claimFilter);

    $onlyFullyExpired = $allResults->filter(function ($claim) {
        return $claim['MinutesLeft'] < 0;
    })->values();

    $totalExpiredClaims = $onlyFullyExpired->count();

    // Apply pagination manually after filtering.
    $paginatedResults = $onlyFullyExpired->slice($offset, $count)->values();

    return response()->json([
        'Count' => count($paginatedResults),
        'Total' => $totalExpiredClaims,
        'Results' => $paginatedResults->toArray(),
    ]);
}

$totalClaims = 0;
if ($claimFilter === ClaimFilters::AllCompletedPrimaryClaims) {
    $totalClaims = AchievementSetClaim::complete()->primaryClaim()->count();
} elseif ($claimFilter === ClaimFilters::AllDroppedClaims) {
    $totalClaims = AchievementSetClaim::dropped()->count();
}

return response()->json([
    'Count' => count($claimsResults),
    'Total' => $totalClaims,
    'Results' => $claimsResults->toArray(),
]);
