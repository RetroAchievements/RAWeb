<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer|exists:achievements,id',
    'video' => 'nullable|url',
]);

$achievementId = (int) $input['achievement'];
$embedUrl = $input['video'];

$achievement = Achievement::find($achievementId);

$currentVideoUrl = $achievement->embed_url;

// Only allow jr. devs to update achievement embed if they are the author and the achievement is not core/official
if (
    $permissions === Permissions::JuniorDeveloper
    && ($user !== $achievement->developer?->User || $achievement->is_published)
) {
    abort(403);
}

$userModel = User::whereName($user)->first();

$achievement->embed_url = strip_tags($embedUrl);
$achievement->save();

$auditLog = "{$userModel->display_name} set this achievement's embed URL.";

addArticleComment('Server', ArticleType::Achievement, $achievementId, $auditLog, $userModel->display_name);

return response()->json(['message' => __('legacy.success.ok')]);
