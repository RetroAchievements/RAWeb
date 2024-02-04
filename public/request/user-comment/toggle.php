<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'active' => 'sometimes|boolean',
]);

$value = (int) ($input['active'] ?? false);

$db = getMysqliConnection();
$query = "UPDATE UserAccounts
        SET UserWallActive=$value, Updated=NOW()
        WHERE User='$user'";

$dbResult = mysqli_query($db, $query);
if (!$dbResult) {
    log_sql_fail();

    return back()->withErrors(__('legacy.error.error'));
}

return back()->with('success', __('legacy.success.change'));
