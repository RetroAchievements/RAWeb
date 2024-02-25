<?php

use App\Models\System;
/*
 *  API_GetConsoleIDs - returns mapping of known consoles
 *
 *  array
 *   object    [value]
 *    string    ID                  unique identifier of the console
 *    string    Name                name of the console
 *    string    IconURL             site-relative path to the console icon
 */

$systems = System::select()->get();

$response = [];

foreach ($systems as $system) {
    $data = [
        'ID' => $system['ID'],
        'Name' => $system['Name'],
        'IconURL' => '/' . $system->getIconUrlPath(),
    ];
    $response[] = $data;
}

return response()->json($response);
