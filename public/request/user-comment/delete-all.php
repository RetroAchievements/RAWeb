<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$db = getMysqliConnection();
$query = "DELETE FROM Comment
          WHERE ArticleType = " . ArticleType::User . " && ArticleID = ( SELECT ua.ID FROM UserAccounts AS ua WHERE ua.User = '$user' )";

$dbResult = mysqli_query($db, $query);
if (!$dbResult) {
    log_sql_fail();

    return back()->withErrors(__('legacy.error.error'));
}

return back()->with('success', __('legacy.success.delete'));
