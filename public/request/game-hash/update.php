<?php

use App\Community\Enums\ArticleType;
use App\Platform\Models\GameHash;
use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$input = Validator::make(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'hash' => 'required|string',
    'name' => 'required|string',
    'labels' => 'required|string',
    'internal_patch_url' => 'nullable|url|regex:/github\.com\/RetroAchievements\/RAPatches\/blob\/main\/.*\.zip$/i',
    'source' => 'nullable|url',
])->validate();

$gameId = (int) $input['game'];
$hash = $input['hash'];
$name = $input['name'];
$labels = $input['labels'];
$internalPatchUrl = $input['internal_patch_url'] ?? null;
$sourceUrl = $input['source'] ?? null;

// Retrieve the existing GameHash to compare with the new inputs.
$existingGameHash = GameHash::where('MD5', $hash)->where('GameID', $gameId)->first();

$didUpdate = updateHashDetails(
    $gameId,
    $hash,
    $name,
    $labels,
    $internalPatchUrl,
    $sourceUrl,
);

if (!$didUpdate) {
    abort(400);
}

// Log the successful update of the game hash.
$comment = "$hash updated by $user.";
$comment .= $name ? " File Name: \"$name\"." : " File Name: None.";
$comment .= $labels ? " Label: \"$labels\"." : " Label: None.";
if ($internalPatchUrl !== $existingGameHash->internal_patch_url) {
    $comment .= $internalPatchUrl ? " RAPatches URL updated to: $internalPatchUrl." : " RAPatches URL removed.";
}
if ($sourceUrl !== $existingGameHash->source) {
    $comment .= $sourceUrl ? " Resource Page URL updated to: $sourceUrl." : " Resource Page URL removed.";
}
addArticleComment("Server", ArticleType::GameHash, $gameId, $comment);

return response()->json(['message' => __('legacy.success.update')]);
