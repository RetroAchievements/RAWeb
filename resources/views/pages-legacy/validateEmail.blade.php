<?php

// TODO migrate to Fortify

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'v' => 'required',
], [], [
    'v' => 'token',
]);

if (validateEmailVerificationToken($input['v'], $user)) {
    return redirect(route('home'))->with('success', __('legacy.success.email_verify'));
}

return redirect(route('home'))->withErrors(__('legacy.error.token'));
