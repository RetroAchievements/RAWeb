<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'username' => 'required',
]);

$targetUser = User::firstWhere('User', $input['username']);

if (!$targetUser->isBanned()) {
    RequestPasswordReset($targetUser);
}

return back()->with('message', __('legacy.email_check'));
