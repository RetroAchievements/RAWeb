<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

$imageIterFilename = "ImageIter.txt";

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
$uploadType = seekPOST('t', "");

$returnID = seekPOST('i', 0);
settype($returnID, 'integer');

$allowedExts = ["png", "jpeg", "jpg", "gif", "bmp"];
$extension = mb_strtolower(end(explode(".", $_FILES["file"]["name"])));


if ($_FILES["file"]["size"] > 1048576) {
    echo "Error: image too big! Must be smaller than 1mb!";
} else {
    if ($extension == null || mb_strlen($extension) < 1) {
        echo "Error: no file detected. Did you pick a file for upload?";
    } else {
        if (!in_array($extension, $allowedExts)) {
            echo "Error: image type ($extension) not supported! Supported types: .png, .jpeg, .gif";
        } else {
            if (!in_array($uploadType, $allowedTypes)) {
                echo "Error: upload authorisation not given. Are you uploading from retroachievements.org?";
            } else {
                if ($_FILES["file"]["error"] > 0) {
                    echo "Error: " . $_FILES["file"]["error"] . "<br />";
                } else {
                    $tempFile = $_FILES["file"]["tmp_name"];
                    if ($extension == 'png') {
                        $tempImage = imagecreatefrompng($tempFile);
                    } else {
                        if ($extension == 'jpg' || $extension == 'jpeg') {
                            $tempImage = imagecreatefromjpeg($tempFile);
                        } else {
                            if ($extension == 'gif') {
                                $tempImage = imagecreatefromgif($tempFile);
                            } else {
                                if ($extension == 'bmp') {
                                    $tempImage = imagecreatefrombitmap($tempFile);
                                }
                            }
                        }
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

                    if ($uploadType == "NEWS") {
                        $maxImageSizeWidth = 160;
                        $maxImageSizeHeight = 160;
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
                        // error_log("uploadpic.php failed: Issues copying to $newImageFilename");
                        echo "Issues encountered - these have been reported and will be fixed - sorry for the inconvenience... please try another file!";
                        exit;
                    } else {
                        UploadToS3($newImageFilename, "Images/$nextImageFilenameStr.png");

                        //	Increment and save this new badge number for next time
                        $newImageIter = str_pad($nextImageFilename + 1, 6, "0", STR_PAD_LEFT);
                        file_put_contents($imageIterFilename, $newImageIter);

                        if ($uploadType == "NEWS") {
                            //header( "Location: " . getenv('APP_URL') . "/submitnews.php?e=uploadok&g=/$newImageFilename" );
                            echo "OK:/$newImageFilename";
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
					  SET $param='/Images/$nextImageFilenameStr.png'
					  WHERE gd.ID = $returnID";

                                $dbResult = mysqli_query($db, $query);

                                if ($dbResult == false) {
                                    log_sql_fail();
                                    //log_email("uploadpic.php went wrong... $uploadType, $returnID");
                                } else {
                                    // error_log("Logged image update $uploadType to game $returnID, to image /Images/$nextImageFilenameStr.png");
                                }

                                header("Location: " . getenv('APP_URL') . "/game/$returnID?e=uploadok");
                                exit;
                            }
                        }
                    }
                }
            }
        }
    }
}
