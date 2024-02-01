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
 *  string     Released                   release date information for the game
 */

use App\Platform\Models\Game;

$gameID = (int) request()->query('i');

$gameSystem = Game::find($gameID)->system->Name;
$gameData = collect([Game::find($gameID)])->map(function ($gd) use ($gameID, $gameSystem) {
    return [
        'Title' => $gd->Title,
        'GameTitle' => $gd->Title,
        'ConsoleID' => $gd->ConsoleID,
        'ConsoleName' => $gameSystem,
        'Console' => $gameSystem,
        'ForumTopicID' => $gd->ForumTopicID,
        'Flags' => (int) 0, // Always '0'
        'GameIcon' => $gd->ImageIcon,
        'ImageIcon' => $gd->ImageIcon,
        'ImageTitle' => $gd->ImageTitle,
        'ImageIngame' => $gd->ImageIngame,
        'ImageBoxArt' => $gd->ImageBoxArt,
        'Publisher' => $gd->Publisher,
        'Developer' => $gd->Developer,
        'Genre' => $gd->Genre,
        'Released' => $gd->Released,
    ];
})->first();

if ($gameID === null) {
    return response()->json();
}

return response()->json($gameData);