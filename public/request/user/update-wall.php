<?php

use RA\ArticleType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (!ValidatePOSTChars("t")) {
    header("Location: " . config('app.url') . "?e=invalidparams");
    exit;
}

$prefType = requestInputPost('t');
$value = requestInputPost('v', 0, 'integer');

$db = getMysqliConnection();

if ($prefType == 'wall') {
    $query = "UPDATE UserAccounts
            SET UserWallActive=$value, Updated=NOW()
            WHERE User='$user'";

    $dbResult = mysqli_query($db, $query);
    if (!$dbResult) {
        log_sql_fail();

        return back()->withErrors(__('legacy.error.error'));
    }

    return back()->with('success', __('legacy.success.change'));
} else {
    // TODO move to separate request
    if ($prefType == 'cleanwall') {
        $query = "DELETE FROM Comment
                  WHERE ArticleType = " . ArticleType::User . " && ArticleID = ( SELECT ua.ID FROM UserAccounts AS ua WHERE ua.User = '$user' )";

        $dbResult = mysqli_query($db, $query);
        if (!$dbResult) {
            log_sql_fail();

            return back()->withErrors(__('legacy.error.error'));
        }

        return back()->with('success', __('legacy.success.delete'));
    }
}

return back()->withErrors(__('legacy.error.error'));
