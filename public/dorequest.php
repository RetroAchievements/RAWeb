<?php
require_once __DIR__ . '/../lib/bootstrap.php';

//	Syntax:
//	request.php?r=addfriend&<params> (Web)
//	request.php?r=addfriend&u=user&t=token&<params> (From App)

$response = array( 'Success' => TRUE );

//	AVOID A G O C - these are now strongly typed as INT!
//	Global RESERVED vars:
$requestType = seekPOSTorGET( 'r' );
$user = seekPOSTorGET( 'u' );
$token = seekPOSTorGET( 't', NULL );
$achievementID = seekPOSTorGET( 'a', 0, 'integer' );  //	Keep in mind, this will overwrite anything given outside these params!!
$gameID = seekPOSTorGET( 'g', 0, 'integer' );
$offset = seekPOSTorGET( 'o', 0, 'integer' );
$count = seekPOSTorGET( 'c', 10, 'integer' );

//$bounceReferrer	= seekPOSTorGET( 'b' );	//	TBD: Remove!

$errorCode = "OK";

$validLogin = FALSE;

//	Be aware that if token or cookie are invalid, $user will be invalidated (NULLED) by RA_ReadCookieCredentials!
if( isset( $token ) /* && strlen( $token ) == 16 */ )
{
    $validLogin = RA_ReadTokenCredentials( $user, $token, $points, $truePoints, $unreadMessageCount, $permissions );
}
if( $validLogin == FALSE )
{
    $validLogin = RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
}
function DoRequestError( $errorMsg )
{
    global $response;
    $response[ 'Success' ] = FALSE;
    $response[ 'Error' ] = $errorMsg;

    global $user;
    global $requestType;
    error_log( "User: $user, Request: $requestType, Error: $errorMsg" );
}

//	Early exit if we need a valid login
$credentialsOK = true;
switch( $requestType )
{
    //	Registration required and user=local:
    case "achievementwondata":
    case "addfriend":
    case "awardachievement":
    case "getfriendlist":
    case "modifyfriend":
    case "patch":
    case "postactivity":
    case "removecomment":
    case "richpresencepatch":
    case "submitcodenote":
    case "submitgametitle":
    case "submitlbentry":
    case "unlocks":
    case "uploadachievement":
        $credentialsOK = $validLogin && ( $permissions >= \RA\Permissions::Registered );
        break;

    //	Developer status required:
    case "createnewlb":
    case "recalctrueratio":
    case "removelbentry":
        $credentialsOK = $validLogin && ( $permissions >= \RA\Permissions::Developer );
        break;

    default: //	Incl. Login!
        $credentialsOK = true; //	None required
        break;
}

if( $credentialsOK )
{
    //	Interrogate requirements:
    switch( $requestType )
    {
        //////////////////////////////////////////////////////////////////////////////////////////
        //	Login (special)

        case "login": //	From App!
            $user = seekPOSTorGET( 'u' );
            $rawPass = seekPOSTorGET( 'p' );
            $success = login_appWithToken( $user, $rawPass, $token, $scoreOut, $messagesOut );
            if( $success == 1 )
            {
                //	OK:
                $response[ 'User' ] = $user;
                $response[ 'Token' ] = $token;
                $response[ 'Score' ] = $scoreOut;
                $response[ 'Messages' ] = $messagesOut;
            }
            else
            {
                //	Token invalid or out of date
                DoRequestError( "Error with login! Please try again." );
            }
            break;


        //////////////////////////////////////////////////////////////////////////////////////////
        //	Global, no permissions required:

        case "allprogress":
            $consoleID = seekPOSTorGET( 'c' );
            $response[ 'Response' ] = GetAllUserProgress( $user, $consoleID );
            break;

        case "badgeiter":
            $response[ 'FirstBadge' ] = 80;
            $response[ 'NextBadge' ] = file_get_contents( "./BadgeIter.txt" );
            settype( $response[ 'NextBadge' ], 'integer' );
            break;

        //	Deprecated:
        case "codenotes":
            if( !getCodeNotes( $gameID, $codeNotesOut ) )
            {
                DoRequestError( "FAILED!" );
            }
            else
            {
                echo "OK:$gameID:";
                foreach( $codeNotesOut as $codeNote )
                {
                    if( strlen( $codeNote[ 'Note' ] ) > 2 )
                    {
                        $noteAdj = str_replace( "\n", "\r\n", $codeNote[ 'Note' ] );
                        echo $codeNote[ 'User' ] . ':' . $codeNote[ 'Address' ] . ':' . $noteAdj . "#";
                    }
                }
            }
            break;

        case "codenotes2":
            $response[ 'CodeNotes' ] = getCodeNotesData( $gameID );
            $response[ 'GameID' ] = $gameID;
            //error_log( "codenotes2, $gameID" );
            break;

        case "currentactivity": //requestcurrentlyactiveplayers
            $response[ 'CurrentActivity' ] = getLatestRichPresenceUpdates();
            break;

        case "currentlyonline":
            $response[ 'CurrentlyOnline' ] = getCurrentlyOnlinePlayers();
            break;

        case "developerstats":
            $response[ 'DeveloperStats' ] = GetDeveloperStats( 99, 0 );
            break;

        case "gameid":
            $md5 = seekPOSTorGET( 'm' );
            $response[ 'GameID' ] = GetGameIDFromMD5( $md5 );
            break;

        case "gameslist":
            $consoleID = seekPOSTorGET( 'c', 0, 'integer' );
            //error_log( "gameslist..." );
            $response[ 'Response' ] = GetGamesListDataNamesOnly( $consoleID );
            //error_log( count( $response['Response'] ) );
            break;

        case "officialgameslist":
            $consoleID = seekPOSTorGET( 'c', 0, 'integer' );
            $response[ 'Response' ] = GetGamesListDataNamesOnly( $consoleID, TRUE );
            break;

        case "hashlibrary":
            $consoleID = seekPOSTorGET( 'c', 0, 'integer' );
            $response[ 'MD5List' ] = GetMD5List( $consoleID );
            break;

        case "latestclient":
            $emulatorID = seekPOSTorGET( 'e' );
            $consoleID = seekPOSTorGET( 'c' );
            if( isset( $emulatorID ) )
            {
                switch( $emulatorID )
                {
                    case \RA\Emulators::RAGens:
                        $versionFile = "LatestRAGensVersion.html";
                        break;
                    case \RA\Emulators::RAP64:
                        $versionFile = "LatestRAP64Version.html";
                        break;
                    case \RA\Emulators::RASnes9x:
                        $versionFile = "LatestRASnesVersion.html";
                        break;
                    case \RA\Emulators::RAVBA:
                        $versionFile = "LatestRAVBAVersion.html";
                        break;
                    case \RA\Emulators::RANes:
                        $versionFile = "LatestRANESVersion.html";
                        break;
                    case \RA\Emulators::RAPCE:
                        $versionFile = "LatestRAPCEVersion.html";
                        break;
                    case \RA\Emulators::RALibretro:
                        $versionFile = "LatestRALibretroVersion.html";
                        break;
                    case \RA\Emulators::RAMeka:
                        $versionFile = "LatestRAMekaVersion.html";
                        break;
                    case \RA\Emulators::RAQUASI88:
                        $versionFile = "LatestRAQUASI88Version.html";
                        break;
                    case \RA\Emulators::RAppleWin:
                        $versionFile = "LatestRAppleWinVersion.html";
                        break;
                    default:
                        $versionFile = NULL;
                        $errMsg = "EmulatorID: $emulatorID";
                }
            }
            else
            {
                switch( $consoleID ) // keeping the previous behavior
                {
                    case 1:
                        $versionFile = "LatestRAGensVersion.html";
                        break;
                    case 2:
                        $versionFile = "LatestRAP64Version.html";
                        break;
                    case 3:
                        $versionFile = "LatestRASnesVersion.html";
                        break;
                    case 4:
                        $versionFile = "LatestRAVBAVersion.html";
                        break;
                    case 7:
                        $versionFile = "LatestRANESVersion.html";
                        break;
                    case 8:
                        $versionFile = "LatestRAPCEVersion.html";
                        break;
                    case 11:
                        $versionFile = "LatestRAMekaVersion.html";
                        break;
                    case 25:
                        $versionFile = "LatestRALibretroVersion.html";
                        break;
                    case 38:
                        $versionFile = "LatestRAppleWinVersion.html";
                        break;
                    case 47:
                        $versionFile = "LatestRAQUASI88Version.html";
                        break;
                    default:
                        $versionFile = NULL;
                        $errMsg = "ConsoleID: $consoleID";
                }
            }
            if( file_exists( $versionFile ) )
            {
                $response[ 'LatestVersion' ] = trim(preg_replace('/\s\s+/', ' ', file_get_contents( $versionFile )));
            }
            else
            {
                $errMsg = $errMsg ?? "File not found: $versionFile";
                DoRequestError( "Unknown client! (" . $errMsg . ")" );
            }
            break;

        case "news":
            $response[ 'News' ] = GetLatestNewsData( $offset, $count );
            break;

        case "ping":
            $activityMessage = seekPOST( 'm', NULL );
            $response[ 'Success' ] = userActivityPing( $user );

            if( isset( $activityMessage ) )
            {
                UpdateUserRichPresence( $user, $gameID, $activityMessage );
            }
            break;

        case "resetpassword":
            $username = seekPOSTorGET( 'u' );
            error_log( "ResetPassword, " . $username );
            $response[ 'Response' ] = RequestPasswordReset( $username );
            break;

        case "setpassword":
            // $username = seekPOSTorGET( 'u' );
            // $newPassword = seekPOSTorGET( 'p' );
            // //error_log( "SetPassword, " . $username . ", " . $newPassword );
            // $success = changePassword( $username, $newPassword );
            //
            // //  If changed OK, auto-login - doesn't appear to work?
            // //if( validateUser( $username, $newPassword, $fbUser, 0 ) )
            // //{
            // //    generateCookie( $user, $cookie );
            // //}
            // $response[ 'Success' ] = $success;
            // $response[ 'Cookie' ] = $cookie;
            $response[ 'Success' ] = false;
            $response[ 'Error' ] = 'Deprecated';
            break;

        case "score":
            $user = seekPOSTorGET( 'u' );
            $response[ 'Score' ] = GetScore( $user );
            $response[ 'User' ] = $user;
            break;

        case "staticdata":
            $response[ 'StaticData' ] = getStaticData();
            break;


        case "userpic":
            {
                //	Special case
                $targetUser = seekPOSTorGET( 'i' );
                $destURL = getenv('APP_URL')."/UserPic/$targetUser" . ".png";

                header( 'Content-type: image/png' );
                readfile( $destURL );
                exit; //	N.B.!
            }

        case "badge":
            {
                //	DO NOT USE: access URL directly please!
                //	Special case
                $badgeURI = seekPOSTorGET( 'i' );
                $destURL = getenv('APP_STATIC_URL') . "/Badge/$badgeURI" . ".png";

                header( 'Content-type: image/png' );
                readfile( $destURL );
                exit; //	N.B.!
            }



        //////////////////////////////////////////////////////////////////////////////////////////
        //	User-based (require credentials):

        case "achievementwondata":
            $friendsOnly = seekPOSTorGET( 'f', 0, 'integer' );
            $response[ 'Offset' ] = $offset;
            $response[ 'Count' ] = $count;
            $response[ 'FriendsOnly' ] = $friendsOnly;
            $response[ 'AchievementID' ] = $achievementID;
            $response[ 'Response' ] = getAchievementRecentWinnersData( $achievementID, $offset, $count, $user, $friendsOnly );
            break;

        case "addfriend":
            $newFriend = seekPOSTorGET( 'n' );
            $response[ 'Success' ] = AddFriend( $user, $newFriend );
            break;

        case "awardachievement":
            $validation = seekPOSTorGET( 'v' );
            $achIDToAward = seekPOSTorGET( 'a', 0, 'integer' );
            $hardcore = seekPOSTorGET( 'h', 0, 'integer' );
            //	Prefer later values, i.e. allow AddEarnedAchievementJSON to overwrite the 'success' key
            $response = array_merge( $response, AddEarnedAchievementJSON( $user, $achIDToAward, $hardcore, $validation ) );
            $response[ 'Score' ] = GetScore( $user );
            $response[ 'AchievementID' ] = $achIDToAward;
            break;

        case "createnewlb":
            $response[ 'Success' ] = SubmitNewLeaderboard( $gameID, $lbID );
            $response[ 'NewLeaderboardID' ] = $lbID;
            break;

        case "getfriendlist":
            $response[ 'Friends' ] = GetFriendList( $user );
            break;

        case "lbinfo":
            $lbID = seekPOSTorGET( 'i', 0, 'integer' );
            $friendsOnly = 0; //	TBD
            $response[ 'LeaderboardData' ] = GetLeaderboardData( $lbID, $user, $count, $offset, $friendsOnly );
            break;

        case "modifyfriend":
            $friend = seekPOSTorGET( 'f' );
            $action = seekPOSTorGET( 'a' );
            $response[ 'Response' ] = changeFriendStatus( $user, $friend, $action );
            break;

        case "patch":
            $flags = seekPOSTorGET( 'f', 0, 'integer' );
            $hardcore = seekPOSTorGET( 'h', 0, 'integer' );
            $response[ 'PatchData' ] = GetPatchData( $gameID, $flags, $user, $hardcore );
            break;

        case "postactivity":
            $activityType = seekPOSTorGET( 'a' );
            $activityMessage = seekPOSTorGET( 'm' );
            $response[ 'Success' ] = postActivity( $user, $activityType, $activityMessage );
            break;

        case "recalctrueratio":
            $response[ 'Success' ] = RecalculateTrueRatio( $gameID );
            break;

        case "removecomment":
            $articleID = seekPOSTorGET( 'a', 0, 'integer' );
            $commentID = seekPOSTorGET( 'c', 0, 'integer' );
            error_log( "$user authorised removing comment $commentID, type $articleID" );
            $response[ 'Success' ] = RemoveComment( $articleID, $commentID );
            $response[ 'ArtID' ] = $articleID;
            $response[ 'CommentID' ] = $commentID;
            break;

        case "removelbentry":
            $lbID = seekPOSTorGET( 'l', 0, 'integer' );
            $targetUser = seekPOSTorGET( 't' );
            error_log( "$user authorised dropping LB entry by $targetUser from LB $lbID" );
            $response[ 'Success' ] = RemoveLeaderboardEntry( $targetUser, $lbID );
            break;

        case "richpresencepatch":
            $response[ 'Success' ] = GetRichPresencePatch( $gameID, $richPresenceData );
            $response[ 'RichPresencePatch' ] = $richPresenceData;
            break;

        case "submitcodenote":
            $note = seekPOSTorGET( 'n' );
            $address = seekPOSTorGET( 'm', 0, 'integer' );
            $response[ 'Success' ] = submitCodeNote2( $user, $gameID, $address, $note );
            $response[ 'GameID' ] = $gameID;     //	Repeat this back to the caller?
            $response[ 'Address' ] = $address;    //	Repeat this back to the caller?
            $response[ 'Note' ] = $note;      //	Repeat this back to the caller?
            break;

        case "submitgametitle":
            $md5 = seekPOSTorGET( 'm' );
            $gameTitle = seekPOSTorGET( 'i' );
            $consoleID = seekPOSTorGET( 'c' );
            $response[ 'Response' ] = SubmitNewGameTitleJSON( $user, $md5, $gameTitle, $consoleID );
            $response[ 'Success' ] = $response[ 'Response' ][ 'Success' ]; //	Passthru
            if( isset( $response[ 'Response' ][ 'Error' ] ) )
                $response[ 'Error' ] = $response[ 'Response' ][ 'Error' ];
            break;

        case "submitlbentry":
            $lbID = seekPOSTorGET( 'i', 0, 'integer' );
            $score = seekPOSTorGET( 's', 0, 'integer' );
            $validation = seekPOSTorGET( 'v' ); //	Ignore for now?
            $response[ 'Response' ] = SubmitLeaderboardEntryJSON( $user, $lbID, $score, $validation );
            $response[ 'Success' ] = $response[ 'Response' ][ 'Success' ]; //	Passthru
            if( $response[ 'Success' ] == FALSE )
                $response[ 'Error' ] = $response[ 'Response' ][ 'Error' ];
            break;

        case "submitticket":
            $idCSV = seekPOSTorGET( 'i' );
            $problemType = seekPOSTorGET( 'p' );
            $comment = seekPOSTorGET( 'n' );
            $md5 = seekPOSTorGET( 'm' );
            $response[ 'Response' ] = submitNewTicketsJSON( $user, $idCSV, $problemType, $comment, $md5 );
            $response[ 'Success' ] = $response[ 'Response' ][ 'Success' ]; //	Passthru
            if( isset( $response[ 'Response' ][ 'Error' ] ) )
                $response[ 'Error' ] = $response[ 'Response' ][ 'Error' ];
            break;

        case "unlocks":
            $hardcoreMode = seekPOSTorGET( 'h', 0, 'integer' );
            $response[ 'UserUnlocks' ] = GetUserUnlocksData( $user, $gameID, $hardcoreMode );
            $response[ 'GameID' ] = $gameID;     //	Repeat this back to the caller?
            $response[ 'HardcoreMode' ] = $hardcoreMode;  //	Repeat this back to the caller?
            settype( $response[ 'HardcoreMode' ], 'boolean' );
            break;

        case "uploadachievement":
            //	Needs completely redoing from the app side!
            $newTitle = seekPOSTorGET( 'n' );
            $newDesc = seekPOSTorGET( 'd' );
            $newPoints = seekPOSTorGET( 'z', 0, 'integer' );
            $newMemString = seekPOSTorGET( 'm' );
            $newFlags = seekPOSTorGET( 'f', 0, 'integer' );
            $newBadge = seekPOSTorGET( 'b' );
            $errorOut = "";
            $response[ 'Success' ] = UploadNewAchievement( $user, $gameID, $newTitle, $newDesc, ' ', ' ', ' ', $newPoints, $newMemString, $newFlags, $achievementID, $newBadge, $errorOut );
            $response[ 'AchievementID' ] = $achievementID;
            $response[ 'Error' ] = $errorOut;
            break;

        default:
            DoRequestError( "Unknown Request: '" . $requestType . "'" );
            break;
    }
}
else
{
    DoRequestError( "Credentials invalid ($permissions)" );
}

settype( $response[ 'Success' ], 'boolean' );
echo json_encode( $response );
