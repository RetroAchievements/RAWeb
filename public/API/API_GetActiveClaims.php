<?php

use App\Community\Enums\ClaimFilters;

/*
 *  API_GetActiveClaims - returns information about all (1000 max) active set claims.
 *
 *  array
 *   object     [value]
 *    int        ID                 unique ID of the claim
 *    string     User               user who made the claim
 *    int        GameID             id of the claimed game
 *    string     GameTitle          title of the claimed game
 *    string     GameIcon           site-relative path to the game's icon image
 *    int        ConsoleID          console id of the claimed game
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

return response()->json(
    getFilteredClaims(
        claimFilter: ClaimFilters::AllActiveClaims,
    )
);
