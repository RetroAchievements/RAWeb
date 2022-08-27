<?php

use Illuminate\Support\Facades\Validator;

$input = Validator::validate(request()->query(), [
    'v' => 'required',
], customAttributes: [
    'v' => 'token',
]);

if (validateEmailVerificationToken($input['v'], $user)) {
    return redirect(route('home'))->with('success', __('legacy.success.email_verify'));
}

return redirect(route('home'))->withErrors(__('legacy.error.token'));
