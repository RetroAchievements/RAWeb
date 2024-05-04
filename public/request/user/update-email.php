<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'email' => 'required|email|confirmed|min:8|different:username',
]);

$email = $input['email'];

$user->EmailAddress = $email;
$user->Permissions = Permissions::Unregistered;
$user->email_verified_at = null;
$user->save();

sendValidationEmail($user->username, $email);

addArticleComment(
    'Server',
    ArticleType::UserModeration,
    $user->id,
    "{$user->display_name} changed their email address",
);

return back()->with('success', __('legacy.success.change'));
