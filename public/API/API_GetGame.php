<?php

/*
 *  API_GetGame - returns information about a game
 *    i : game id
 *
 *  string     Title                      name of the game
 *  string     GameTitle                  name of the game
 *  int        ConsoleID                  unique identifier of the console associated to the game
 *  string     ConsoleName                name of the console associated to the game
 *  string     Console                    name of the console associated to the game
 *  int        ForumTopicID               unique identifier of the official forum topic for the game
 *  int        Flags                      always "0"
 *  string     GameIcon                   site-relative path to the game's icon image
 *  string     ImageIcon                  site-relative path to the game's icon image
 *  string     ImageTitle                 site-relative path to the game's title image
 *  string     ImageIngame                site-relative path to the game's in-game image
 *  string     ImageBoxArt                site-relative path to the game's box art image
 *  string     Publisher                  publisher information for the game
 *  string     Developer                  developer information for the game
 *  string     Genre                      genre information for the game
 *  string?    Released                   a YYYY-MM-DD date of the game's earliest release date, or null. also see ReleasedAtGranularity.
 *  string?    ReleasedAtGranularity      how precise the Released value is. possible values are "day", "month", "year", and null.
 */

use App\Models\Game;

$gameId = (int) request()->query('i');

$game = Game::with('system')->find($gameId);

if (!$game) {
    return response()->json();
}

return response()->json([
    'Title' => $game->Title,
    'GameTitle' => $game->Title,
    'ConsoleID' => $game->ConsoleID,
    'ConsoleName' => $game->system->Name,
    'Console' => $game->system->Name,
    'ForumTopicID' => $game->ForumTopicID,
    'Flags' => 0, // Always '0'
    'GameIcon' => $game->ImageIcon,
    'ImageIcon' => $game->ImageIcon,
    'ImageTitle' => $game->ImageTitle,
    'ImageIngame' => $game->ImageIngame,
    'ImageBoxArt' => $game->ImageBoxArt,
    'Publisher' => $game->Publisher,
    'Developer' => $game->Developer,
    'Genre' => $game->Genre,
    'Released' => $game->released_at?->format('Y-m-d'),
    'ReleasedAtGranularity' => $game->released_at_granularity?->value,
]);
