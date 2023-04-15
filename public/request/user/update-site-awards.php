<?php

use App\Community\Enums\AwardType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'awards.*' => ['required', 'string', 'regex:/^(\d+),(\d+),(\d+),(-?\d+)$/'],
], [
    'awards.*.regex' => 'The :attribute must be 4 comma-separated integers, with the 4th value allowing a negative integer.',
]);

$compressedAwards = $input['awards'];

// The awards this endpoint receives are compressed to save on variable space.
// Before doing any more work, we'll decompress the awards.
$awards = array_map(function ($award) {
    $decoded = explode(',', $award);

    return [
        'type' => intval($decoded[0]),
        'data' => intval($decoded[1]),
        'extra' => intval($decoded[2]),
        'number' => intval($decoded[3]),
    ];
}, $compressedAwards);

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
