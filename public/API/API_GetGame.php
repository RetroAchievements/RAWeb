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
    'Title' => $game->title,
    'GameTitle' => $game->title,
    'ConsoleID' => $game->system_id,
    'ConsoleName' => $game->system->name,
    'Console' => $game->system->name,
    'ForumTopicID' => $game->forum_topic_id,
    'Flags' => 0, // Always '0'
    'GameIcon' => $game->image_icon_asset_path,
    'ImageIcon' => $game->image_icon_asset_path,
    'ImageTitle' => $game->image_title_asset_path,
    'ImageIngame' => $game->image_ingame_asset_path,
    'ImageBoxArt' => $game->image_box_art_asset_path,
    'Publisher' => $game->publisher,
    'Developer' => $game->developer,
    'Genre' => $game->genre,
    'Released' => $game->released_at?->format('Y-m-d'),
    'ReleasedAtGranularity' => $game->released_at_granularity?->value,
]);
