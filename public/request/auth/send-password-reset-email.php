<?php

use Illuminate\Support\Facades\Validator;

$input = Validator::validate(request()->post(), [
    'username' => 'required',
]);

RequestPasswordReset($input['username']);

return back()->with('message', __('legacy.email_check'));
