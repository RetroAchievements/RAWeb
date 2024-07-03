<?php

// TODO migrate to Fortify

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!request()->query('v')) {
    abort_with(redirect(route('home')));
}

$input = Validator::validate(Arr::wrap(request()->query()), [
    'v' => 'required',
], [], [
    'v' => 'token',
]);

if (validateEmailVerificationToken($input['v'], $user)) {
    abort_with(redirect(route('home'))->with('success', __('legacy.success.email_verify')));
}

abort_with(redirect(route('home'))->withErrors(__('legacy.error.token')));
