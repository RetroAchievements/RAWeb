<?php

use RA\ArticleType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetail)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (!ValidatePOSTChars("ef")) {
    header("Location: " . config('app.url') . "/controlpanel.php?e=e_baddata");
    exit;
}

$email = requestInputPost('e');
$email2 = requestInputPost('f');

if ($email !== $email2) {
    header("Location: " . config('app.url') . "/controlpanel.php?e=e_notmatch");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: " . config('app.url') . "/controlpanel.php?e=e_badnewemail");
    exit;
}

$dbResult = s_mysql_query(
    "UPDATE UserAccounts SET EmailAddress='$email', Permissions=" . Permissions::Unregistered . ", Updated=NOW() WHERE User='$user'"
);

if (!$dbResult) {
    return back()->withErrors(__('legacy.error.error'));
}

sendValidationEmail($user, $email);

addArticleComment('Server', ArticleType::UserModeration, $userDetail['ID'],
    $user . ' changed their email address'
);

return back()->with('success', __('legacy.success.change'));
