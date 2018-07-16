<?php
require_once __DIR__ . '/../lib/bootstrap.php';

//	Syntax:
//	doupload.php?r=uploadbadgeimage&<params> (Web)
//	doupload.php?r=uploadbadgeimage&u=user&t=token&<params> (From App)

$response = array( 'Success' => true );

//	Global RESERVED vars:
$requestType = seekPOSTorGET( 'r' );
$user = seekPOSTorGET( 'u' );
$token = seekPOSTorGET( 't' );

$bounceReferrer = seekPOSTorGET( 'b' ); //	TBD: Remove!

$validLogin = FALSE;

if( isset( $token ) )
{
    $validLogin = RA_ReadTokenCredentials( $user, $token, $points, $truePoints, $unreadMessageCount, $permissions );
}
if( $validLogin == FALSE )
{
    $validLogin = RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
}

use Aws\S3\S3Client;

function UploadToS3( $filenameDest, $rawFile )
{
    $client = new S3Client([
		'region' => getenv('AWS_DEFAULT_REGION'),
		'version' => 'latest'
    ]);

    // Register the stream wrapper from a client object
    //$client->registerStreamWrapper();
    //$url = "s3://i.retroachievements.org/$filenameDest";

	$result = $client->putObject([
		'Bucket' => getenv('AWS_BUCKET'),
		'Key' => "$filenameDest",
		'Body' => fopen($filenameDest, 'r+'),
	]);

    //$ok = imagepng( $rawFile, $url );
    if( $result )
    {
        error_log( "Successfully uploaded $filenameDest to S3!" );
    }
    else
    {
        error_log( "FAILED to upload $filenameDest to S3!" );
    }
}

//	08:38 28/10/2014
function UploadUserPic( $user, $filename, $rawImage )
{
    $response = array();

    $response[ 'Filename' ] = $filename;
    $response[ 'User' ] = $user;

    //$filename = seekPOST( 'f' );
    //$rawImage = seekPOST( 'i' );
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
    error_log( $tempFilename );

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
            $tempImage = imagecreatefrombitmap( $tempFilename );

        $userPicDestSize = 128;

        if( IsAtHome() )
            $existingUserFile = "UserPic/$user.png";
        else
            $existingUserFile = "./UserPic/$user.png";

        $newImage = imagecreatetruecolor( $userPicDestSize, $userPicDestSize );
        //$existingImage = imagecreatefrompng( $existingUserFile );
        //	Create a black rect, size 128x128
        $blackRect = imagecreatetruecolor( $userPicDestSize, $userPicDestSize )
                or die( 'Cannot Initialize new GD image stream' );

        //	Copy the black rect onto our image
        imagecopy( $newImage, $blackRect, 0, 0, 0, 0, $userPicDestSize, $userPicDestSize );

        //	Reduce the input file size
        list($givenImageWidth, $givenImageHeight) = getimagesize( $tempFilename );
        //error_log( "Given Image W/H is $givenImageWidth, $givenImageHeight, dest size is $userPicDestSize");

        imagecopyresampled( $newImage, $tempImage, 0, 0, 0, 0, $userPicDestSize, $userPicDestSize, $givenImageWidth, $givenImageHeight );

        $success = imagepng( $newImage, $existingUserFile );

        if( $success == FALSE )
        {
            error_log( "UploadUserPic failed: Issues copying from $tempFile to UserPic/$user.png" );
            $response[ 'Error' ] = "Issues copying from $tempFile to UserPic/$user.png";
            //echo "Issues encountered - these have been reported and will be fixed - sorry for the inconvenience... please try another file!";
        }
        else
        {
            //	Done OK
            //echo 'OK';
            //header( "Location: " . getenv('APP_URL') . "/manageuserpic.php?e=success" );
        }
    }

    $response[ 'Success' ] = $success;
    return $response;
}

//	08:56 28/10/2014
function UploadBadgeImage( $file )
{
    error_log( "UploadBadgeImage" );

    $response = array();

    $filename = $file[ "name" ];
    $filesize = $file[ "size" ];
    $fileerror = $file[ "error" ];
    $fileTempName = $file[ "tmp_name" ];

    $response[ 'Filename' ] = $filename;
    $response[ 'Size' ] = $filesize;

    $allowedExts = array( "png", "jpeg", "jpg", "gif" );
    $filenameParts = explode( ".", $filename );
    $extension = strtolower( end( $filenameParts ) );

    if( $filesize > 1048576 )
    {
        $response[ 'Error' ] = "Error: image too big ($filesize)! Must be smaller than 1mb!";
    }
    else if( !in_array( $extension, $allowedExts ) )
    {
        $response[ 'Error' ] = "Error: image type ($extension) not supported! Supported types: .png, .jpg, .jpeg, .gif";
    }
    else if( $fileerror )
    {
        if( $fileerror == UPLOAD_ERR_INI_SIZE )
            $response[ 'Error' ] = "Error: file too big! Must be smaller than 1mb please.";
        else
            $response[ 'Error' ] = "Error: $fileerror<br/>";
    }
    else
    {
        $nextBadgeFilename = file_get_contents( "BadgeIter.txt" );
        settype( $nextBadgeFilename, "integer" );

        //	Produce filenames

        $newBadgeFilenameFormatted = str_pad( $nextBadgeFilename, 5, "0", STR_PAD_LEFT );

        $destBadgeFile = "Badge/" . "$newBadgeFilenameFormatted" . ".png";
        $destBadgeFileLocked = "Badge/" . "$newBadgeFilenameFormatted" . "_lock.png";
        //$destBadgeFileBig = "Badge/" . "$newBadgeFilenameFormatted" . "_big.png";
        //$destBadgeFileSmall = "Badge/" . "$newBadgeFilenameFormatted" . "_small.png";
        //$destBadgeFileLockedSmall = "Badge/" . "$newBadgeFilenameFormatted" . "_locksmall.png";
        //	Fetch file and width/height

        if( $extension == 'png' )
            $tempImage = imagecreatefrompng( $fileTempName );
        else if( $extension == 'jpg' || $extension == 'jpeg' )
            $tempImage = imagecreatefromjpeg( $fileTempName );
        else if( $extension == 'gif' )
            $tempImage = imagecreatefromgif( $fileTempName );

        list($width, $height) = getimagesize( $fileTempName );

        //	Create all images
        $smallPx = 32;
        $normalPx = 64;
        $largePx = 128;

        //$newSmallImage 		 = imagecreatetruecolor($smallPx, $smallPx);
        $newImage = imagecreatetruecolor( $normalPx, $normalPx );
        //$newLargeImage 		 = imagecreatetruecolor($largePx, $largePx);
        //$newSmallImageLocked = imagecreatetruecolor($smallPx, $smallPx);
        $newImageLocked = imagecreatetruecolor( $normalPx, $normalPx );

        //	Copy source to dest for all imaegs
        //imagecopyresampled($newSmallImage, 	$tempImage, 0, 0, 0, 0, $smallPx, $smallPx, $width, $height);
        imagecopyresampled( $newImage, $tempImage, 0, 0, 0, 0, $normalPx, $normalPx, $width, $height );
        //imagecopyresampled($newLargeImage, 	$tempImage, 0, 0, 0, 0, $largePx, $largePx, $width, $height);

        imagecopyresampled( $newImageLocked, $tempImage, 0, 0, 0, 0, $normalPx, $normalPx, $width, $height );
        imagefilter( $newImageLocked, IMG_FILTER_GRAYSCALE );
        imagefilter( $newImageLocked, IMG_FILTER_CONTRAST, 20 );
        imagefilter( $newImageLocked, IMG_FILTER_GAUSSIAN_BLUR );

        //imagecopyresampled($newSmallImageLocked, $tempImage, 0, 0, 0, 0, $smallPx, $smallPx, $width, $height);
        //imagefilter( $newSmallImageLocked, IMG_FILTER_GRAYSCALE );
        //imagefilter( $newSmallImageLocked, IMG_FILTER_CONTRAST, 20 );
        ////imagefilter( $newSmallImageLocked, IMG_FILTER_GAUSSIAN_BLUR );

        $success = //imagepng( $newLargeImage, $destBadgeFileBig ) &&
                //imagepng( $newSmallImage, $destBadgeFileSmall ) &&
                //imagepng( $newSmallImageLocked, $destBadgeFileLockedSmall ) &&
                imagepng( $newImage, $destBadgeFile ) &&
                imagepng( $newImageLocked, $destBadgeFileLocked );

        if( $success == FALSE )
        {
            error_log( "UploadUserPic.php failed: Issues copying from $tempFileRawImage to $destBadgeFile" );
            $response[ 'Error' ] = "Issues encountered - these have been reported and will be fixed - sorry for the inconvenience... please try another file!";
        }
        else
        {
            UploadToS3( $destBadgeFile, $newImage );
            UploadToS3( $destBadgeFileLocked, $newImageLocked );

            $newBadgeContent = str_pad( $nextBadgeFilename, 5, "0", STR_PAD_LEFT );
            //echo "OK:$newBadgeContent";
            $response[ 'BadgeIter' ] = $newBadgeContent;

            //	Increment and save this new badge number for next time
            $newBadgeContent = str_pad( $nextBadgeFilename + 1, 5, "0", STR_PAD_LEFT );
            file_put_contents( "BadgeIter.txt", $newBadgeContent );
        }
    }

    $response[ 'Success' ] = !isset( $response[ 'Error' ] );
    return $response;
}

//	08:20 31/10/2014
function DoRequestError( $errorMsg )
{
    global $response;
    $response[ 'Success' ] = FALSE;
    $response[ 'Error' ] = $errorMsg;

    global $user;
    global $requestType;
    error_log( "User: $user, Request$requestType: $errorMsg" );
}

//  Infer from app
if( isset( $_FILES[ "file" ] ) && isset( $_FILES[ "file" ][ "name" ] ) )
{
    $requestType = substr( $_FILES[ "file" ][ "name" ], 0, -4 );
    error_log( "RT: " . $requestType );
}
//error_log( "doupload.php" );
//error_log( print_r( $_FILES, true ) );
//	Interrogate requirements:
switch( $requestType )
{
    case "uploadbadgeimage":
        $response[ 'Response' ] = UploadBadgeImage( $_FILES[ "file" ] );
        break;

    case "uploaduserpic":
        $filename = seekPOSTorGET( 'f' );
        $rawImage = seekPOSTorGET( 'i' );
        $response[ 'Response' ] = UploadUserPic( $user, $filename, $rawImage );
        break;

    default:
        DoRequestError( "Unknown Request: '" . $requestType . "'" );
        break;
}

settype( $response[ 'Success' ], 'boolean' );
echo json_encode( $response );

exit;
?>
