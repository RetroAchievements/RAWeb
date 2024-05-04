<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'motto' => 'nullable|string|max:50',
]);

$newMotto = mb_strcut($input['motto'], 0, 50, "UTF-8");

$user->Motto = $newMotto;
$user->save();

return back()->with('success', __('legacy.success.change'));
