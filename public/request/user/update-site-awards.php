<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Enums\AwardType;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'awards.*.type' => 'required|integer',
    'awards.*.data' => 'required|integer',
    'awards.*.extra' => 'required|integer',
    'awards.*.number' => 'required|integer',
]);

$awards = $input['awards'];

foreach ($awards as $award) {
    $awardType = $award['type'];
    $awardData = $award['data'];
    $awardDataExtra = $award['extra'];
    $value = $award['number'];

    // Change display order for all entries if it's a "stacking" award type.
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
}

$userAwards = getUsersSiteAwards($user);
$updatedAwardsHTML = '';
ob_start();
RenderSiteAwards($userAwards);
$updatedAwardsHTML = ob_get_clean();

return response()->json([
    'message' => __('legacy.success.ok'),
    'updatedAwardsHTML' => $updatedAwardsHTML,
]);
