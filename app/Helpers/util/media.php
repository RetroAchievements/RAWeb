<?php

use App\Platform\Enums\ImageType;
use App\Support\Media\FilenameIterator;
use Illuminate\Support\Facades\Storage;

function UploadToS3(string $filenameSrc, string $filenameDest): bool
{
    if (!config('filesystems.disks.s3.key')) {
        // nothing to do here
        return false;
    }

    // allow using minio locally
    if (app()->environment('local') && !config('filesystems.disks.s3.minio')) {
        return false;
    }

    return Storage::disk('s3')->put(ltrim($filenameDest, '/'), file_get_contents($filenameSrc));
}

/**
 * @throws Exception
 */
function validateFile(array $file): bool
{
    if ($file['error'] ?? null) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE) {
            throw new Exception('File too large');
        }
        throw new Exception($file['error']);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array($extension, ['png', 'jpeg', 'jpg', 'gif'])) {
        throw new Exception('Invalid file type');
    }

    if (($file['size'] ?? 0) > 1_048_576) {
        throw new Exception('File too large');
    }

    return true;
}

/**
 * @throws Exception
 */
function createImageFromExtension(array $file): GdImage
{
    $image = match (pathinfo($file['name'], PATHINFO_EXTENSION)) {
        'png' => imagecreatefrompng($file['tmp_name']),
        'jpg', 'jpeg' => imagecreatefromjpeg($file['tmp_name']),
        'gif' => imagecreatefromgif($file['tmp_name']),
        default => null
    };
    if (!$image) {
        throw new Exception('Cannot create image');
    }

    return $image;
}

/**
 * @throws Exception
 */
function createFileArrayFromDataUrl(string $dataUrl): array
{
    $dataUrlParts = explode(';base64,', $dataUrl);

    $base64ImageData = $dataUrlParts[1] ?? '';
    if (empty($base64ImageData)) {
        throw new Exception('No image data found in data URL');
    }

    $extension = mb_strtolower(str_replace('data:image/', '', $dataUrlParts[0] ?? ''));
    if (empty($extension)) {
        throw new Exception('No file type found in data URL');
    }

    $imageData = base64_decode($base64ImageData);
    if (!$imageData) {
        throw new Exception('Could not decode base64 image data');
    }

    $tempFilename = tempnam(sys_get_temp_dir(), 'data-url');
    if (!file_put_contents($tempFilename, $imageData)) {
        throw new Exception('Could not write temporary file');
    }

    return ['name' => 'data-url.' . $extension, 'tmp_name' => $tempFilename];
}

/**
 * @throws Exception
 */
function UploadBadgeImage(array $file): string
{
    validateFile($file);
    $sourceImage = createImageFromExtension($file);

    [$width, $height] = getimagesize($file['tmp_name']);

    $size = 64;

    $image = imagecreatetruecolor($size, $size);
    imagecopyresampled($image, $sourceImage, 0, 0, 0, 0, $size, $size, $width, $height);

    $imageLocked = imagecreatetruecolor($size, $size);
    imagecopyresampled($imageLocked, $sourceImage, 0, 0, 0, 0, $size, $size, $width, $height);
    imagefilter($imageLocked, IMG_FILTER_GRAYSCALE);
    imagefilter($imageLocked, IMG_FILTER_CONTRAST, 20);
    imagefilter($imageLocked, IMG_FILTER_GAUSSIAN_BLUR);

    $badgeIterator = FilenameIterator::getBadgeIterator();
    $imagePath = 'Badge/' . $badgeIterator . '.png';
    $imagePathLocked = 'Badge/' . $badgeIterator . '_lock.png';
    if (!imagepng($image, storage_path('app/media/' . $imagePath))
        || !imagepng($imageLocked, storage_path('app/media/' . $imagePathLocked))) {
        throw new Exception('Cannot copy image to destination');
    }
    FilenameIterator::incrementBadgeIterator();

    UploadToS3(storage_path('app/media/' . $imagePath), $imagePath);
    UploadToS3(storage_path('app/media/' . $imagePathLocked), $imagePathLocked);

    return $badgeIterator;
}

/**
 * @throws Exception
 */
function UploadGameImage(array $file, string $imageType): string
{
    validateFile($file);
    $sourceImage = createImageFromExtension($file);

    [$width, $height] = getimagesize($file['tmp_name']);

    // Scale the resulting image to fit within the following limits:
    $maxWidth = 160;
    $maxHeight = 160;
    switch ($imageType) {
        case ImageType::GameIcon:
            $maxWidth = 96;
            $maxHeight = 96;
            break;
        case ImageType::GameTitle:
        case ImageType::GameInGame:
            $maxWidth = 320;
            $maxHeight = 240;
            break;
        case ImageType::GameBoxArt:
            $maxWidth = 320;
            $maxHeight = 320;
            break;
    }

    $targetWidth = $width;
    $targetHeight = $height;
    if ($targetWidth > $maxWidth) {
        $wScaling = 1.0 / ($targetWidth / $maxWidth);
        $targetWidth = $targetWidth * $wScaling;
        $targetHeight = $targetHeight * $wScaling;
    }
    // IF, after potentially being reduced, the height's still too big, scale again
    if ($targetHeight > $maxHeight) {
        $vScaling = 1.0 / ($targetHeight / $maxHeight);
        $targetWidth = $targetWidth * $vScaling;
        $targetHeight = $targetHeight * $vScaling;
    }

    $imagePath = '/Images/' . FilenameIterator::getImageIterator() . '.png';
    $image = imagecreatetruecolor($targetWidth, $targetHeight);
    imagecopyresampled($image, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

    if (!imagepng($image, storage_path('app/media/' . $imagePath))) {
        throw new Exception('Cannot copy image to destination');
    }
    FilenameIterator::incrementImageIterator();

    UploadToS3(storage_path('app/media/' . $imagePath), $imagePath);

    return $imagePath;
}

/**
 * @throws Exception
 */
function UploadAvatar(string $user, string $base64ImageData): void
{
    $file = createFileArrayFromDataUrl($base64ImageData);
    validateFile($file);
    $sourceImage = createImageFromExtension($file);

    $size = 128;

    // Allow transparent backgrounds for .png and .gif files
    $image = imagecreatetruecolor($size, $size);
    if (!$image) {
        throw new Exception('Cannot initialize new GD image stream');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if ($extension === 'png' || $extension === 'gif') {
        $background = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagecolortransparent($image, $background);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagecopy($image, $sourceImage, 0, 0, 0, 0, $size, $size);
    } elseif ($extension === 'jpg' || $extension === 'jpeg') {
        // Create a black rect, size 128x128
        $background = imagecreatetruecolor($size, $size);
        // Copy the black rect onto our image
        imagecopy($image, $background, 0, 0, 0, 0, $size, $size);
    }

    // Reduce the input file size
    [$width, $height] = getimagesize($file['tmp_name']);
    // Given Image W/H is $givenImageWidth, $givenImageHeight, dest size is $userPicDestSize

    imagecopyresampled($image, $sourceImage, 0, 0, 0, 0, $size, $size, $width, $height);

    if (!imagepng($image, storage_path('app/media/UserPic/' . $user . '.png'))) {
        throw new Exception('Cannot copy image to destination');
    }

    // touch user entry
    $db = getMysqliConnection();
    mysqli_query($db, "UPDATE UserAccounts SET Updated=NOW() WHERE User='$user'");
}

function removeAvatar(string $user): void
{
    $avatarFile = storage_path('app/media/UserPic/' . $user . '.png');
    if (file_exists($avatarFile)) {
        unlink($avatarFile);
    }
}

/**
 * @throws Exception
 */
function UploadNewsImage(string $base64ImageData): string
{
    $file = createFileArrayFromDataUrl($base64ImageData);
    validateFile($file);
    $sourceImage = createImageFromExtension($file);

    [$width, $height] = getimagesize($file['tmp_name']);

    // Scale the resulting image to fit within the following limits:
    $maxWidth = 530;
    $maxHeight = 280;
    $targetWidth = $width;
    $targetHeight = $height;
    if ($targetWidth > $maxWidth) {
        $wScaling = 1.0 / ($targetWidth / $maxWidth);
        $targetWidth = $targetWidth * $wScaling;
        $targetHeight = $targetHeight * $wScaling;
    }
    // IF, after potentially being reduced, the height's still too big, scale again
    if ($targetHeight > $maxHeight) {
        $vScaling = 1.0 / ($targetHeight / $maxHeight);
        $targetWidth = $targetWidth * $vScaling;
        $targetHeight = $targetHeight * $vScaling;
    }

    $image = imagecreatetruecolor($targetWidth, $targetHeight);
    imagecopyresampled($image, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

    $imagePath = '/Images/' . FilenameIterator::getImageIterator() . '.jpg';
    if (!imagejpeg($image, storage_path('app/media/' . $imagePath))) {
        throw new Exception('Cannot copy image to destination');
    }
    FilenameIterator::incrementImageIterator();

    UploadToS3(storage_path('app/media/' . $imagePath), $imagePath);

    return media_asset($imagePath);
}
