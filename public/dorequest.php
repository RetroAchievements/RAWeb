<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

/**
 * @usage
 * dorequest.php?r=addfriend&<params> (Web)
 * dorequest.php?r=addfriend&u=user&t=token&<params> (From App)
 */
$response = ['Success' => true];

/**
 * AVOID A G O C - these are now strongly typed as INT!
 * Global RESERVED vars:
 */
$requestType = requestInput('r');
$user = requestInput('u');
$token = requestInput('t', null);
$achievementID = requestInput('a', 0, 'integer');  // Keep in mind, this will overwrite anything given outside these params!!
$gameID = requestInput('g', 0, 'integer');
$offset = requestInput('o', 0, 'integer');
$count = requestInput('c', 10, 'integer');

//$bounceReferrer = requestInput( 'b' ); // TBD: Remove!

$errorCode = "OK";

$validLogin = false;
$permissions = null;
if (!empty($token) /* && strlen( $token ) == 16 */) {
    $validLogin = RA_ReadTokenCredentials($user, $token, $points, $truePoints, $unreadMessageCount, $permissions);
}

function DoRequestError($errorMsg)
{
    global $response;
    $response['Success'] = false;
    $response['Error'] = $errorMsg;
}

/**
 * RAIntegration implementation
 * https://github.com/RetroAchievements/RAIntegration/blob/master/src/api/impl/ConnectedServer.cpp
 */

/**
 * Early exit if we need a valid login
 */
$credentialsOK = true;
switch ($requestType) {
    /**
     * Registration required and user=local
     */
    // case "addfriend":
    // case "modifyfriend":
    // case "removecomment":
    case "achievementwondata":
    case "awardachievement":
    case "getfriendlist":
    case "patch":
    case "postactivity":
    case "richpresencepatch":
    case "submitcodenote":
    case "submitgametitle":
    case "submitlbentry":
    case "unlocks":
    case "uploadachievement":
        $credentialsOK = $validLogin && ($permissions >= \RA\Permissions::Registered);
        break;

    /**
     * Developer status required
     */
    // case "createnewlb":
    // case "recalctrueratio":
    // case "removelbentry":
    //     $credentialsOK = $validLogin && ($permissions >= \RA\Permissions::Developer);
    //     break;

    /**
     * Anything else is public. Includes login
     */
    default:
        $credentialsOK = true;
        break;
}

if (!$credentialsOK) {
    DoRequestError("Credentials invalid ($permissions)");
    settype($response['Success'], 'boolean');
    echo json_encode($response);
    exit;
}

switch ($requestType) {
    /**
     * Login
     */
    case "login": // From App!
        $user = requestInput('u');
        $rawPass = requestInput('p');
        $success = login_appWithToken($user, $rawPass, $token, $scoreOut, $messagesOut);
        if ($success == 1) {
            // OK:
            $response['User'] = $user;
            $response['Token'] = $token;
            $response['Score'] = $scoreOut;
            $response['Messages'] = $messagesOut;
        } else {
            /**
             * Token invalid or out of date
             */
            DoRequestError("Error with login! Please try again.");
        }
        break;

    /**
     * Global, no permissions required
     */
    case "allprogress":
        $consoleID = requestInput('c', null, 'integer');
        $response['Response'] = GetAllUserProgress($user, $consoleID);
        break;

    case "badgeiter":
        $response['FirstBadge'] = 80;
        $response['NextBadge'] = file_get_contents(__DIR__ . "/BadgeIter.txt");
        settype($response['NextBadge'], 'integer');
        break;

    // TODO: Deprecate - not used anymore
    case "codenotes":
        if (!getCodeNotes($gameID, $codeNotesOut)) {
            DoRequestError("FAILED!");
        } else {
            echo "OK:$gameID:";
            foreach ($codeNotesOut as $codeNote) {
                if (mb_strlen($codeNote['Note']) > 2) {
                    $noteAdj = str_replace("\n", "\r\n", $codeNote['Note']);
                    echo $codeNote['User'] . ':' . $codeNote['Address'] . ':' . $noteAdj . "#";
                }
            }
        }
        break;

    case "codenotes2":
        $response['CodeNotes'] = getCodeNotesData($gameID);
        $response['GameID'] = $gameID;
        //error_log( "codenotes2, $gameID" );
        break;

    // case "currentactivity": //requestcurrentlyactiveplayers
    //     $response['CurrentActivity'] = getLatestRichPresenceUpdates();
    //     break;
    // case "currentlyonline":
    //     $response['CurrentlyOnline'] = getCurrentlyOnlinePlayers();
    //     break;
    // case "developerstats":
    //     $response['DeveloperStats'] = GetDeveloperStats(99, 0);
    //     break;

    case "gameid":
        $md5 = requestInput('m');
        $response['GameID'] = getGameIDFromMD5($md5);
        break;

    case "gameslist":
        $consoleID = requestInput('c', 0, 'integer');
        $response['Response'] = getGamesListDataNamesOnly($consoleID);
        break;

    case "officialgameslist":
        $consoleID = requestInput('c', 0, 'integer');
        $response['Response'] = getGamesListDataNamesOnly($consoleID, true);
        break;

    case "hashlibrary":
        $consoleID = requestInput('c', 0, 'integer');
        $response['MD5List'] = getMD5List($consoleID);
        break;

    case "latestclient":
        $emulatorId = requestInput('e');
        $consoleId = requestInput('c');

        if ($emulatorId === null && $consoleId !== null) {
            DoRequestError("Lookup by Console ID has been deprecated");
            break;
        }

        $emulator = getEmulatorReleaseByIntegrationId($emulatorId);

        if ($emulator === null) {
            DoRequestError("Unknown client");
            break;
        }
        $baseDownloadUrl = str_replace('https', 'http', getenv('APP_URL')) . '/';
        $response['MinimumVersion'] = $emulator['minimum_version'] ?? null;
        $response['LatestVersion'] = $emulator['latest_version'] ?? null;
        $response['LatestVersionUrl'] = $baseDownloadUrl . $emulator['latest_version_url'] ?? null;
        $response['LatestVersionUrlX64'] = ($emulator['latest_version_url_x64'] ?? null) ? $baseDownloadUrl . $emulator['latest_version_url_x64'] : null;
        break;

    case "latestintegration":
        $integration = getIntegrationRelease();
        if (!$integration) {
            DoRequestError("Unknown client");
            break;
        }
        $baseDownloadUrl = str_replace('https', 'http', getenv('APP_URL')) . '/';
        $response['MinimumVersion'] = $integration['minimum_version'] ?? null;
        $response['LatestVersion'] = $integration['latest_version'] ?? null;
        $response['LatestVersionUrl'] = ($integration['latest_version_url'] ?? null)
            ? $baseDownloadUrl . $integration['latest_version_url']
            : 'http://retroachievements.org/bin/RA_Integration.dll';
        $response['LatestVersionUrlX64'] = ($integration['latest_version_url_x64'] ?? null)
            ? $baseDownloadUrl . $integration['latest_version_url_x64']
            : 'http://retroachievements.org/bin/RA_Integration-x64.dll';
        break;

    // case "news":
    //     $response['News'] = GetLatestNewsData($offset, $count);
    //     break;

    case "ping":
        $activityMessage = requestInputPost('m', null);
        $response['Success'] = userActivityPing($user);

        if (isset($activityMessage)) {
            UpdateUserRichPresence($user, $gameID, $activityMessage);
        }
        break;

    // case "resetpassword":
    //     $username = requestInput('u');
    //     $response['Response'] = RequestPasswordReset($username);
    //     break;
    // case "setpassword":
    //     $username = requestInput( 'u' );
    //     $newPassword = requestInput( 'p' );
    //     $success = changePassword( $username, $newPassword );
    //
    //     //  If changed OK, auto-login - doesn't appear to work?
    //     //if( validateUser( $username, $newPassword, $fbUser, 0 ) )
    //     //{
    //     //    generateCookie( $user, $cookie );
    //     //}
    //     $response[ 'Success' ] = $success;
    //     $response[ 'Cookie' ] = $cookie;
    //     DoRequestError('Deprecated');
    //     break;
    // case "score":
    //     $user = requestInput('u');
    //     $response['Score'] = GetScore($user);
    //     $response['User'] = $user;
    //     break;
    // case "staticdata":
    //     $response['StaticData'] = getStaticData();
    //     break;

    // case "userpic":
    // {
    //     // Special case
    //     $targetUser = requestInput('i');
    //     $destURL = getenv('APP_URL') . "/UserPic/$targetUser" . ".png";
    //
    //     header('Content-type: image/png');
    //     readfile($destURL);
    //     exit;
    // }
    // case "badge":
    // {
    //     // DO NOT USE: access URL directly please!
    //     // Special case
    //     $badgeURI = requestInput('i');
    //     $destURL = getenv('ASSET_URL') . "/Badge/$badgeURI" . ".png";
    //
    //     header('Content-type: image/png');
    //     readfile($destURL);
    //     exit;
    // }

    /**
     * User-based (require credentials)
     */
    case "achievementwondata":
        $friendsOnly = requestInput('f', 0, 'integer');
        $response['Offset'] = $offset;
        $response['Count'] = $count;
        $response['FriendsOnly'] = $friendsOnly;
        $response['AchievementID'] = $achievementID;
        $response['Response'] = getAchievementRecentWinnersData($achievementID, $offset, $count, $user, $friendsOnly);
        break;

    // case "addfriend":
    //     $newFriend = requestInput('n');
    //     $response['Success'] = AddFriend($user, $newFriend);
    //     break;

    case "awardachievement":
        $achIDToAward = requestInput('a', 0, 'integer');
        $hardcore = requestInput('h', 0, 'integer');
        /**
         * Prefer later values, i.e. allow AddEarnedAchievementJSON to overwrite the 'success' key
         */
        $response = array_merge($response, addEarnedAchievementJSON($user, $achIDToAward, $hardcore));
        $response['Score'] = GetScore($user);
        $response['AchievementID'] = $achIDToAward;
        break;

    // case "createnewlb":
    //     $response['Success'] = SubmitNewLeaderboard($gameID, $lbID);
    //     $response['NewLeaderboardID'] = $lbID;
    //     break;

    case "getfriendlist":
        $response['Friends'] = GetFriendList($user);
        break;

    case "lbinfo":
        $lbID = requestInput('i', 0, 'integer');
        $friendsOnly = 0; // TBD
        $response['LeaderboardData'] = GetLeaderboardData($lbID, $user, $count, $offset, $friendsOnly);
        break;

    // case "modifyfriend":
    //     $friend = requestInput('f');
    //     $action = requestInput('a');
    //     $response['Response'] = changeFriendStatus($user, $friend, $action);
    //     break;

    case "patch":
        $flags = requestInput('f', 0, 'integer');
        // $hardcore = requestInput('h', 0, 'integer'); // not used
        $response['PatchData'] = GetPatchData($gameID, $flags, $user);
        break;

    case "postactivity":
        $activityType = requestInput('a');
        $activityMessage = requestInput('m');
        $response['Success'] = postActivity($user, $activityType, $activityMessage);
        break;

    // case "recalctrueratio":
    //     $response['Success'] = recalculateTrueRatio($gameID);
    //     break;
    // case "removecomment":
    //     $articleID = requestInput('a', 0, 'integer');
    //     $commentID = requestInput('c', 0, 'integer');
    //     $response['Success'] = RemoveComment($articleID, $commentID);
    //     $response['ArtID'] = $articleID;
    //     $response['CommentID'] = $commentID;
    //     break;
    // case "removelbentry":
    //     $lbID = requestInput('l', 0, 'integer');
    //     $targetUser = requestInput('t');
    //     // error_log("$user authorised dropping LB entry by $targetUser from LB $lbID");
    //     $response['Success'] = RemoveLeaderboardEntry($targetUser, $lbID);
    //     break;

    case "richpresencepatch":
        $response['Success'] = getRichPresencePatch($gameID, $richPresenceData);
        $response['RichPresencePatch'] = $richPresenceData;
        break;

    case "submitcodenote":
        $note = requestInput('n');
        $address = requestInput('m', 0, 'integer');
        $response['Success'] = submitCodeNote2($user, $gameID, $address, $note);
        $response['GameID'] = $gameID;     // Repeat this back to the caller?
        $response['Address'] = $address;    // Repeat this back to the caller?
        $response['Note'] = $note;      // Repeat this back to the caller?
        break;

    case "submitgametitle":
        $md5 = requestInput('m');
        $gameID = requestInput('g');
        $gameTitle = requestInput('i');
        $description = requestInput('d');
        $consoleID = requestInput('c');
        $response['Response'] = submitNewGameTitleJSON($user, $md5, $gameID, $gameTitle, $consoleID);
        $response['Success'] = $response['Response']['Success']; // Passthru
        if (isset($response['Response']['Error'])) {
            $response['Error'] = $response['Response']['Error'];
        }
        break;

    case "submitlbentry":
        $lbID = requestInput('i', 0, 'integer');
        $score = requestInput('s', 0, 'integer');
        $validation = requestInput('v'); // Ignore for now?
        $response['Response'] = SubmitLeaderboardEntryJSON($user, $lbID, $score, $validation);
        $response['Success'] = $response['Response']['Success']; // Passthru
        if ($response['Success'] == false) {
            $response['Error'] = $response['Response']['Error'];
        }
        break;

    case "submitticket":
        $idCSV = requestInput('i');
        $problemType = requestInput('p');
        $comment = requestInput('n');
        $md5 = requestInput('m');
        $response['Response'] = submitNewTicketsJSON($user, $idCSV, $problemType, $comment, $md5);
        $response['Success'] = $response['Response']['Success']; // Passthru
        if (isset($response['Response']['Error'])) {
            $response['Error'] = $response['Response']['Error'];
        }
        break;

    case "unlocks":
        $hardcoreMode = requestInput('h', 0, 'integer');
        $response['UserUnlocks'] = GetUserUnlocksData($user, $gameID, $hardcoreMode);
        $response['GameID'] = $gameID;     // Repeat this back to the caller?
        $response['HardcoreMode'] = $hardcoreMode;  // Repeat this back to the caller?
        settype($response['HardcoreMode'], 'boolean');
        break;

    case "uploadachievement":
        $newTitle = requestInput('n');
        $newDesc = requestInput('d');
        $newPoints = requestInput('z', 0, 'integer');
        $newMemString = requestInput('m');
        $newFlags = requestInput('f', 0, 'integer');
        $newBadge = requestInput('b');
        $errorOut = "";
        $response['Success'] = UploadNewAchievement($user, $gameID, $newTitle, $newDesc, ' ', ' ', ' ', $newPoints, $newMemString, $newFlags, $achievementID, $newBadge, $errorOut);
        $response['AchievementID'] = $achievementID;
        $response['Error'] = $errorOut;
        break;

    default:
        DoRequestError("Unknown Request: '" . $requestType . "'");
        break;
}

settype($response['Success'], 'boolean');
echo json_encode($response);
