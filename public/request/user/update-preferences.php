<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($username, $permissions, $userDetails)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'preferences' => 'required|integer',
]);

/** @var User $user */
$user = request()->user();
$user->websitePrefs = $input['preferences'];
$user->save();

return response()->json(['message' => __('legacy.success.ok')]);
