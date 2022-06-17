<?php

use Illuminate\Support\Facades\Validator;

$input = Validator::validate(request()->query(), [
    'v' => 'required',
], customAttributes: [
    'v' => 'token',
]);

if (validateEmailVerificationToken($input['v'], $user)) {
    redirect(route('home'))->with('success', __('legacy.success.email_validate'));
}

return redirect(route('home'))->withErrors(__('legacy.error.token'));
