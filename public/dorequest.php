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
use App\Connect\Actions\SubmitCodeNotesAction;
use App\Connect\Actions\SubmitGameTitleAction;
use App\Connect\Actions\SubmitLeaderboardAction;
use App\Connect\Actions\SubmitLeaderboardEntryAction;
use App\Connect\Actions\SubmitRichPresenceAction;
use App\Connect\Actions\UnknownRequestAction;
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
    'submitcodenotes' => new SubmitCodeNotesAction(),
    'submitgametitle' => new SubmitGameTitleAction(),
    'submitlbentry' => new SubmitLeaderboardEntryAction(),
    'submitrichpresence' => new SubmitRichPresenceAction(),
    'systemgames' => new GetSystemGamesAction(),
    'unlocks' => new GetPlayerGameUnlocksAction(),
    'uploadachievement' => new SubmitAchievementAction(),
    'uploadleaderboard' => new SubmitLeaderboardAction(),
    default => new UnknownRequestAction(),
};

return $handler->handleRequest(request());
