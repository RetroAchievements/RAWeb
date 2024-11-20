<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer|exists:Achievements,ID',
    'video' => 'nullable|url',
]);

$achievementId = (int) $input['achievement'];
$embedUrl = $input['video'];

$achievement = Achievement::find($achievementId);

$currentVideoUrl = $achievement->AssocVideo;

// Only allow jr. devs to update achievement embed if they are the author and the achievement is not core/official
if (
    $permissions === Permissions::JuniorDeveloper
    && ($user !== $achievement->developer?->User || $achievement->Flags === AchievementFlag::OfficialCore->value)
) {
    abort(403);
}

$achievement->AssocVideo = strip_tags($embedUrl);
$achievement->save();

$auditLog = "$user set this achievement's embed URL.";

addArticleComment('Server', ArticleType::Achievement, $achievementId, $auditLog, $user);

return response()->json(['message' => __('legacy.success.ok')]);
