<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    if (getAccountDetails($user, $userDetails) == false) {
        //	Immediate redirect if we cannot validate user!
        header("Location: " . getenv('APP_URL') . "?e=accountissue");
        exit;
    }
} else {
    //	Immediate redirect if we cannot validate cookie!
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

$allowedTypes = ["NEWS", "GAME_ICON", "GAME_TITLE", "GAME_INGAME", "GAME_BOXART"]; //, "ACHIEVEMENT"
$uploadType = requestInputPost('t', "");

if ($uploadType !== 'NEWS') {
    // error_log("Unsupported upload type!");
    return;
}

$filename = requestInputPost('f');
$rawImage = requestInputPost('i');

//	sometimes the extension... *is* the filename?
$extension = $filename;
if (explode(".", $filename) !== false) {
    $segmentParts = explode(".", $filename);
    $extension = end($segmentParts);
}

$extension = mb_strtolower($extension);

//	Trim declaration
$rawImage = str_replace('data:image/png;base64,', '', $rawImage);
$rawImage = str_replace('data:image/bmp;base64,', '', $rawImage);
$rawImage = str_replace('data:image/gif;base64,', '', $rawImage); //	added untested 23:47 28/02/2014
$rawImage = str_replace('data:image/jpg;base64,', '', $rawImage);
$rawImage = str_replace('data:image/jpeg;base64,', '', $rawImage);

$imageData = base64_decode($rawImage);

//$tempFilename = '/tmp/' . uniqid() . '.png';
$tempFilename = tempnam(sys_get_temp_dir(), 'PIC');
//error_log( $tempFilename );
$success = file_put_contents($tempFilename, $imageData);

if ($success) {
    if ($extension == 'png') {
        $tempImage = imagecreatefrompng($tempFilename);
    } else {
        if ($extension == 'jpg' || $extension == 'jpeg') {
            $tempImage = imagecreatefromjpeg($tempFilename);
        } else {
            if ($extension == 'gif') {
                $tempImage = imagecreatefromgif($tempFilename);
            } else {
                if ($extension == 'bmp') {
                    $tempImage = imagecreatefrombitmap($tempFilename);
                }
            }
        }
    }

    $targetExt = ($uploadType == "NEWS") ? ".jpg" : ".png";

    $nextImageFilename = file_get_contents(__DIR__ . "/../../ImageIter.txt");
    settype($nextImageFilename, "integer");
    $nextImageFilenameStr = str_pad($nextImageFilename, 6, "0", STR_PAD_LEFT) . $targetExt;

    [$width, $height] = getimagesize($tempFilename);

    //	Scale the resulting image to fit within the following limits:
    $maxImageSizeWidth = 160;
    $maxImageSizeHeight = 160;

    if ($uploadType == "NEWS") {
        $maxImageSizeWidth = 530;
        $maxImageSizeHeight = 280;
    } else {
        if ($uploadType == "GAME_ICON") { //	ICON
            $maxImageSizeWidth = 96;
            $maxImageSizeHeight = 96;
        } else {
            if ($uploadType == "GAME_TITLE" || $uploadType == "GAME_INGAME") {  //	Screenshot
                $maxImageSizeWidth = 320;
                $maxImageSizeHeight = 240;
            } else {
                if ($uploadType == "GAME_BOXART") {
                    $maxImageSizeWidth = 320;
                    $maxImageSizeHeight = 320;
                }
            }
        }
    }


    $wScaling = 1.0;

    $targetWidth = $width;
    $targetHeight = $height;


    if ($targetWidth > $maxImageSizeWidth) {
        $wScaling = 1.0 / ($targetWidth / $maxImageSizeWidth);
        //error_log( "WScaling is $wScaling, so width $targetWidth and height $targetHeight become..." );
        $targetWidth = $targetWidth * $wScaling;
        $targetHeight = $targetHeight * $wScaling;
        //error_log( "$targetWidth and $targetHeight" );
    }
    //	IF, after potentially being reduced, the height's still too big, scale again
    if ($targetHeight > $maxImageSizeHeight) {
        $vScaling = 1.0 / ($targetHeight / $maxImageSizeHeight);
        //error_log( "VScaling is $vScaling, so width $targetWidth and height $targetHeight become..." );
        $targetWidth = $targetWidth * $vScaling;
        $targetHeight = $targetHeight * $vScaling;
        //error_log( "$targetWidth and $targetHeight" );
    }

    $newImage = imagecreatetruecolor($targetWidth, $targetHeight);
    imagecopyresampled($newImage, $tempImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

    $newImageFilename = "Images/$nextImageFilenameStr";

    if ($uploadType == "NEWS") {
        $success = imagejpeg($newImage, __DIR__ . '/../../' . $newImageFilename);
    } else {
        $success = imagepng($newImage, __DIR__ . '/../../' . $newImageFilename);
    }


    if ($success == false) {
        // error_log("uploadpicinline.php failed: Issues copying to $newImageFilename");
        echo "Issues encountered - these have been reported and will be fixed - sorry for the inconvenience... please try another file!";
        exit;
    } else {
        UploadToS3(__DIR__ . '/../../' . $newImageFilename, $newImageFilename);

        //	Increment and save this new badge number for next time
        $thisImageIter = str_pad($nextImageFilename, 6, "0", STR_PAD_LEFT);
        $newImageIter = str_pad($nextImageFilename + 1, 6, "0", STR_PAD_LEFT);
        file_put_contents(__DIR__ . "/../../ImageIter.txt", $newImageIter);

        //error_log( $tempFilename );
        unlink($tempFilename);

        if ($uploadType == "NEWS") {
            echo "OK:/Images/" . $thisImageIter . $targetExt;
            exit;
        } else {
            if ($uploadType == "GAME_ICON" || $uploadType == "GAME_TITLE" || $uploadType == "GAME_INGAME" || $uploadType == "GAME_BOXART") {
                //	Associate new data, then return to game page:

                $param = '';
                if ($uploadType == "GAME_ICON") {
                    $param = 'ImageIcon';
                } else {
                    if ($uploadType == "GAME_TITLE") {
                        $param = 'ImageTitle';
                    } else {
                        if ($uploadType == "GAME_INGAME") {
                            $param = 'ImageIngame';
                        } else {
                            if ($uploadType == "GAME_BOXART") {
                                $param = 'ImageBoxArt';
                            }
                        }
                    }
                }

                $query = "UPDATE GameData AS gd
					  SET $param='/$newImageFilename'
					  WHERE gd.ID = $returnID";

                $dbResult = mysqli_query($db, $query);

                if ($dbResult == false) {
                    log_sql_fail();
                //log_email("uploadpicinline.php went wrong... $uploadType, $returnID");
                } else {
                    //error_log( $query );
                    // error_log("Logged image update $uploadType to game $returnID, to image /$newImageFilename");
                }

                header("Location: " . getenv('APP_URL') . "/game/$returnID?e=uploadok");
                exit;
            }
        }
    }
} else {
    echo "Could not write temporary file?!";
    exit;
}
