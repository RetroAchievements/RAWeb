<?php

/*
 *  API_GetUserWantToPlayList - returns a list of GameIDs that a user has saved on their WantToPlayList
 *    u : username
 *
 *  array
 *   int     GameID                id of the game 
 */

$user = request()->query('u');

return response()->json(
    getUserWantToPlayList(
        username: $username
    )
);
