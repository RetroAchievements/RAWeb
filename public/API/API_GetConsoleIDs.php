<?php

use App\Models\System;

/*
 *  API_GetConsoleIDs - returns mapping of known consoles
 *    a : active - 1 for only active systems, 0 for all (default: 0)
 *    g : only game systems - 1 for only game systems, 0 for all system types (Events, Hubs, etc) (default: 0)
 *
 *  array
 *   object    [value]
 *    string    ID                  unique identifier of the console
 *    string    Name                name of the console
 *    string    IconURL             system icon URL
 *    bool      Active              boolean value indicating if the console is active in RA
 *    bool      IsGameSystem        boolean value indicating if is a game system (not Events, Hubs, etc.)
 */

$activeFlag = (int) request()->query('a', '0');
$gamesConsoleFlag = (int) request()->query('g', '0');

$systems = getSystemsData($activeFlag, $gamesConsoleFlag);

$response = $systems->map(fn ($system) => [
        'ID' => $system->ID,
        'Name' => $system->Name,
        'IconURL' => $system->icon_url,
        'Active' => boolval($system->active),
        'IsGameSystem' => System::isGameSystem($system->ID),
    ]
);

return response()->json($response);
