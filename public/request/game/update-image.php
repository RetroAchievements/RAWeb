<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Community\Enums\ClaimSetType;
use LegacyApp\Platform\Enums\ImageType;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'game' => 'required|integer|exists:mysql_legacy.GameData,ID',
    'type' => ['required', 'string', Rule::in(ImageType::cases())],
    'file' => 'image',
]);

$gameID = (int) $input['game'];
$imageType = $input['type'];

// Only allow jr. devs if they are the sole author of the set or have the primary claim
if ($permissions == Permissions::JuniorDeveloper && (!checkIfSoleDeveloper($user, $gameID) && !hasSetClaimed($user, $gameID, true, ClaimSetType::NewSet))) {
    return back()->withErrors(__('legacy.error.permissions'));
}

try {
    $imagePath = UploadGameImage($_FILES['file'], $imageType);
} catch (Exception) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

$field = match ($imageType) {
    ImageType::GameIcon => 'ImageIcon',
    ImageType::GameTitle => 'ImageTitle',
    ImageType::GameInGame => 'ImageIngame',
    ImageType::GameBoxArt => 'ImageBoxArt',
    default => null, // should never hit this because of the match above
};
if (!$field) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

$db = getMysqliConnection();
$dbResult = mysqli_query($db, "UPDATE GameData AS gd SET $field='$imagePath' WHERE gd.ID = $gameID");
if (!$dbResult) {
    return back()->withErrors(__('legacy.error.image_upload'));
}

$label = match ($imageType) {
    ImageType::GameIcon => 'game icon',
    ImageType::GameTitle => 'title screenshot',
    ImageType::GameInGame => 'in-game screenshot',
    ImageType::GameBoxArt => 'game box art',
    default => '?', // should never hit this because of the match above
};

addArticleComment('Server', ArticleType::GameModification, $gameID, "$user changed the $label");

return back()->with('success', __('legacy.success.image_upload'));
