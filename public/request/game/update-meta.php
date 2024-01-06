<?php

use App\Community\Enums\ClaimSetType;
use App\Platform\Actions\TrimGameMetadata;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'developer' => 'nullable|string|max:50',
    'publisher' => 'nullable|string|max:50',
    'genre' => 'nullable|string|max:50',
    'release' => 'nullable|string|max:50',
    'guide_url' => [
        'nullable',
        'active_url',
        'regex:/^https?:\/\/(www\.)?github\.com\/RetroAchievements\/guides\//i',
    ],
], [
    'guide_url.regex' => 'The guide URL must be from https://github.com/RetroAchievements/guides/.',
]);

$gameId = (int) $input['game'];

// Only allow jr. devs if they are the sole author of the set or have the primary claim
if ($permissions === Permissions::JuniorDeveloper && (!checkIfSoleDeveloper($user, $gameId) && !hasSetClaimed($user, $gameId, true, ClaimSetType::NewSet))) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (modifyGameData($user, $gameId, TrimGameMetadata::trimWhitespace($input['developer']),
    TrimGameMetadata::trimWhitespace($input['publisher']), TrimGameMetadata::trimWhitespace($input['genre']),
    TrimGameMetadata::trimWhitespace($input['release']), TrimGameMetadata::trimWhitespace($input['guide_url']))
) {
    return back()->with('success', __('legacy.success.update'));
}

return back()->withErrors(__('legacy.error.error'));
