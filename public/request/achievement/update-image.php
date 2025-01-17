<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
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
if ($permissions === Permissions::JuniorDeveloper && $user !== $achievement->developer?->User) {
    return back()->withErrors(__('legacy.error.permissions'));
}

try {
    $imagePath = UploadBadgeImage($_FILES['file']);
} catch (Exception) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

$achievement->BadgeName = $imagePath;
if (!$achievement->save()) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

$userModel = User::whereName($user)->first();

addArticleComment(
    'Server',
    ArticleType::Achievement,
    $achievementId,
    "{$userModel->display_name} edited this achievement's badge.",
    $userModel->display_name
);

return back()->with('success', __('legacy.success.image_upload'));
