<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'motto' => 'nullable|string|max:50',
]);

$newMotto = mb_strcut($input['motto'], 0, 50, "UTF-8");

sanitize_sql_inputs($user, $cookie, $newMotto);

// TODO use model, remove extra sanitization
$query = "
        UPDATE UserAccounts
        SET Motto='$newMotto', Updated=NOW()
        WHERE User='$user'";

$db = getMysqliConnection();
$dbResult = mysqli_query($db, $query);
if (!$dbResult) {
    log_sql_fail();

    return back()->withErrors(__('legacy.error.error'));
}

return back()->with('success', __('legacy.success.change'));
