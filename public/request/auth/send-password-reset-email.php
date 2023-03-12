<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->post()), [
    'username' => 'required',
]);

RequestPasswordReset($input['username']);

return back()->with('message', __('legacy.email_check'));
