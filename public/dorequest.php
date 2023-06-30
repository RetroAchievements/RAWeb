<?php

use App\Platform\Enums\AchievementType;
use App\Site\Enums\Permissions;
use App\Support\Media\FilenameIterator;
use Illuminate\Http\JsonResponse;

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
$requestType = request()->input('r');
$user = request()->input('u');
$token = request()->input('t');
$achievementID = (int) request()->input('a', 0);  // Keep in mind, this will overwrite anything given outside these params!!
$gameID = (int) request()->input('g', 0);
$offset = (int) request()->input('o', 0);
$count = (int) request()->input('c', 10);

$validLogin = false;
$permissions = null;
if (!empty($token)) {
    $validLogin = authenticateFromAppToken($user, $token, $permissions);
}

function DoRequestError(string $error): JsonResponse
{
    return response()->json([
        'Success' => false,
        'Error' => $error,
    ]);
}

/**
 * RAIntegration implementation
 * https://github.com/RetroAchievements/RAIntegration/blob/master/src/api/impl/ConnectedServer.cpp
 */

/**
 * Early exit if we need a valid login
 */
$credentialsOK = match ($requestType) {
    /*
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
    /*
     * Anything else is public. Includes login
     */
    default => true,
};

if (!$credentialsOK) {
    return DoRequestError("Credentials invalid ($permissions)");
}

switch ($requestType) {
    /*
     * Login
     */
    case "login": // From App!
        $user = request()->input('u');
        $rawPass = request()->input('p');
        $response = authenticateFromPasswordOrAppToken($user, $rawPass, $token);
        break;

    /*
     * Global, no permissions required
     */
    case "allprogress":
        $consoleID = (int) request()->input('c');
        $response['Response'] = GetAllUserProgress($user, $consoleID);
        break;

    case "badgeiter":
        // Used by RALibretro achievement editor
        $response['FirstBadge'] = 80;
        $response['NextBadge'] = (int) FilenameIterator::getBadgeIterator();
        break;

    // TODO: Deprecate - not used anymore
    case "codenotes":
        if (!getCodeNotes($gameID, $codeNotesOut)) {
            return DoRequestError("FAILED!");
        }
        echo "OK:$gameID:";
        foreach ($codeNotesOut as $codeNote) {
            if (mb_strlen($codeNote['Note']) > 2) {
                $noteAdj = str_replace("\n", "\r\n", $codeNote['Note']);
                echo $codeNote['User'] . ':' . $codeNote['Address'] . ':' . $noteAdj . "#";
            }
        }
        break;

    case "codenotes2":
        $response['CodeNotes'] = getCodeNotesData($gameID);
        $response['GameID'] = $gameID;
        break;
    case "gameid":
        $md5 = request()->input('m') ?? '';
        $response['GameID'] = getGameIDFromMD5($md5);
        break;

    case "gameslist":
        $consoleID = (int) request()->input('c', 0);
        $response['Response'] = getGamesListDataNamesOnly($consoleID);
        break;

    case "officialgameslist":
        $consoleID = (int) request()->input('c', 0);
        $response['Response'] = getGamesListDataNamesOnly($consoleID, true);
        break;

    case "hashlibrary":
        $consoleID = (int) request()->input('c', 0);
        $response['MD5List'] = getMD5List($consoleID);
        break;

    case "latestclient":
        $emulatorId = (int) request()->input('e');
        $consoleId = (int) request()->input('c');

        if (empty($emulatorId) && !empty($consoleId)) {
            return DoRequestError("Lookup by Console ID has been deprecated");
        }

        $emulator = getEmulatorReleaseByIntegrationId($emulatorId);

        if ($emulator === null) {
            return DoRequestError("Unknown client");
        }
        $baseDownloadUrl = str_replace('https', 'http', config('app.url')) . '/';
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
            return DoRequestError("Unknown client");
        }
        $baseDownloadUrl = str_replace('https', 'http', config('app.url')) . '/';
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
        $activityMessage = request()->post('m');
        $response['Success'] = userActivityPing($user);

        if (isset($activityMessage)) {
            UpdateUserRichPresence($user, $gameID, $activityMessage);
        }
        break;

    /*
     * User-based (require credentials)
     */

    case "achievementwondata":
        $friendsOnly = (bool) request()->input('f', 0);
        $response['Offset'] = $offset;
        $response['Count'] = $count;
        $response['FriendsOnly'] = $friendsOnly;
        $response['AchievementID'] = $achievementID;
        $response['Response'] = getRecentUnlocksPlayersData($achievementID, $offset, $count, $user, $friendsOnly);
        break;

    case "awardachievement":
        $achIDToAward = (int) request()->input('a', 0);
        $hardcore = (bool) request()->input('h', 0);
        /**
         * Prefer later values, i.e. allow AddEarnedAchievementJSON to overwrite the 'success' key
         */
        $response = array_merge($response, unlockAchievement($user, $achIDToAward, $hardcore));
        $response['Score'] = 0;
        $response['SoftcoreScore'] = 0;
        if (getPlayerPoints($user, $userPoints)) {
            $response['Score'] = $userPoints['RAPoints'];
            $response['SoftcoreScore'] = $userPoints['RASoftcorePoints'];
        }
        $response['AchievementID'] = $achIDToAward;
        break;

    case "getfriendlist":
        $response['Friends'] = GetFriendList($user);
        break;

    case "lbinfo":
        $lbID = (int) request()->input('i', 0);
        // Note: Nearby entry behavior has no effect if $user is null
        // TBD: friendsOnly
        $response['LeaderboardData'] = GetLeaderboardData($lbID, $user, $count, $offset, nearby: true);
        break;

    case "patch":
        $flags = (int) request()->input('f', 0);
        $response['PatchData'] = GetPatchData($gameID, $flags);
        if (array_key_exists('Success', $response['PatchData'])) {
            $response['Success'] = $response['PatchData']['Success']; // Passthru
            unset($response['PatchData']['Success']);
        }
        break;

    case "postactivity":
        $activityType = (int) request()->input('a');
        $activityMessage = (int) request()->input('m');
        $response['Success'] = postActivity($user, $activityType, $activityMessage);
        break;

    case "richpresencepatch":
        $response['Success'] = getRichPresencePatch($gameID, $richPresenceData);
        $response['RichPresencePatch'] = $richPresenceData;
        break;

    case "submitcodenote":
        $note = request()->input('n') ?? '';
        $address = (int) request()->input('m', 0);
        $response['Success'] = submitCodeNote2($user, $gameID, $address, $note);
        $response['GameID'] = $gameID;     // Repeat this back to the caller?
        $response['Address'] = $address;    // Repeat this back to the caller?
        $response['Note'] = $note;      // Repeat this back to the caller?
        break;

    case "submitgametitle":
        $md5 = request()->input('m');
        $gameID = request()->input('g');
        $gameTitle = request()->input('i');
        $description = request()->input('d');
        $consoleID = request()->input('c');
        $response['Response'] = submitNewGameTitleJSON($user, $md5, $gameID, $gameTitle, $consoleID, $description);
        $response['Success'] = $response['Response']['Success']; // Passthru
        if (isset($response['Response']['Error'])) {
            $response['Error'] = $response['Response']['Error'];
        }
        break;

    case "submitlbentry":
        $lbID = (int) request()->input('i', 0);
        $score = (int) request()->input('s', 0);
        $validation = request()->input('v'); // Ignore for now?
        $response['Response'] = SubmitLeaderboardEntry($user, $lbID, $score, $validation);
        $response['Success'] = $response['Response']['Success']; // Passthru
        if (!$response['Success']) {
            $response['Error'] = $response['Response']['Error'];
        }
        break;

    case "submitticket":
        $idCSV = request()->input('i');
        $problemType = request()->input('p');
        $comment = request()->input('n');
        $md5 = request()->input('m');
        $response['Response'] = submitNewTicketsJSON($user, $idCSV, $problemType, $comment, $md5);
        $response['Success'] = $response['Response']['Success']; // Passthru
        if (isset($response['Response']['Error'])) {
            $response['Error'] = $response['Response']['Error'];
        }
        break;

    case "unlocks":
        $hardcoreMode = (bool) request()->input('h', 0);
        $response['UserUnlocks'] = GetUserUnlocksData($user, $gameID, $hardcoreMode);
        $response['GameID'] = $gameID;     // Repeat this back to the caller?
        $response['HardcoreMode'] = $hardcoreMode;
        break;

    case "uploadachievement":
        $errorOut = "";
        $response['Success'] = UploadNewAchievement(
            author: $user,
            gameID: $gameID,
            title: request()->input('n'),
            desc: request()->input('d'),
            progress: ' ',
            progressMax: ' ',
            progressFmt: ' ',
            points: (int) request()->input('z', 0),
            mem: request()->input('m'),
            type: (int) request()->input('f', AchievementType::Unofficial),
            idInOut: $achievementID,
            badge: request()->input('b'),
            errorOut: $errorOut
        );
        $response['AchievementID'] = $achievementID;
        $response['Error'] = $errorOut;
        break;

    case "uploadleaderboard":
        $leaderboardID = (int) request()->input('i', 0);
        $newTitle = request()->input('n');
        $newDesc = request()->input('d') ?? '';
        $newStartMemString = request()->input('s');
        $newSubmitMemString = request()->input('b');
        $newCancelMemString = request()->input('c');
        $newValueMemString = request()->input('l');
        $newLowerIsBetter = (bool) request()->input('w', 0);
        $newFormat = request()->input('f');
        $newMemString = "STA:$newStartMemString::CAN:$newCancelMemString::SUB:$newSubmitMemString::VAL:$newValueMemString";

        $errorOut = "";
        $response['Success'] = UploadNewLeaderboard($user, $gameID, $newTitle, $newDesc, $newFormat, $newLowerIsBetter, $newMemString, $leaderboardID, $errorOut);
        $response['LeaderboardID'] = $leaderboardID;
        $response['Error'] = $errorOut;
        break;

    default:
        return DoRequestError("Unknown Request: '" . $requestType . "'");
}

$response['Success'] = (bool) $response['Success'];

return response()->json($response);
