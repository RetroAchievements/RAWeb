<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer|exists:mysql_legacy.Achievements,ID',
    'file' => 'image',
]);

$achievementId = (int) $input['achievement'];
$achievement = Achievement::find($achievementId);
if (!$achievement) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

// Only allow jr. devs to update achievement image if they are the author
if ($permissions == Permissions::JuniorDeveloper && $user != $achievement['Author']) {
    return back()->withErrors(__('legacy.error.permissions'));
}

try {
    $imagePath = UploadBadgeImage($_FILES['file']);
} catch (Exception) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

$dbResult = legacyDbStatement('UPDATE Achievements AS a SET BadgeName=:badgeName WHERE a.ID = :achievementId', [
    'achievementId' => $achievementId,
    'badgeName' => $imagePath,
]);
if (!$dbResult) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

addArticleComment('Server', ArticleType::Achievement, $achievementId, "$user edited this achievement's badge.", $user);

return back()->with('success', __('legacy.success.image_upload'));
