<?php

use Aws\S3\S3Client;

function UploadToS3($filenameSrc, $filenameDest)
{
    if (!getenv('AWS_ACCESS_KEY_ID') && !in_array(getenv('APP_ENV'), ['local'])) {
        // nothing to do here
        return;
    }

    $client = new S3Client([
        'region' => getenv('AWS_DEFAULT_REGION'),
        'version' => 'latest',
    ]);

    $client->putObject([
        'Bucket' => getenv('AWS_BUCKET'),
        'Key' => $filenameDest,
        'Body' => fopen($filenameSrc, 'r+'),
        'CacheControl' => 'max-age=2628000',
    ]);
}

function UploadUserPic($user, $filename, $rawImage)
{
    $response = [];

    $response['Filename'] = $filename;
    $response['User'] = $user;

    // $filename = requestInputPost( 'f' );
    // $rawImage = requestInputPost( 'i' );
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
    if ($success) {
        $userPicDestSize = 128;

        $existingUserFile = __DIR__ . "/../../public/UserPic/$user.png";

        $tempImage = null;

        // Allow transparent backgrounds for .png and .gif files
        if ($extension == 'png' || $extension == 'gif') {
            $newImage = imagecreatetruecolor($userPicDestSize, $userPicDestSize);
            $background = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagecolortransparent($newImage, $background);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);

            if ($extension == 'png') {
                $tempImage = imagecreatefrompng($tempFilename);
            } else {
                $tempImage = imagecreatefromgif($tempFilename);
            }

            if (!$tempImage) {
                $response['Success'] = false;
                $response['Error'] = "Failed to process $extension file";
                return $response;
            }

            imagecopy($newImage, $tempImage, 0, 0, 0, 0, $userPicDestSize, $userPicDestSize);
        } elseif ($extension == 'jpg' || $extension == 'jpeg') {
            $tempImage = imagecreatefromjpeg($tempFilename);
            if (!$tempImage) {
                $response['Success'] = false;
                $response['Error'] = 'Failed to process JPEG file';
                return $response;
            }

            $newImage = imagecreatetruecolor($userPicDestSize, $userPicDestSize);

            // Create a black rect, size 128x128
            $blackRect = imagecreatetruecolor($userPicDestSize, $userPicDestSize);
            if ($blackRect === false) {
                $response['Success'] = false;
                $response['Error'] = 'Cannot initialize new GD image stream';
                return $response;
            }

            // Copy the black rect onto our image
            imagecopy($newImage, $blackRect, 0, 0, 0, 0, $userPicDestSize, $userPicDestSize);
        } else {
            $response['Success'] = false;
            $response['Error'] = "Unsupported file type: $extension";
            return $response;
        }

        // Reduce the input file size
        [$givenImageWidth, $givenImageHeight] = getimagesize($tempFilename);
        // Given Image W/H is $givenImageWidth, $givenImageHeight, dest size is $userPicDestSize

        imagecopyresampled($newImage, $tempImage, 0, 0, 0, 0, $userPicDestSize, $userPicDestSize, $givenImageWidth, $givenImageHeight);

        $success = imagepng($newImage, $existingUserFile);

        if ($success == false) {
            // UploadUserPic failed: Issues copying to UserPic/$user.png
            // echo "Issues encountered - these have been reported and will be fixed - sorry for the inconvenience... please try another file!";
            $response['Error'] = "Issues copying to UserPic/$user.png";
        } else {
            // touch user entry
            global $db;
            mysqli_query($db, "UPDATE UserAccounts SET Updated=NOW() WHERE User='$user'");

            // Done OK
            // echo 'OK';
            // header( "Location: " . getenv('APP_URL') . "/manageuserpic.php?e=success" );
        }
    }

    $response['Success'] = $success;
    return $response;
}

function UploadBadgeImage($file)
{
    $response = [];

    $filename = $file["name"];
    $filesize = $file["size"];
    $fileerror = $file["error"];
    $fileTempName = $file["tmp_name"];

    $response['Filename'] = $filename;
    $response['Size'] = $filesize;

    $allowedExts = ["png", "jpeg", "jpg", "gif"];
    $filenameParts = explode(".", $filename);
    $extension = mb_strtolower(end($filenameParts));

    if ($filesize > 1_048_576) {
        $response['Error'] = "Error: image too big ($filesize)! Must be smaller than 1mb!";
    } else {
        if (!in_array($extension, $allowedExts)) {
            $response['Error'] = "Error: image type ($extension) not supported! Supported types: .png, .jpg, .jpeg, .gif";
        } else {
            if ($fileerror) {
                if ($fileerror == UPLOAD_ERR_INI_SIZE) {
                    $response['Error'] = "Error: file too big! Must be smaller than 1mb please.";
                } else {
                    $response['Error'] = "Error: $fileerror<br>";
                }
            } else {
                $nextBadgeFilename = file_get_contents(__DIR__ . "/../../public/BadgeIter.txt");
                settype($nextBadgeFilename, "integer");

                // Produce filenames

                $newBadgeFilenameFormatted = str_pad($nextBadgeFilename, 5, "0", STR_PAD_LEFT);

                // Fetch file and width/height

                $tempImage = null;

                if ($extension == 'png') {
                    $tempImage = imagecreatefrompng($fileTempName);
                } else {
                    if ($extension == 'jpg' || $extension == 'jpeg') {
                        $tempImage = imagecreatefromjpeg($fileTempName);
                    } else {
                        if ($extension == 'gif') {
                            $tempImage = imagecreatefromgif($fileTempName);
                        }
                    }
                }

                if (!$tempImage) {
                    $response['Error'] = "Failed to process $extension image";
                    $response['Success'] = false;
                    return $response;
                }

                [$width, $height] = getimagesize($fileTempName);

                // Create all images
                $smallPx = 32;
                $normalPx = 64;
                $largePx = 128;

                // $newSmallImage = imagecreatetruecolor($smallPx, $smallPx);
                $newImage = imagecreatetruecolor($normalPx, $normalPx);
                // $newLargeImage = imagecreatetruecolor($largePx, $largePx);
                // $newSmallImageLocked = imagecreatetruecolor($smallPx, $smallPx);
                $newImageLocked = imagecreatetruecolor($normalPx, $normalPx);

                // Copy source to dest for all images
                // imagecopyresampled($newSmallImage, 	$tempImage, 0, 0, 0, 0, $smallPx, $smallPx, $width, $height);
                imagecopyresampled($newImage, $tempImage, 0, 0, 0, 0, $normalPx, $normalPx, $width, $height);
                // imagecopyresampled($newLargeImage, 	$tempImage, 0, 0, 0, 0, $largePx, $largePx, $width, $height);

                imagecopyresampled($newImageLocked, $tempImage, 0, 0, 0, 0, $normalPx, $normalPx, $width, $height);
                imagefilter($newImageLocked, IMG_FILTER_GRAYSCALE);
                imagefilter($newImageLocked, IMG_FILTER_CONTRAST, 20);
                imagefilter($newImageLocked, IMG_FILTER_GAUSSIAN_BLUR);

                // imagecopyresampled($newSmallImageLocked, $tempImage, 0, 0, 0, 0, $smallPx, $smallPx, $width, $height);
                // imagefilter( $newSmallImageLocked, IMG_FILTER_GRAYSCALE );
                // imagefilter( $newSmallImageLocked, IMG_FILTER_CONTRAST, 20 );
                // imagefilter( $newSmallImageLocked, IMG_FILTER_GAUSSIAN_BLUR );

                $destBadgeFile = "Badge/" . "$newBadgeFilenameFormatted" . ".png";
                $destBadgeFileLocked = "Badge/" . "$newBadgeFilenameFormatted" . "_lock.png";
                // $destBadgeFileBig = "Badge/" . "$newBadgeFilenameFormatted" . "_big.png";
                // $destBadgeFileSmall = "Badge/" . "$newBadgeFilenameFormatted" . "_small.png";
                // $destBadgeFileLockedSmall = "Badge/" . "$newBadgeFilenameFormatted" . "_locksmall.png";

                $success = // imagepng( $newLargeImage, $destBadgeFileBig ) &&
                    // imagepng( $newSmallImage, $destBadgeFileSmall ) &&
                    // imagepng( $newSmallImageLocked, $destBadgeFileLockedSmall ) &&
                    imagepng($newImage, __DIR__ . '/../../public/' . $destBadgeFile) &&
                    imagepng($newImageLocked, __DIR__ . '/../../public/' . $destBadgeFileLocked);

                if ($success == false) {
                    // UploadBadgeImage failed: Issues copying from ? to $destBadgeFile
                    $response['Error'] = "Issues encountered - these have been reported and will be fixed - sorry for the inconvenience... please try another file!";
                } else {
                    UploadToS3(__DIR__ . '/../../public/' . $destBadgeFile, $destBadgeFile);
                    UploadToS3(__DIR__ . '/../../public/' . $destBadgeFileLocked, $destBadgeFileLocked);

                    $newBadgeContent = str_pad($nextBadgeFilename, 5, "0", STR_PAD_LEFT);
                    // echo "OK:$newBadgeContent";
                    $response['BadgeIter'] = $newBadgeContent;

                    // Increment and save this new badge number for next time
                    $newBadgeContent = str_pad($nextBadgeFilename + 1, 5, "0", STR_PAD_LEFT);
                    file_put_contents(__DIR__ . "/../../public/BadgeIter.txt", $newBadgeContent);
                }
            }
        }
    }

    $response['Success'] = !isset($response['Error']);
    return $response;
}
