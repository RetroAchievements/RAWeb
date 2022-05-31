<?php

use RA\AchievementType;
use RA\FilenameIterator;
use RA\Permissions;

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

// $bounceReferrer = requestInput( 'b' ); // TBD: Remove!

$errorCode = "OK";

$validLogin = false;
$permissions = null;
if (!empty($token)) {
    $validLogin = authenticateFromAppToken($user, $token, $permissions);
}

function DoRequestError($errorMsg): void
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
$credentialsOK = match ($requestType) {
    /**
     * Registration required and user=local
     */
    "achievementwondata",
    "awardachievement",
    "getfriendlist",
    "patch",
    "postactivity",
    "richpresencepatch",
    "submitcodenote",
    "submitgametitle",
    "submitlbentry",
    "unlocks",
    "uploadachievement",
    "uploadleaderboard" => $validLogin && ($permissions >= Permissions::Registered),
    /**
     * Anything else is public. Includes login
     */
    default => true,
};

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
        $response = authenticateFromPasswordOrAppToken($user, $rawPass, $token);
        break;

    /**
     * Global, no permissions required
     */
    case "allprogress":
        $consoleID = requestInput('c', null, 'integer');
        $response['Response'] = GetAllUserProgress($user, $consoleID);
        break;

    // TODO: still used?
    case "badgeiter":
        $response['FirstBadge'] = 80;
        $response['NextBadge'] = (int) FilenameIterator::getBadgeIterator();
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
        break;
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
        $response['LatestVersionUrl'] = null;
        if ($emulator['latest_version_url'] ?? null) {
            $response['LatestVersionUrl'] = $baseDownloadUrl . $emulator['latest_version_url'];
        }
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
    case "ping":
        $activityMessage = requestInputPost('m', null);
        $response['Success'] = userActivityPing($user);

        if (isset($activityMessage)) {
            UpdateUserRichPresence($user, $gameID, $activityMessage);
        }
        break;

    /*
     * User-based (require credentials)
     */

    case "achievementwondata":
        $friendsOnly = (int) requestInput('f', 0);
        $response['Offset'] = $offset;
        $response['Count'] = $count;
        $response['FriendsOnly'] = $friendsOnly;
        $response['AchievementID'] = $achievementID;
        $response['Response'] = getRecentUnlocksPlayersData($achievementID, $offset, $count, $user, $friendsOnly);
        break;

    case "awardachievement":
        $achIDToAward = requestInput('a', 0, 'integer');
        $hardcore = requestInput('h', 0, 'integer');
        /**
         * Prefer later values, i.e. allow AddEarnedAchievementJSON to overwrite the 'success' key
         */
        $response = array_merge($response, unlockAchievement($user, $achIDToAward, $hardcore));
        $response['Score'] = getPlayerPoints($user);
        $response['AchievementID'] = $achIDToAward;
        break;

    case "getfriendlist":
        $response['Friends'] = GetFriendList($user);
        break;

    case "lbinfo":
        $lbID = requestInput('i', 0, 'integer');
        $nearby = true; // Nearby entry behavior has no effect if $user is null
        $friendsOnly = 0; // TBD
        $response['LeaderboardData'] = GetLeaderboardData($lbID, $user, $count, $offset, $friendsOnly, $nearby);
        break;

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
        $response['Response'] = submitNewGameTitleJSON($user, $md5, $gameID, $gameTitle, $consoleID, $description);
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
        $errorOut = "";
        $response['Success'] = UploadNewAchievement(
            author: $user,
            gameID: $gameID,
            title: requestInput('n'),
            desc: requestInput('d'),
            progress: ' ',
            progressMax: ' ',
            progressFmt: ' ',
            points: requestInput('z', 0, 'integer'),
            mem: requestInput('m'),
            type: requestInput('f', AchievementType::UNOFFICIAL, 'integer'),
            idInOut: $achievementID,
            badge: requestInput('b'),
            errorOut: $errorOut
        );
        $response['AchievementID'] = $achievementID;
        $response['Error'] = $errorOut;
        break;

    case "uploadleaderboard":
        $leaderboardID = requestInput('i', 0, 'integer');
        $newTitle = requestInput('n');
        $newDesc = requestInput('d');
        $newStartMemString = requestInput('s');
        $newSubmitMemString = requestInput('b');
        $newCancelMemString = requestInput('c');
        $newValueMemString = requestInput('l');
        $newLowerIsBetter = requestInput('w', 0, 'integer');
        $newFormat = requestInput('f');
        $newMemString = "STA:$newStartMemString::CAN:$newCancelMemString::SUB:$newSubmitMemString::VAL:$newValueMemString";

        $errorOut = "";
        $response['Success'] = UploadNewLeaderboard($user, $gameID, $newTitle, $newDesc, $newFormat, $newLowerIsBetter, $newMemString, $leaderboardID, $errorOut);
        $response['LeaderboardID'] = $leaderboardID;
        $response['Error'] = $errorOut;
        break;

    default:
        DoRequestError("Unknown Request: '" . $requestType . "'");
        break;
}

settype($response['Success'], 'boolean');
echo json_encode($response, JSON_THROW_ON_ERROR);
