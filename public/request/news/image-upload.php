<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    // Immediate redirect if we cannot validate cookie!
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

$uploadType = requestInputPost('t', "");

if ($uploadType !== 'NEWS') {
    exit;
}

$filename = requestInputPost('f');
$rawImage = requestInputPost('i');

// sometimes the extension... *is* the filename?
$extension = $filename;
if (explode(".", $filename) !== false) {
    $segmentParts = explode(".", $filename);
    $extension = end($segmentParts);
}

$extension = mb_strtolower($extension);

// Trim declaration
$rawImage = str_replace('data:image/png;base64,', '', $rawImage);
$rawImage = str_replace('data:image/bmp;base64,', '', $rawImage);
$rawImage = str_replace('data:image/gif;base64,', '', $rawImage); // added untested 23:47 28/02/2014
$rawImage = str_replace('data:image/jpg;base64,', '', $rawImage);
$rawImage = str_replace('data:image/jpeg;base64,', '', $rawImage);

$imageData = base64_decode($rawImage);

// $tempFilename = '/tmp/' . uniqid() . '.png';
$tempFilename = tempnam(sys_get_temp_dir(), 'PIC');
$success = file_put_contents($tempFilename, $imageData);

if (!$success) {
    echo "Could not write temporary file?!";
    exit;
}

$tempImage = match ($extension) {
    'png' => imagecreatefrompng($tempFilename),
    'jpg', 'jpeg' => imagecreatefromjpeg($tempFilename),
    'gif' => imagecreatefromgif($tempFilename),
    default => null
};

if (!$tempImage) {
    exit;
}

$targetExt = ".jpg";

$nextImageFilename = file_get_contents(__DIR__ . "/../../ImageIter.txt");
settype($nextImageFilename, "integer");
$nextImageFilenameStr = str_pad($nextImageFilename, 6, "0", STR_PAD_LEFT) . $targetExt;

[$width, $height] = getimagesize($tempFilename);

// Scale the resulting image to fit within the following limits:
$maxImageSizeWidth = 530;
$maxImageSizeHeight = 280;

$wScaling = 1.0;

$targetWidth = $width;
$targetHeight = $height;

if ($targetWidth > $maxImageSizeWidth) {
    $wScaling = 1.0 / ($targetWidth / $maxImageSizeWidth);
    $targetWidth = $targetWidth * $wScaling;
    $targetHeight = $targetHeight * $wScaling;
}
// IF, after potentially being reduced, the height's still too big, scale again
if ($targetHeight > $maxImageSizeHeight) {
    $vScaling = 1.0 / ($targetHeight / $maxImageSizeHeight);
    $targetWidth = $targetWidth * $vScaling;
    $targetHeight = $targetHeight * $vScaling;
}

$newImage = imagecreatetruecolor($targetWidth, $targetHeight);
imagecopyresampled($newImage, $tempImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

$newImageFilename = "Images/$nextImageFilenameStr";

$success = imagejpeg($newImage, __DIR__ . '/../../' . $newImageFilename);

if (!$success) {
    echo "Issues encountered - these have been reported and will be fixed - sorry for the inconvenience... please try another file!";
    exit;
}

UploadToS3(__DIR__ . '/../../' . $newImageFilename, $newImageFilename);

// Increment and save this new badge number for next time
$thisImageIter = str_pad($nextImageFilename, 6, "0", STR_PAD_LEFT);
$newImageIter = str_pad($nextImageFilename + 1, 6, "0", STR_PAD_LEFT);
file_put_contents(__DIR__ . "/../../ImageIter.txt", $newImageIter);

unlink($tempFilename);

echo "OK:/Images/" . $thisImageIter . $targetExt;
