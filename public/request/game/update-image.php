<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimSetType;
use App\Enums\Permissions;
use App\Models\User;
use App\Platform\Enums\ImageType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'type' => ['required', 'string', Rule::in(ImageType::cases())],
    'file' => ['image'],
]);

if ($input['type'] === ImageType::GameIcon) {
    Validator::make(
        request()->all(),
        ['file' => ['dimensions:width=96,height=96']],
        ['file.dimensions' => 'Game icons are required to have dimensions of 96x96 pixels.']
    )->validate();
}

$gameID = (int) $input['game'];
$imageType = $input['type'];

$userModel = User::firstWhere('User', $user);

// Only allow jr. devs if they are the sole author of the set or have the primary claim
if (
    $permissions == Permissions::JuniorDeveloper
    && (!checkIfSoleDeveloper($userModel, $gameID) && !hasSetClaimed($user, $gameID, true, ClaimSetType::NewSet))
) {
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
