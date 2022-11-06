<?php

use Illuminate\Support\Facades\Validator;
use RA\ArticleType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'achievement' => 'required|integer|exists:mysql_legacy.Achievements,ID',
    'file' => 'image',
]);

$achievementID = (int) $input['achievement'];

$achievement = GetAchievementData($achievementID);
if (!$achievement) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

if ($permissions == Permissions::JuniorDeveloper && !checkIfSoleDeveloper($user, $achievement['GameID'])) {
    return back()->withErrors(__('legacy.error.permissions'));
}

try {
    $imagePath = UploadBadgeImage($_FILES['file']);
} catch (Exception) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

$db = getMysqliConnection();
$dbResult = mysqli_query($db, "UPDATE Achievements AS a SET BadgeName='$imagePath' WHERE a.ID = $achievementID");
if (!$dbResult) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

addArticleComment('Server', ArticleType::Achievement, $achievementID, "$user edited this achievement's badge.", $user);

return back()->with('success', __('legacy.success.image_upload'));
