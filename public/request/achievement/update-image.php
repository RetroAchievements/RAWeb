<?php

use RA\ArticleType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$achievementID = (int) requestInputPost('i', 0);

$achievement = GetAchievementData($achievementID);
if (!$achievement) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

if ($permissions == Permissions::JuniorDeveloper && !checkIfSoleDeveloper($user, $achievement['GameID'])) {
    return back()->withErrors(__('legacy.error.permissions'));
}

try {
    $imagePath = UploadBadgeImage($_FILES['file']);
} catch (Exception $exception) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

$db = getMysqliConnection();
$dbResult = mysqli_query($db, "UPDATE Achievements AS a SET BadgeName='$imagePath' WHERE a.ID = $achievementID");
if (!$dbResult) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

addArticleComment('Server', ArticleType::Achievement, $achievementID, "$user edited this achievement's badge.", $user);

return back()->with('success', __('legacy.success.image_upload'));
