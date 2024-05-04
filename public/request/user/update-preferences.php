<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'preferences' => 'required|integer',
]);

$user->websitePrefs = $input['preferences'];
$user->save();

return response()->json(['message' => __('legacy.success.ok')]);
