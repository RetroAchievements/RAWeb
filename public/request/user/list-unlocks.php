<?php

use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Unregistered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer',
]);

getUserUnlocksDetailed($user, $input['game'], $dataOut);

$hardcoreUnlocks = (new Collection($dataOut))
    ->filter(fn ($achievement) => (bool) $achievement['HardcoreMode'])
    ->keyBy('ID');

$dataOut = (new Collection($dataOut))
    // results in unique IDs
    ->keyBy('ID')
    ->filter(fn ($achievement) => !$achievement['HardcoreMode'])
    // merge on top to make sure hardcore unlocks take precedence
    ->merge($hardcoreUnlocks)
    ->map(function ($achievement) {
        $achievement['HardcoreMode'] = (int) $achievement['HardcoreMode'];

        return $achievement;
    })
    ->values();

return response()->json($dataOut);
