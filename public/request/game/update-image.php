<?php

use RA\ImageType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    redirect('?e=badcredentials');
    exit;
}

$gameID = (int) requestInputPost('i', 0);
$imageType = (string) requestInputPost('t');

if ($permissions == Permissions::JuniorDeveloper && !checkIfSoleDeveloper($user, $gameID)) {
    // Immediate redirect if the jr. dev attempting to upload the image is not the sole developer
    redirect("game/$gameID?e=badcredentials");
    exit;
}

if (!ImageType::isValidGameImageType($imageType)) {
    redirect("game/$gameID?e=error");
    exit;
}

try {
    $imagePath = UploadGameImage($_FILES['file'], $imageType);
} catch (Exception $exception) {
    redirect("game/$gameID?e=error");
    exit;
}

$field = match ($imageType) {
    ImageType::GameIcon => 'ImageIcon',
    ImageType::GameTitle => 'ImageTitle',
    ImageType::GameInGame => 'ImageIngame',
    ImageType::GameBoxArt => 'ImageBoxArt',
    default => null,
};
if (!$field) {
    redirect("game/$gameID?e=error");
    exit;
}

global $db;
$dbResult = mysqli_query($db, "UPDATE GameData AS gd SET $field='$imagePath' WHERE gd.ID = $gameID");
if (!$dbResult) {
    redirect("game/$gameID?e=error");
    exit;
}

redirect("game/$gameID?e=uploadok");
