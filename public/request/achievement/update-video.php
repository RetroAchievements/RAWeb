<?php

use App\Community\Enums\ArticleType;
use App\Platform\Enums\AchievementType;
use App\Platform\Models\Achievement;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    abort(401);
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'achievement' => 'required|integer|exists:mysql_legacy.Achievements,ID',
    'video' => 'nullable|url',
]);

$achievementId = (int) $input['achievement'];
$embedUrl = $input['video'];

$achievement = Achievement::find($achievementId);

$currentVideoUrl = $achievement['AssocVideo'];

// Only allow jr. devs to update achievement embed if they are the author and the achievement is not core/official
if (
    $permissions == Permissions::JuniorDeveloper &&
    ($user != $achievement['Author'] || $achievement['Flags'] == AchievementType::OfficialCore)
) {
    abort(401);
}

if (updateAchievementEmbedVideo($achievementId, $embedUrl)) {
    $embedUrlText = $embedUrl === null ? 'null' : $embedUrl;
    $auditLog = "$user set this achievement's embed URL to $embedUrlText.";

    addArticleComment('Server', ArticleType::Achievement, $achievementId, $auditLog, $user);

    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
