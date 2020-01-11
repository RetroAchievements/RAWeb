<?php
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
$requestType = seekPOSTorGET('r');
$user = seekPOSTorGET('u');
$token = seekPOSTorGET('t', null);
$achievementID = seekPOSTorGET('a', 0, 'integer');  // Keep in mind, this will overwrite anything given outside these params!!
$gameID = seekPOSTorGET('g', 0, 'integer');
$offset = seekPOSTorGET('o', 0, 'integer');
$count = seekPOSTorGET('c', 10, 'integer');

//$bounceReferrer = seekPOSTorGET( 'b' ); // TBD: Remove!

$errorCode = "OK";

$validLogin = false;

/**
 * Be aware that if token or cookie are invalid, $user will be invalidated (NULLED) by RA_ReadCookieCredentials!
 */
if (isset($token) /* && strlen( $token ) == 16 */) {
    $validLogin = RA_ReadTokenCredentials($user, $token, $points, $truePoints, $unreadMessageCount, $permissions);
}
if ($validLogin == false) {
    $validLogin = RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);
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
    case "achievementwondata":
    // case "addfriend":
    case "awardachievement":
    case "getfriendlist":
    // case "modifyfriend":
    case "patch":
    case "postactivity":
    // case "removecomment":
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

if ($credentialsOK) {
    switch ($requestType) {
        /**
         * Login
         */
        case "login": // From App!
            $user = seekPOSTorGET('u');
            $rawPass = seekPOSTorGET('p');
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
            $consoleID = seekPOSTorGET('c');
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
            $md5 = seekPOSTorGET('m');
            $response['GameID'] = getGameIDFromMD5($md5);
            break;

        case "gameslist":
            $consoleID = seekPOSTorGET('c', 0, 'integer');
            $response['Response'] = getGamesListDataNamesOnly($consoleID);
            break;

        case "officialgameslist":
            $consoleID = seekPOSTorGET('c', 0, 'integer');
            $response['Response'] = getGamesListDataNamesOnly($consoleID, true);
            break;

        case "hashlibrary":
            $consoleID = seekPOSTorGET('c', 0, 'integer');
            $response['MD5List'] = getMD5List($consoleID);
            break;

        case "latestclient":
            $emulatorId = seekPOSTorGET('e');
            $consoleId = seekPOSTorGET('c');

            /**
             * Keep backwards compatible behaviour by mapping console ID to emulator/integration ID
             */
            if ($consoleId !== null) {
                $emulatorId = getEmulatorIdByConsoleId($consoleId);
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
            $response['LatestVersionUrl'] = $baseDownloadUrl . $integration['latest_version_url'] ?? null;
            $response['LatestVersionUrlX64'] = ($integration['latest_version_url_x64'] ?? null) ? $baseDownloadUrl . $integration['latest_version_url_x64'] : null;
            break;

        // case "news":
        //     $response['News'] = GetLatestNewsData($offset, $count);
        //     break;

        case "ping":
            $activityMessage = seekPOST('m', null);
            $response['Success'] = userActivityPing($user);

            if (isset($activityMessage)) {
                UpdateUserRichPresence($user, $gameID, $activityMessage);
            }
            break;

        // case "resetpassword":
        //     $username = seekPOSTorGET('u');
        //     $response['Response'] = RequestPasswordReset($username);
        //     break;
        // case "setpassword":
        //     $username = seekPOSTorGET( 'u' );
        //     $newPassword = seekPOSTorGET( 'p' );
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
        //     $user = seekPOSTorGET('u');
        //     $response['Score'] = GetScore($user);
        //     $response['User'] = $user;
        //     break;
        // case "staticdata":
        //     $response['StaticData'] = getStaticData();
        //     break;

        // case "userpic":
        // {
        //     // Special case
        //     $targetUser = seekPOSTorGET('i');
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
        //     $badgeURI = seekPOSTorGET('i');
        //     $destURL = getenv('APP_STATIC_URL') . "/Badge/$badgeURI" . ".png";
        //
        //     header('Content-type: image/png');
        //     readfile($destURL);
        //     exit;
        // }


        /**
         * User-based (require credentials)
         */
        case "achievementwondata":
            $friendsOnly = seekPOSTorGET('f', 0, 'integer');
            $response['Offset'] = $offset;
            $response['Count'] = $count;
            $response['FriendsOnly'] = $friendsOnly;
            $response['AchievementID'] = $achievementID;
            $response['Response'] = getAchievementRecentWinnersData($achievementID, $offset, $count, $user, $friendsOnly);
            break;

        // case "addfriend":
        //     $newFriend = seekPOSTorGET('n');
        //     $response['Success'] = AddFriend($user, $newFriend);
        //     break;

        case "awardachievement":
            $validation = seekPOSTorGET('v');
            $achIDToAward = seekPOSTorGET('a', 0, 'integer');
            $hardcore = seekPOSTorGET('h', 0, 'integer');
            /**
             * Prefer later values, i.e. allow AddEarnedAchievementJSON to overwrite the 'success' key
             */
            $response = array_merge($response, AddEarnedAchievementJSON($user, $achIDToAward, $hardcore, $validation));
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
            $lbID = seekPOSTorGET('i', 0, 'integer');
            $friendsOnly = 0; // TBD
            $response['LeaderboardData'] = GetLeaderboardData($lbID, $user, $count, $offset, $friendsOnly);
            break;

        // case "modifyfriend":
        //     $friend = seekPOSTorGET('f');
        //     $action = seekPOSTorGET('a');
        //     $response['Response'] = changeFriendStatus($user, $friend, $action);
        //     break;

        case "patch":
            $flags = seekPOSTorGET('f', 0, 'integer');
            $hardcore = seekPOSTorGET('h', 0, 'integer');
            $response['PatchData'] = GetPatchData($gameID, $flags, $user, $hardcore);
            break;

        case "postactivity":
            $activityType = seekPOSTorGET('a');
            $activityMessage = seekPOSTorGET('m');
            $response['Success'] = postActivity($user, $activityType, $activityMessage);
            break;

        // case "recalctrueratio":
        //     $response['Success'] = recalculateTrueRatio($gameID);
        //     break;
        // case "removecomment":
        //     $articleID = seekPOSTorGET('a', 0, 'integer');
        //     $commentID = seekPOSTorGET('c', 0, 'integer');
        //     $response['Success'] = RemoveComment($articleID, $commentID);
        //     $response['ArtID'] = $articleID;
        //     $response['CommentID'] = $commentID;
        //     break;
        // case "removelbentry":
        //     $lbID = seekPOSTorGET('l', 0, 'integer');
        //     $targetUser = seekPOSTorGET('t');
        //     // error_log("$user authorised dropping LB entry by $targetUser from LB $lbID");
        //     $response['Success'] = RemoveLeaderboardEntry($targetUser, $lbID);
        //     break;

        case "richpresencepatch":
            $response['Success'] = getRichPresencePatch($gameID, $richPresenceData);
            $response['RichPresencePatch'] = $richPresenceData;
            break;

        case "submitcodenote":
            $note = seekPOSTorGET('n');
            $address = seekPOSTorGET('m', 0, 'integer');
            $response['Success'] = submitCodeNote2($user, $gameID, $address, $note);
            $response['GameID'] = $gameID;     // Repeat this back to the caller?
            $response['Address'] = $address;    // Repeat this back to the caller?
            $response['Note'] = $note;      // Repeat this back to the caller?
            break;

        case "submitgametitle":
            $md5 = seekPOSTorGET('m');
            $gameTitle = seekPOSTorGET('i');
            $consoleID = seekPOSTorGET('c');
            $response['Response'] = submitNewGameTitleJSON($user, $md5, $gameTitle, $consoleID);
            $response['Success'] = $response['Response']['Success']; // Passthru
            if (isset($response['Response']['Error'])) {
                $response['Error'] = $response['Response']['Error'];
            }
            break;

        case "submitlbentry":
            $lbID = seekPOSTorGET('i', 0, 'integer');
            $score = seekPOSTorGET('s', 0, 'integer');
            $validation = seekPOSTorGET('v'); // Ignore for now?
            $response['Response'] = SubmitLeaderboardEntryJSON($user, $lbID, $score, $validation);
            $response['Success'] = $response['Response']['Success']; // Passthru
            if ($response['Success'] == false) {
                $response['Error'] = $response['Response']['Error'];
            }
            break;

        case "submitticket":
            $idCSV = seekPOSTorGET('i');
            $problemType = seekPOSTorGET('p');
            $comment = seekPOSTorGET('n');
            $md5 = seekPOSTorGET('m');
            $response['Response'] = submitNewTicketsJSON($user, $idCSV, $problemType, $comment, $md5);
            $response['Success'] = $response['Response']['Success']; // Passthru
            if (isset($response['Response']['Error'])) {
                $response['Error'] = $response['Response']['Error'];
            }
            break;

        case "unlocks":
            $hardcoreMode = seekPOSTorGET('h', 0, 'integer');
            $response['UserUnlocks'] = GetUserUnlocksData($user, $gameID, $hardcoreMode);
            $response['GameID'] = $gameID;     // Repeat this back to the caller?
            $response['HardcoreMode'] = $hardcoreMode;  // Repeat this back to the caller?
            settype($response['HardcoreMode'], 'boolean');
            break;

        case "uploadachievement":
            $newTitle = seekPOSTorGET('n');
            $newDesc = seekPOSTorGET('d');
            $newPoints = seekPOSTorGET('z', 0, 'integer');
            $newMemString = seekPOSTorGET('m');
            $newFlags = seekPOSTorGET('f', 0, 'integer');
            $newBadge = seekPOSTorGET('b');
            $errorOut = "";
            $response['Success'] = UploadNewAchievement($user, $gameID, $newTitle, $newDesc, ' ', ' ', ' ', $newPoints, $newMemString, $newFlags, $achievementID, $newBadge, $errorOut);
            $response['AchievementID'] = $achievementID;
            $response['Error'] = $errorOut;
            break;

        default:
            DoRequestError("Unknown Request: '" . $requestType . "'");
            break;
    }
} else {
    DoRequestError("Credentials invalid ($permissions)");
}

settype($response['Success'], 'boolean');
echo json_encode($response);
