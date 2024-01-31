<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($username, $permissions, $userDetail)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'email' => 'required|email|confirmed|min:8|different:username',
]);

$email = $input['email'];

$dbResult = s_mysql_query(
    "UPDATE UserAccounts SET EmailAddress='$email', Permissions=" . Permissions::Unregistered . ", Updated=NOW() WHERE User='$username'"
);

if (!$dbResult) {
    return back()->withErrors(__('legacy.error.error'));
}

sendValidationEmail($username, $email);

addArticleComment('Server', ArticleType::UserModeration, $userDetail['ID'],
    $username . ' changed their email address'
);

return back()->with('success', __('legacy.success.change'));
