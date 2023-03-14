<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LegacyApp\Community\Enums\ClaimFilters;

/*
 *  API_GetClaims - returns information about 1000 max set claims.
 *    k : claim kind - 1 for completed, 2 for dropped, 3 for expired (default: 1)
 *
 *  array
 *   object     [value]
 *    string     ID                 unique ID of the claim
 *    string     User               user who made the claim
 *    string     GameID             id of the claimed game
 *    string     GameTitle          title of the claimed game
 *    string     GameIcon           site-relative path to the game's icon image
 *    string     ConsoleName        console name of the claimed game
 *    string     ClaimType          claim type: 0 - primary, 1 - collaboration
 *    string     SetType            set type claimed: 0 - new set, 1 - revision
 *    string     Status             claim status: 0 - active, 1 - complete, 2 - dropped
 *    string     Extension          number of thes the claim has been extended
 *    string     Special            flag indicating a special type of claim
 *    string     Created            date the claim was made
 *    string     DoneTime           date the claim is done
 *                                    Expiration date for active claims
 *                                    Completion date for complete claims
 *                                    Dropped date for dropped claims
 *    string     Updated            date the claim was updated
 *    string     MinutesLeft        time in minutes left until the claim expires
 */

 $input = Validator::validate(Arr::wrap(request()->query()), [
    'k' => [
        'nullable',
        Rule::in([1, 2, 3]),
    ],
], [
    'k.in' => 'k must be set to one of the following values: :values',
]);

$completedClaims = 1;
$droppedClaims = 2;
$expiredClaims = 3;

$claimKind = (int) request()->query('k', $completedClaims);

$claimFilter = ClaimFilters::AllCompletedPrimaryClaims;
if ($claimKind === $droppedClaims) {
    $claimFilter = ClaimFilters::AllDroppedClaims;
} elseif ($claimKind === $expiredClaims) {
    $claimFilter = ClaimFilters::AllActiveClaims;
}

return response()->json(
    getFilteredClaims(
        claimFilter: $claimFilter,
    )
);
