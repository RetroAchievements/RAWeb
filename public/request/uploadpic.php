<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

use RA\Permissions;

$imageIterFilename = __DIR__ . "/../ImageIter.txt";

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Developer)) {
    if (getAccountDetails($user, $userDetails) == false) {
        // Immediate redirect if we cannot validate user!
        header("Location: " . getenv('APP_URL') . "?e=accountissue");
        exit;
    }
} else {
    // Immediate redirect if we cannot validate cookie!
    header("Location: " . getenv('APP_URL') . "?e=badcredentials");
    exit;
}

$allowedGameImageTypes = ["GAME_ICON", "GAME_TITLE", "GAME_INGAME", "GAME_BOXART"];
$allowedTypes = array_merge(["NEWS"], $allowedGameImageTypes); //, "ACHIEVEMENT"
$uploadType = requestInputPost('t', "");

$returnID = requestInputPost('i', 0, 'integer');

$allowedExts = ["png", "jpeg", "jpg", "gif", "bmp"];
$filenameParts = explode(".", $_FILES["file"]["name"]);
$extension = mb_strtolower(end($filenameParts));

if ($_FILES["file"]["size"] > 1048576) {
    echo "Error: image too big! Must be smaller than 1mb!";
    return;
}
if ($extension == null || mb_strlen($extension) < 1) {
    echo "Error: no file detected. Did you pick a file for upload?";
    return;
}
if (!in_array($extension, $allowedExts)) {
    echo "Error: image type ($extension) not supported! Supported types: .png, .jpeg, .gif";
    return;
}
if (!in_array($uploadType, $allowedTypes)) {
    echo "Error: upload authorisation not given. Are you uploading from retroachievements.org?";
    return;
}
if ($_FILES["file"]["error"] > 0) {
    echo "Error: " . $_FILES["file"]["error"] . "<br />";
    return;
}
$tempImage = null;
$tempFile = $_FILES["file"]["tmp_name"];
switch ($extension) {
    case 'png':
        $tempImage = imagecreatefrompng($tempFile);
        break;
    case 'jpg':
    case 'jpeg':
        $tempImage = imagecreatefromjpeg($tempFile);
        break;
    case 'gif':
        $tempImage = imagecreatefromgif($tempFile);
        break;
}

if (!$tempImage) {
    header("Location: " . getenv('APP_URL') . "/game/$returnID?e=error");
    exit;
}

$nextImageFilename = file_get_contents($imageIterFilename);
settype($nextImageFilename, "integer");
$nextImageFilenameStr = str_pad($nextImageFilename, 6, "0", STR_PAD_LEFT);

$destPath = getenv('DOC_ROOT') . "public/Images/";

$newImageFilename = $destPath . $nextImageFilenameStr . ".png";

[$width, $height] = getimagesize($tempFile);

//	Scale the resulting image to fit within the following limits:
$maxImageSizeWidth = 160;
$maxImageSizeHeight = 160;

switch ($uploadType) {
    case 'GAME_ICON':
        $maxImageSizeWidth = 96;
        $maxImageSizeHeight = 96;
        break;
    case 'GAME_TITLE':
    case 'GAME_INGAME':
        $maxImageSizeWidth = 320;
        $maxImageSizeHeight = 240;
        break;
    case 'GAME_BOXART':
        $maxImageSizeWidth = 320;
        $maxImageSizeHeight = 320;
        break;
}

$wScaling = 1.0;

$targetWidth = $width;
$targetHeight = $height;

if ($targetWidth > $maxImageSizeWidth) {
    $wScaling = 1.0 / ($targetWidth / $maxImageSizeWidth);
    // error_log("WScaling is $wScaling, so width $targetWidth and height $targetHeight become...");
    $targetWidth = $targetWidth * $wScaling;
    $targetHeight = $targetHeight * $wScaling;
    // error_log("$targetWidth and $targetHeight");
}
//	IF, after potentially being reduced, the height's still too big, scale again
if ($targetHeight > $maxImageSizeHeight) {
    $vScaling = 1.0 / ($targetHeight / $maxImageSizeHeight);
    // error_log("VScaling is $vScaling, so width $targetWidth and height $targetHeight become...");
    $targetWidth = $targetWidth * $vScaling;
    $targetHeight = $targetHeight * $vScaling;
    // error_log("$targetWidth and $targetHeight");
}

$newImage = imagecreatetruecolor($targetWidth, $targetHeight);
imagecopyresampled($newImage, $tempImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

$success = imagepng($newImage, $newImageFilename);

if ($success == false) {
    error_log("uploadpic.php failed: Issues copying to $newImageFilename");
    echo "Issues encountered - these have been reported and will be fixed - sorry for the inconvenience... please try another file!";
    exit;
}
UploadToS3($newImageFilename, "Images/$nextImageFilenameStr.png");

// Increment and save this new badge number for next time
$newImageIter = str_pad($nextImageFilename + 1, 6, "0", STR_PAD_LEFT);
file_put_contents($imageIterFilename, $newImageIter);

if ($uploadType == "NEWS") {
    //header( "Location: " . getenv('APP_URL') . "/submitnews.php?e=uploadok&g=/$newImageFilename" );
    echo "OK:/$newImageFilename";
    exit;
}

if (in_array($uploadType, $allowedGameImageTypes)) {
    // Associate new data, then return to game page:
    $param = '';
    switch ($uploadType) {
        case 'GAME_ICON':
            $param = 'ImageIcon';
            break;
        case 'GAME_TITLE':
            $param = 'ImageTitle';
            break;
        case 'GAME_INGAME':
            $param = 'ImageIngame';
            break;
        case 'GAME_BOXART':
            $param = 'ImageBoxArt';
            break;
    }

    global $db;
    $query = "UPDATE GameData AS gd SET $param='/Images/$nextImageFilenameStr.png' WHERE gd.ID = $returnID";
    $dbResult = mysqli_query($db, $query);

    if ($dbResult == false) {
        log_sql_fail();
    }

    header("Location: " . getenv('APP_URL') . "/game/$returnID?e=uploadok");
    exit;
}
