<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Achievement;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer|exists:Achievements,ID',
    'file' => 'image',
]);

$achievementId = (int) $input['achievement'];
$achievement = Achievement::find($achievementId);
if (!$achievement) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

// Only allow jr. devs to update achievement image if they are the author
// TODO use a policy
if ($permissions == Permissions::JuniorDeveloper && $user->id !== $achievement->user_id) {
    return back()->withErrors(__('legacy.error.permissions'));
}

try {
    $imagePath = UploadBadgeImage($_FILES['file']);
} catch (Exception) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

$dbResult = legacyDbStatement('UPDATE Achievements AS a SET BadgeName=:badgeName WHERE a.ID = :achievementId', [
    'achievementId' => $achievement->id,
    'badgeName' => $imagePath,
]);
if (!$dbResult) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

addArticleComment(
    'Server',
    ArticleType::Achievement,
    $achievementId,
    "{$user->display_name} edited this achievement's badge.",
    $user->username,
);

return back()->with('success', __('legacy.success.image_upload'));
