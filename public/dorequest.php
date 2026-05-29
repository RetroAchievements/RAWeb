<?php

use App\Connect\Actions\AwardAchievementAction;
use App\Connect\Actions\AwardAchievementsAction;
use App\Connect\Actions\GetAchievementSetsAction;
use App\Connect\Actions\GetAchievementUnlocksAction;
use App\Connect\Actions\GetBadgeIdRangeAction;
use App\Connect\Actions\GetCodeNotesAction;
use App\Connect\Actions\GetFriendListAction;
use App\Connect\Actions\GetGameIdFromHashAction;
use App\Connect\Actions\GetGameInfosAction;
use App\Connect\Actions\GetGamesListAction;
use App\Connect\Actions\GetHashLibraryAction;
use App\Connect\Actions\GetLatestClientVersionAction;
use App\Connect\Actions\GetLatestIntegrationVersionAction;
use App\Connect\Actions\GetLeaderboardEntriesAction;
use App\Connect\Actions\GetOfficialGamesListAction;
use App\Connect\Actions\GetPlayerGameUnlocksAction;
use App\Connect\Actions\GetSystemGamesAction;
use App\Connect\Actions\GetUserProgressForConsoleAction;
use App\Connect\Actions\LegacyGetPatchAction;
use App\Connect\Actions\LegacyLoginAction;
use App\Connect\Actions\LoginAction;
use App\Connect\Actions\PingAction;
use App\Connect\Actions\PostActivityAction;
use App\Connect\Actions\StartSessionAction;
use App\Connect\Actions\SubmitAchievementAction;
use App\Connect\Actions\SubmitCodeNoteAction;
use App\Connect\Actions\SubmitGameTitleAction;
use App\Connect\Actions\SubmitLeaderboardAction;
use App\Connect\Actions\SubmitLeaderboardEntryAction;
use App\Connect\Actions\SubmitRichPresenceAction;
use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Sentry\State\Scope;

use function Sentry\configureScope;

$requestType = request()->input('r');

// Tag the request type so Sentry can group dorequest.php calls by routine.
configureScope(function (Scope $scope) use ($requestType) {
    $scope->setTag('dorequest.type', $requestType ?? 'unknown');
});

$handler = match ($requestType) {
    'achievementwondata' => new GetAchievementUnlocksAction(),
    'achievementsets' => new GetAchievementSetsAction(),
    'allprogress' => new GetUserProgressForConsoleAction(),
    'awardachievement' => new AwardAchievementAction(),
    'awardachievements' => new AwardAchievementsAction(),
    'badgeiter' => new GetBadgeIdRangeAction(),
    'codenotes2' => new GetCodeNotesAction(),
    'gameid' => new GetGameIdFromHashAction(),
    'gameinfolist' => new GetGameInfosAction(),
    'gameslist' => new GetGamesListAction(),
    'getfriendlist' => new GetFriendListAction(),
    'hashlibrary' => new GetHashLibraryAction(),
    'latestclient' => new GetLatestClientVersionAction(),
    'latestintegration' => new GetLatestIntegrationVersionAction(),
    'lbinfo' => new GetLeaderboardEntriesAction(),
    'login' => new LegacyLoginAction(),
    'login2' => new LoginAction(),
    'officialgameslist' => new GetOfficialGamesListAction(),
    'patch' => new LegacyGetPatchAction(),
    'ping' => new PingAction(),
    'postactivity' => new PostActivityAction(),
    'startsession' => new StartSessionAction(),
    'submitcodenote' => new SubmitCodeNoteAction(),
    'submitgametitle' => new SubmitGameTitleAction(),
    'submitlbentry' => new SubmitLeaderboardEntryAction(),
    'submitrichpresence' => new SubmitRichPresenceAction(),
    'systemgames' => new GetSystemGamesAction(),
    'unlocks' => new GetPlayerGameUnlocksAction(),
    'uploadachievement' => new SubmitAchievementAction(),
    'uploadleaderboard' => new SubmitLeaderboardAction(),
    default => null,
};
if ($handler) {
    return $handler->handleRequest(request());
}

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
$username = request()->input('u');
$token = request()->input('t');
$delegateTo = request()->input('k');
$achievementID = (int) request()->input('a', 0);  // Keep in mind, this will overwrite anything given outside these params!!
$gameID = (int) request()->input('g', 0);
$offset = (int) request()->input('o', 0);
$count = (int) request()->input('c', 10);

$validLogin = false;
$permissions = null;
if (!empty($token)) {
    $validLogin = authenticateFromAppToken($username, $token, $permissions);
}

/** @var ?User $foundDelegateToUser */
$foundDelegateToUser = null;

/** @var ?User $user */
$user = request()->user('connect-token');

if (!function_exists('DoRequestError')) {
    function DoRequestError(string $error, ?int $status = 200, ?string $code = null): JsonResponse
    {
        $response = [
            'Success' => false,
            'Error' => $error,
        ];

        if ($code !== null) {
            $response['Code'] = $code;
        }

        if ($status !== 200) {
            $response['Status'] = $status;

            if ($status === 401) {
                return response()->json($response, $status)->header('WWW-Authenticate', 'Bearer');
            }

            return response()->json($response, $status);
        }

        return response()->json($response);
    }
}

/**
 * RAIntegration implementation
 * https://github.com/RetroAchievements/RAIntegration/blob/master/src/api/impl/ConnectedServer.cpp
 */

/**
 * Early exit if we need a valid login
 */
$credentialsOK = $validLogin && ($permissions >= Permissions::Registered);
if (!$credentialsOK) {
    if (!$validLogin) {
        return DoRequestError("Invalid user/token combination.", 401, 'invalid_credentials');
    }

    if ($permissions < Permissions::Unregistered) { // Banned/Spam accounts
        return DoRequestError("Access denied.", 403, 'access_denied');
    }
    if ($permissions === Permissions::Unregistered) {
        return DoRequestError("Access denied. Please verify your email address.", 403, 'access_denied');
    }

    return DoRequestError("You do not have permission to do that.", 403, 'access_denied');
}

switch ($requestType) {
    case "richpresencepatch":
        $response['Success'] = getRichPresencePatch($gameID, $richPresenceData);
        $response['RichPresencePatch'] = $richPresenceData;
        break;

    default:
        return DoRequestError("Unknown Request: '" . $requestType . "'");
}

$response['Success'] = (bool) $response['Success'];

// Convert the response to a JSON string in order to calculate the exact Content-Length.
// Cloudflare is manipulating the headers of dorequest.php responses, and some clients
// are unable to gracefully handle this (ie: RetroArch 1.20.0 and below). By adding
// explicit Content-Type, Content-Length, and Cache-Control headers, we inform Cloudflare
// that these responses are immutable and should be passed straight through.
$jsonContent = json_encode($response);
$contentLength = (string) strlen($jsonContent);

return response($jsonContent)
    ->header('Content-Type', 'application/json')
    ->header('Content-Length', $contentLength)
    ->header('Cache-Control', 'no-transform, private, must-revalidate');
