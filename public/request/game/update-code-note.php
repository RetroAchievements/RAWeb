<?php

use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'gameId' => 'required|integer',
    'address' => 'required|integer',
    'note' => 'required|string',
]);

$gameId = $input['gameId'];
$address = $input['address'];
$note = $input['note'];

$success = submitCodeNote2($user, $gameId, $address, $note);

if (!$success) {
    abort(400);
}

return response()->json(['message' => __('legacy.success.ok')]);
