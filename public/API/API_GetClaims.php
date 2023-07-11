<?php

use App\Community\Enums\ClaimFilters;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/*
 *  API_GetClaims - returns information about 1000 max set claims, sorted by latest `Created` date.
 *    k : claim kind - 1 for completed, 2 for dropped, 3 for expired (default: 1)
 *
 *  array
 *   object     [value]
 *    int        ID                 unique ID of the claim
 *    string     User               user who made the claim
 *    int        GameID             id of the claimed game
 *    string     GameTitle          title of the claimed game
 *    string     GameIcon           site-relative path to the game's icon image
 *    string     ConsoleName        console name of the claimed game
 *    int        ClaimType          claim type: 0 - primary, 1 - collaboration
 *    int        SetType            set type claimed: 0 - new set, 1 - revision
 *    int        Status             claim status: 0 - active, 1 - complete, 2 - dropped
 *    int        Extension          number of thes the claim has been extended
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

$input = Validator::validate(Arr::wrap(request()->query()), [
   'k' => [
       'nullable',
       Rule::in(['1', '2', '3']),
   ],
], [
   'k.in' => 'k must be set to one of the following values: :values',
]);

$completedClaims = '1';
$droppedClaims = '2';
$expiredClaims = '3';

$claimKind = request()->query('k', $completedClaims);

$claimFilter = ClaimFilters::AllCompletedPrimaryClaims;
if ($claimKind === $droppedClaims) {
    $claimFilter = ClaimFilters::AllDroppedClaims;
} elseif ($claimKind === $expiredClaims) {
    $claimFilter = ClaimFilters::AllActiveClaims;
}

$claimsResults = getFilteredClaims(claimFilter: $claimFilter);

if ($claimKind === $expiredClaims) {
    $onlyFullyExpired = $claimsResults->filter(function ($claim) {
        return $claim['MinutesLeft'] < 0;
    })->toArray();

    return response()->json($onlyFullyExpired);
}

return response()->json($claimsResults);
