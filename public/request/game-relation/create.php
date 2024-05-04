<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'relations' => 'required|string',
]);

$gameId = (int) $input['game'];

// Filter out instances where a game might be linked to itself.
$relationsArray = explode(",", $input['relations']);
$filteredArray = array_diff($relationsArray, [$input['game']]);
$filteredRelationsCsv = implode(",", $filteredArray);

modifyGameAlternatives($user->username, $gameId, toAdd: $filteredRelationsCsv);

return back()->with('success', __('legacy.success.ok'));
