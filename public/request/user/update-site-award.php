<?php

use Illuminate\Support\Facades\Validator;
use RA\AwardType;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$input = Validator::validate(request()->post(), [
    'type' => 'required|integer',
    'data' => 'required|integer',
    'extra' => 'required|integer',
    'number' => 'required|integer',
]);

$awardType = $input['type'];
$awardData = $input['data'];
$awardDataExtra = $input['extra'];
$value = $input['number'];

/*
 * change display order for all entries if it's a "stacking" award type
 */
if (in_array($awardType, [AwardType::AchievementUnlocksYield, AwardType::AchievementPointsYield])) {
    $query = "UPDATE SiteAwards SET DisplayOrder = $value WHERE User = '$user' " .
        "AND AwardType = $awardType " .
        "AND AwardDataExtra = $awardDataExtra";
} else {
    $query = "UPDATE SiteAwards SET DisplayOrder = $value WHERE User = '$user' " .
        "AND AwardType = $awardType " .
        "AND AwardData = $awardData";
}

if (!s_mysql_query($query)) {
    abort(500);
}

return response()->json(['message' => __('legacy.success.ok')]);
