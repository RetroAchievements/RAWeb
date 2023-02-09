<?php

use LegacyApp\Community\Enums\ClaimFilters;
use LegacyApp\Community\Enums\ClaimSorting;

/*
 *  API_GetUserClaims - returns information about a all users set claims
 *    u : username
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

$user = request()->query('u');

return response()->json(
    getFilteredClaims(
        0,
        ClaimFilters::AllFilters,
        ClaimSorting::ClaimDateDescending,
        false,
        $user
    )
);
