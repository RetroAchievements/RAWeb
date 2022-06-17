<?php

use RA\ArticleType;
use RA\ImageType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

// TODO check

$gameID = (int) requestInputPost('i', 0);
$imageType = (string) requestInputPost('t');

if ($permissions == Permissions::JuniorDeveloper && !checkIfSoleDeveloper($user, $gameID)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (!ImageType::isValidGameImageType($imageType)) {
    return back()->withErrors(__('legacy.error.image_upload'));
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
    default => null,
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
