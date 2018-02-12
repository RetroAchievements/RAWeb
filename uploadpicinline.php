<?php
require_once('db.inc.php');

//define( "IMAGE_ITER", "/var/www/html/ImageIter.txt" );
//define( "DEST_PATH", "Images/" );
//	Will need copy/pasting when required :S

require_once('bin/aws.phar');
use Aws\S3\S3Client;
use Guzzle\Http\EntityBody;
function UploadToS3( $filenameDest, $rawFile )
{
    //error_log( "UploadToS3\n" );
    $client = S3Client::factory( array(
                'key' => 'AKIAJ2Q7T35B5AA66TUA',
                'secret' => AMAZON_S3_SECRET,
                'region' => 'eu-west-1'
    ) );

    $result = $client->putObject( array(
        'Bucket' => "i.retroachievements.org",
        'Key' => "$filenameDest",
        'Body' => EntityBody::factory( fopen( $filenameDest, 'r+' ) )
    ) );

    if( $result )
    {
        //error_log( "Successfully uploaded $filenameDest to S3!" );
    }
    else
    {
        error_log( "FAILED to upload $filenameDest to S3!" );
    }

    //error_log( "UploadToS3 2\n" );
}

if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) )
{
    if( getAccountDetails( $user, $userDetails ) == FALSE )
    {
        //	Immediate redirect if we cannot validate user!
        header( "Location: http://" . AT_HOST . "?e=accountissue" );
        exit;
    }
}
else
{
    //	Immediate redirect if we cannot validate cookie!
    header( "Location: http://" . AT_HOST . "?e=notloggedin" );
    exit;
}

$allowedTypes = array( "NEWS", "GAME_ICON", "GAME_TITLE", "GAME_INGAME", "GAME_BOXART" ); //, "ACHIEVEMENT"
$uploadType = seekPOST( 't', "" );

if( $uploadType !== 'NEWS' )
{
    error_log( "Unsupported upload type!" );
    return;
}

$filename = seekPOST( 'f' );
$rawImage = seekPOST( 'i' );

//	sometimes the extension... *is* the filename?
$extension = $filename;
if( explode( ".", $filename ) !== FALSE )
{
    $segmentParts = explode( ".", $filename );
    $extension = end( $segmentParts );
}

$extension = strtolower( $extension );

//	Trim declaration
$rawImage = str_replace( 'data:image/png;base64,', '', $rawImage );
$rawImage = str_replace( 'data:image/bmp;base64,', '', $rawImage );
$rawImage = str_replace( 'data:image/gif;base64,', '', $rawImage ); //	added untested 23:47 28/02/2014
$rawImage = str_replace( 'data:image/jpg;base64,', '', $rawImage );
$rawImage = str_replace( 'data:image/jpeg;base64,', '', $rawImage );

$imageData = base64_decode( $rawImage );

//$tempFilename = '/tmp/' . uniqid() . '.png';
$tempFilename = tempnam( sys_get_temp_dir(), 'PIC' );
//error_log( $tempFilename );
$success = file_put_contents( $tempFilename, $imageData );

if( $success )
{
    if( $extension == 'png' )
        $tempImage = imagecreatefrompng( $tempFilename );
    else if( $extension == 'jpg' || $extension == 'jpeg' )
        $tempImage = imagecreatefromjpeg( $tempFilename );
    else if( $extension == 'gif' )
        $tempImage = imagecreatefromgif( $tempFilename );
    else if( $extension == 'bmp' )
        $tempImage = imagecreatefrombmp( $tempFilename );

    $targetExt = ( $uploadType == "NEWS" ) ? ".jpg" : ".png";

    $nextImageFilename = file_get_contents( "ImageIter.txt" );
    settype( $nextImageFilename, "integer" );
    $nextImageFilenameStr = str_pad( $nextImageFilename, 6, "0", STR_PAD_LEFT ) . $targetExt;

    $newImageFilename = "Images/$nextImageFilenameStr";

    list($width, $height) = getimagesize( $tempFilename );

    //	Scale the resulting image to fit within the following limits:
    $maxImageSizeWidth = 160;
    $maxImageSizeHeight = 160;

    if( $uploadType == "NEWS" )
    {
        $maxImageSizeWidth = 530;
        $maxImageSizeHeight = 280;
    }
    else if( $uploadType == "GAME_ICON" ) //	ICON
    {
        $maxImageSizeWidth = 96;
        $maxImageSizeHeight = 96;
    }
    else if( $uploadType == "GAME_TITLE" || $uploadType == "GAME_INGAME" )  //	Screenshot
    {
        $maxImageSizeWidth = 320;
        $maxImageSizeHeight = 240;
    }
    else if( $uploadType == "GAME_BOXART" )
    {
        $maxImageSizeWidth = 320;
        $maxImageSizeHeight = 320;
    }


    $wScaling = 1.0;

    $targetWidth = $width;
    $targetHeight = $height;


    if( $targetWidth > $maxImageSizeWidth )
    {
        $wScaling = 1.0 / ( $targetWidth / $maxImageSizeWidth );
        //error_log( "WScaling is $wScaling, so width $targetWidth and height $targetHeight become..." );
        $targetWidth = $targetWidth * $wScaling;
        $targetHeight = $targetHeight * $wScaling;
        //error_log( "$targetWidth and $targetHeight" );
    }
    //	IF, after potentially being reduced, the height's still too big, scale again
    if( $targetHeight > $maxImageSizeHeight )
    {
        $vScaling = 1.0 / ( $targetHeight / $maxImageSizeHeight );
        //error_log( "VScaling is $vScaling, so width $targetWidth and height $targetHeight become..." );
        $targetWidth = $targetWidth * $vScaling;
        $targetHeight = $targetHeight * $vScaling;
        //error_log( "$targetWidth and $targetHeight" );
    }

    $newImage = imagecreatetruecolor( $targetWidth, $targetHeight );
    imagecopyresampled( $newImage, $tempImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height );

    if( $uploadType == "NEWS" )
    {
        $success = imagejpeg( $newImage, $newImageFilename );
    }
    else
    {
        $success = imagepng( $newImage, $newImageFilename );
    }


    if( $success == FALSE )
    {
        error_log( "uploadpicinline.php failed: Issues copying to $newImageFilename" );
        echo "Issues encountered - these have been reported and will be fixed - sorry for the inconvenience... please try another file!";
        exit;
    }
    else
    {
        UploadToS3( $newImageFilename, $newImage );

        //	Increment and save this new badge number for next time
        $thisImageIter = str_pad( $nextImageFilename, 6, "0", STR_PAD_LEFT );
        $newImageIter = str_pad( $nextImageFilename + 1, 6, "0", STR_PAD_LEFT );
        file_put_contents( "ImageIter.txt", $newImageIter );

        //error_log( $tempFilename );
        unlink( $tempFilename );

        if( $uploadType == "NEWS" )
        {
            echo "OK:/Images/" . $thisImageIter . $targetExt;
            exit;
        }
        else if( $uploadType == "GAME_ICON" || $uploadType == "GAME_TITLE" || $uploadType == "GAME_INGAME" || $uploadType == "GAME_BOXART" )
        {
            //	Associate new data, then return to game page:

            $param = '';
            if( $uploadType == "GAME_ICON" )
            {
                $param = 'ImageIcon';
            }
            else if( $uploadType == "GAME_TITLE" )
            {
                $param = 'ImageTitle';
            }
            else if( $uploadType == "GAME_INGAME" )
            {
                $param = 'ImageIngame';
            }
            else if( $uploadType == "GAME_BOXART" )
            {
                $param = 'ImageBoxArt';
            }

            $query = "UPDATE GameData AS gd
					  SET $param='/$newImageFilename'
					  WHERE gd.ID = $returnID";

            global $db;
            $dbResult = mysqli_query( $db, $query );

            if( $dbResult == FALSE )
            {
                error_log( $query );
                log_email( "uploadpicinline.php went wrong... $uploadType, $returnID" );
            }
            else
            {
                //error_log( $query );
                error_log( "Logged image update $uploadType to game $returnID, to image /$newImageFilename" );
            }

            header( "Location: http://" . AT_HOST . "/game/$returnID?e=uploadok" );
            exit;
        }
    }
}
else
{
    echo "Could not write temporary file?!";
    exit;
}
?>
