<?php

use App\Platform\Models\System;

/*
 *  API_GetConsoleIDs - returns mapping of known consoles
 *
 *  array
 *   object    [value]
 *    string    ID                  unique identifier of the console
 *    string    Name                name of the console
 */

return response()->json(System::select(['ID', 'Name'])->get());
