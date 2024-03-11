<?php

use App\Models\System;

/*
 *  API_GetConsoleIDs - returns mapping of known consoles
 *
 *  array
 *   object    [value]
 *    string    ID                  unique identifier of the console
 *    string    Name                name of the console
 *    string    IconURL             system icon URL
 */

$systems = System::all()->map(function ($system) {
    return [
        'ID' => $system->ID,
        'Name' => $system->Name,
        'IconURL' => $system->icon_url,
    ];
});

return response()->json($systems);
