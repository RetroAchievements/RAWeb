<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Site\Enums\Permissions;

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
if (!$achievement) {
    abort(400);
}

$currentVideoUrl = $achievement['AssocVideo'];

// Only allow jr. devs to update achievement embed if they are the author and the achievement is not core/official
if (
    $permissions == Permissions::JuniorDeveloper &&
    ($user != $achievement['Author'] || $achievement['Flags'] == AchievementType::OfficialCore)
) {
    abort(401);
}

function buildAuditLogMessage(string $user, ?string $currentVideoUrl, ?string $embedUrl): string {
    if ($currentVideoUrl && $embedUrl) {
        return "$user changed this achievement's embed URL from $currentVideoUrl to $embedUrl.";
    } elseif ($currentVideoUrl && !$embedUrl) {
        return "$user changed this achievement's embed URL from $currentVideoUrl to no embed URL.";
    }

    return "$user set this achievement's embed URL to $embedUrl.";
}

if (updateAchievementEmbedVideo($achievementId, $embedUrl)) {
    $auditLog = buildAuditLogMessage($user, $currentVideoUrl, $embedUrl);
    addArticleComment('Server', ArticleType::Achievement, $achievementId, $auditLog, $user);

    return response()->json(['message' => __('legacy.success.ok')]);
}

abort(400);
