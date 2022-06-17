<?php

use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Unregistered)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer',
]);

getUserUnlocksDetailed($user, $input['game'], $dataOut);

$hardcoreUnlocks = collect($dataOut)
    ->filter(fn ($achievement) => (bool) $achievement['HardcoreMode'])
    ->keyBy('ID');

$dataOut = collect($dataOut)
    // results in unique IDs
    ->keyBy('ID')
    // merge on top to make sure hardcore unlocks take precedence
    ->merge($hardcoreUnlocks)
    ->map(function ($achievement) {
        $achievement['HardcoreMode'] = (int) $achievement['HardcoreMode'];

        return $achievement;
    })
    ->values();

return response()->json($dataOut);
