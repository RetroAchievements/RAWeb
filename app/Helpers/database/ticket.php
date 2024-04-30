<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\TicketFilters;
use App\Community\Enums\TicketState;
use App\Community\ViewModels\Ticket as TicketViewModel;
use App\Models\Achievement;
use App\Models\NotificationPreferences;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function submitNewTicketsJSON(
    string $userSubmitter,
    string $idsCSV,
    int $reportType,
    string $noteIn,
    string $RAHash
): array {
    sanitize_sql_inputs($userSubmitter, $reportType, $noteIn, $RAHash);

    $returnMsg = [];

    /** @var User $user */
    $user = User::firstWhere('User', $userSubmitter);

    if (!$user->exists() || !$user->can('create', Ticket::class)) {
        $returnMsg['Success'] = false;

        return $returnMsg;
    }

    $note = $noteIn;
    $note .= "\nRetroAchievements Hash: $RAHash";

    $achievementIDs = explode(',', $idsCSV);

    $errorsEncountered = false;

    $idsFound = 0;
    $idsAdded = 0;

    foreach ($achievementIDs as $achID) {
        $achievementID = (int) $achID;
        if ($achievementID == 0) {
            continue;
        }

        $idsFound++;

        $ticketID = getExistingTicketID($user, $achievementID);
        if ($ticketID !== 0) {
            $returnMsg['Error'] = "You already have a ticket for achievement $achID";
            $errorsEncountered = true;
            continue;
        }

        $ticketID = _createTicket($user, $achievementID, $reportType, null, $note);
        if ($ticketID === 0) {
            $errorsEncountered = true;
        } else {
            $idsAdded++;
        }
    }

    $returnMsg['Detected'] = $idsFound;
    $returnMsg['Added'] = $idsAdded;
    $returnMsg['Success'] = ($errorsEncountered == false);

    return $returnMsg;
}

function submitNewTicket(User $user, int $achID, int $reportType, int $hardcore, string $note): int
{
    if (!$user->can('create', Ticket::class)) {
        return 0;
    }

    $ticketID = getExistingTicketID($user, $achID);
    if ($ticketID !== 0) {
        return $ticketID;
    }

    return _createTicket($user, $achID, $reportType, $hardcore, $note);
}

function _createTicket(User $user, int $achID, int $reportType, ?int $hardcore, string $note): int
{
    $achievement = Achievement::find($achID);
    if (!$achievement) {
        return 0;
    }

    $noteSanitized = $note;
    sanitize_sql_inputs($noteSanitized);

    $hardcoreValue = $hardcore === null ? 'NULL' : (string) $hardcore;

    $userId = $user->id;
    $username = $user->User;

    $query = "INSERT INTO Ticket (AchievementID, reporter_id, ReportType, Hardcore, ReportNotes, ReportedAt, ResolvedAt, resolver_id )
              VALUES($achID, $userId, $reportType, $hardcoreValue, \"$noteSanitized\", NOW(), NULL, NULL )";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);
    if (!$dbResult) {
        log_sql_fail();

        return 0;
    }

    $ticketID = mysqli_insert_id($db);

    $achTitle = $achievement->title;
    $gameID = $achievement->game->id;
    $gameTitle = $achievement->game->title;

    expireUserTicketCounts($achievement->developer->User);

    $problemTypeStr = ($reportType === 1) ? "Triggers at wrong time" : "Doesn't trigger";

    $emailHeader = "Bug Report ($gameTitle)";
    $ticketUrl = config('app.url') . "/ticketmanager.php?i=$ticketID";
    $bugReportDetails = "
Achievement: $achTitle
Game: $gameTitle
Problem: $problemTypeStr
Comment: $note

This ticket will be raised and will be available for all developers to inspect and manage at the following URL:
<a href='$ticketUrl'>$ticketUrl</a>

Thanks!";

    if ($achievement->developer && BitSet($achievement->developer->websitePrefs, NotificationPreferences::EmailOn_PrivateMessage)) {
        $emailBody = "Hi, {$achievement->developer->User}!

$username would like to report a bug with an achievement you've created:
$bugReportDetails";
        sendRAEmail($achievement->developer->EmailAddress, $emailHeader, $emailBody);
    }

    // notify subscribers other than the achievement's author
    $subscribers = getSubscribersOf(SubscriptionSubjectType::GameTickets, $gameID, 1 << NotificationPreferences::EmailOn_PrivateMessage);
    foreach ($subscribers as $sub) {
        if ($sub['User'] !== $achievement->developer->User && $sub['User'] != $username) {
            $emailBody = "Hi, " . $sub['User'] . "!

$username would like to report a bug with an achievement you're subscribed to:
$bugReportDetails";
            sendRAEmail($sub['EmailAddress'], $emailHeader, $emailBody);
        }
    }

    return $ticketID;
}

function getExistingTicketID(User $user, int $achievementID): int
{
    $userID = $user->ID;
    $query = "SELECT ID FROM Ticket WHERE reporter_id=$userID AND AchievementID=$achievementID"
           . " AND ReportState NOT IN (" . TicketState::Closed . "," . TicketState::Resolved . ")";
    $dbResult = s_mysql_query($query);
    if ($dbResult) {
        $existingTicket = mysqli_fetch_assoc($dbResult);
        if ($existingTicket) {
            return (int) $existingTicket['ID'];
        }
    }

    return 0;
}

function getAllTickets(
    int $offset = 0,
    int $limit = 50,
    ?string $assignedToUser = null,
    ?string $reportedByUser = null,
    ?string $resolvedByUser = null,
    ?int $givenGameID = null,
    ?int $givenAchievementID = null,
    int $ticketFilters = TicketFilters::Default,
    bool $getUnofficial = false
): array {
    $retVal = [];
    $bindings = [];

    $innerCond = "TRUE";
    if (!empty($assignedToUser) && isValidUsername($assignedToUser)) {
        $innerCond .= " AND ach.Author = :assignedToUsername";
        $bindings['assignedToUsername'] = $assignedToUser;
    }
    if (!empty($reportedByUser) && isValidUsername($reportedByUser)) {
        $innerCond .= " AND ua.User = :reportedByUsername";
        $bindings['reportedByUsername'] = $reportedByUser;
    }
    if (!empty($resolvedByUser) && isValidUsername($resolvedByUser)) {
        $innerCond .= " AND ua2.User = :resolvedByUsername";
        $bindings['resolvedByUsername'] = $resolvedByUser;
    }
    if ($givenGameID != 0) {
        $innerCond .= " AND gd.ID = $givenGameID";
    }
    if ($givenAchievementID != 0) {
        $innerCond .= " AND tick.AchievementID = $givenAchievementID";
    }

    // State condition
    $stateCond = getStateCondition($ticketFilters);
    if ($stateCond === null) {
        return $retVal;
    }

    // Report Type condition
    $reportTypeCond = getReportTypeCondition($ticketFilters);
    if ($reportTypeCond === null) {
        return $retVal;
    }

    // Hash condition
    $hashCond = getHashCondition($ticketFilters);
    if ($hashCond === null) {
        return $retVal;
    }

    // Mode condition
    $modeCond = getModeCondition($ticketFilters);
    if ($modeCond === null) {
        return $retVal;
    }

    // Emulator condition
    $emulatorCond = getEmulatorCondition($ticketFilters);

    // Developer Active condition
    $devJoin = "";
    $devActiveCond = getDevActiveCondition($ticketFilters);
    if ($devActiveCond === null) {
        return $retVal;
    }
    if ($devActiveCond != "") {
        $devJoin = "LEFT JOIN UserAccounts AS ua3 ON ua3.User = ach.Author";
    }

    // Karma condition - warning: excludes unresolved tickets
    $notAuthorCond = getResolvedByNonAuthorCondition($ticketFilters);
    $notReporterCond = getResolvedByNonReporterCondition($ticketFilters);

    // Progression filter
    $progressionCond = getProgressionCondition($ticketFilters);

    // official/unofficial filter (ignore when a specific achievement is requested)
    $achFlagCond = '';
    if (!$givenAchievementID) {
        $achFlagCond = $getUnofficial ? " AND ach.Flags = '5'" : "AND ach.Flags = '3'";
    }

    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.type AS AchievementType, ach.Points, ach.BadgeName,
                ach.Author AS AchievementAuthor, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
                tick.ReportedAt, tick.ReportType, tick.Hardcore, tick.ReportNotes, ua.User AS ReportedBy, tick.ResolvedAt, ua2.User AS ResolvedBy, tick.ReportState
              FROM Ticket AS tick
              LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = tick.reporter_id
              LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.resolver_id
              $devJoin
              WHERE $innerCond $achFlagCond $stateCond $modeCond $reportTypeCond $hashCond $emulatorCond $devActiveCond $notAuthorCond $notReporterCond $progressionCond
              ORDER BY tick.ID DESC
              LIMIT $offset, $limit";

    return legacyDbFetchAll($query, $bindings)->toArray();
}

function getTicket(int $ticketID): ?array
{
    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.type AS AchievementType, ach.Points, ach.BadgeName,
                ach.Author AS AchievementAuthor, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
                tick.ReportedAt, tick.ReportType, tick.ReportState, tick.Hardcore, tick.ReportNotes, ua.User AS ReportedBy, tick.ResolvedAt, ua2.User AS ResolvedBy
              FROM Ticket AS tick
              LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = tick.reporter_id
              LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.resolver_id
              WHERE tick.ID = $ticketID
              ";

    return legacyDbFetch($query);
}

function updateTicket(string $user, int $ticketID, int $ticketVal, ?string $reason = null): bool
{
    $userID = getUserIDFromUser($user);

    // get the ticket data before updating so we know what the previous state was
    $ticketData = getTicket($ticketID);

    $resolvedFields = "";
    if ($ticketVal == TicketState::Resolved || $ticketVal == TicketState::Closed) {
        $resolvedFields = ", ResolvedAt=NOW(), resolver_id=$userID ";
    } elseif ($ticketData['ReportState'] == TicketState::Resolved || $ticketData['ReportState'] == TicketState::Closed) {
        $resolvedFields = ", ResolvedAt=NULL, resolver_id=NULL ";
    }

    $query = "UPDATE Ticket
              SET ReportState=$ticketVal $resolvedFields
              WHERE ID=$ticketID";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    $userReporter = $ticketData['ReportedBy'];
    $achID = $ticketData['AchievementID'];
    $achTitle = $ticketData['AchievementTitle'];
    $gameTitle = $ticketData['GameTitle'];
    $consoleName = $ticketData['ConsoleName'];

    $status = TicketState::toString($ticketVal);
    $comment = null;

    switch ($ticketVal) {
        case TicketState::Closed:
            if ($reason == TicketState::REASON_DEMOTED) {
                updateAchievementFlag($achID, AchievementFlag::Unofficial);
                addArticleComment("Server", ArticleType::Achievement, $achID, "$user demoted this achievement to Unofficial.", $user);
            }
            $comment = "Ticket closed by $user. Reason: \"$reason\".";
            break;

        case TicketState::Open:
            if ($ticketData['ReportState'] == TicketState::Request) {
                $comment = "Ticket reassigned to author by $user.";
            } else {
                $comment = "Ticket reopened by $user.";
            }
            break;

        case TicketState::Resolved:
            $comment = "Ticket resolved as fixed by $user.";
            break;

        case TicketState::Request:
            $comment = "Ticket reassigned to reporter by $user.";
            break;
    }

    addArticleComment("Server", ArticleType::AchievementTicket, $ticketID, $comment, $user);

    expireUserTicketCounts($ticketData['AchievementAuthor']);

    $reporterData = [];
    if (!getAccountDetails($userReporter, $reporterData)) {
        return true;
    }

    expireUserTicketCounts($userReporter);

    $email = $reporterData['EmailAddress'];

    $emailTitle = "Ticket status changed";

    $msg = "Hello $userReporter!<br>" .
        "<br>" .
        "$achTitle - $gameTitle ($consoleName)<br>" .
        "<br>" .
        "The ticket you opened for the above achievement had its status changed to \"$status\" by \"$user\".<br>" .
        "<br>Comment: $comment" .
        "<br>" .
        "Click <a href='" . config('app.url') . "/ticketmanager.php?i=$ticketID'>here</a> to view the ticket" .
        "<br>" .
        "Thank-you again for your help in improving the quality of the achievements on RA!<br>" .
        "<br>" .
        "-- Your friends at RetroAchievements.org<br>";

    return mail_utf8($email, $emailTitle, $msg);
}

function countRequestTicketsByUser(?User $user = null): int
{
    if ($user === null) {
        return 0;
    }

    $cacheKey = CacheKey::buildUserRequestTicketsCacheKey($user->User);

    return Cache::remember($cacheKey, Carbon::now()->addHours(20), function () use ($user) {
        return Ticket::where('ReportState', TicketState::Request)
            ->where('reporter_id', $user->ID)
            ->count();
    });
}

function countOpenTicketsByDev(User $dev): array
{
    $cacheKey = CacheKey::buildUserOpenTicketsCacheKey($dev->User);

    return Cache::remember($cacheKey, Carbon::now()->addHours(20), function () use ($dev) {
        $retVal = [
            TicketState::Open => 0,
            TicketState::Request => 0,
        ];

        $tickets = Ticket::with('achievement')
            ->whereHas('achievement', function ($query) use ($dev) {
                $query
                    ->where('user_id', $dev->id)
                    ->whereIn('Flags', [AchievementFlag::OfficialCore, AchievementFlag::Unofficial]);
            })
            ->whereIn('ReportState', [TicketState::Open, TicketState::Request])
            ->select('AchievementID', 'ReportState', DB::raw('count(*) as Count'))
            ->groupBy('ReportState')
            ->get();

        foreach ($tickets as $ticket) {
            $retVal[$ticket->ReportState] = (int) $ticket->Count;
        }

        return $retVal;
    });
}

function expireUserTicketCounts(string $username): void
{
    $cacheKey = CacheKey::buildUserRequestTicketsCacheKey($username);
    Cache::forget($cacheKey);

    $cacheKey = CacheKey::buildUserOpenTicketsCacheKey($username);
    Cache::forget($cacheKey);
}

function countOpenTicketsByAchievement(int $achievementID): int
{
    if ($achievementID <= 0) {
        return 0;
    }

    $query = "
        SELECT COUNT(*) as count
        FROM Ticket
        WHERE AchievementID = $achievementID AND ReportState IN (" . TicketState::Open . "," . TicketState::Request . ')';

    $results = legacyDbFetch($query);

    return ($results != null) ? $results['count'] : 0;
}

function countOpenTickets(
    bool $unofficialFlag = false,
    int $ticketFilters = TicketFilters::Default,
    ?string $assignedToUser = null,
    ?string $reportedByUser = null,
    ?string $resolvedByUser = null,
    ?int $gameID = null,
    ?int $achievementID = null
): int {
    $bindings = [];

    // State condition
    $stateCond = getStateCondition($ticketFilters);
    if ($stateCond === null) {
        return 0;
    }

    // Report Type condition
    $reportTypeCond = getReportTypeCondition($ticketFilters);
    if ($reportTypeCond === null) {
        return 0;
    }

    // Hash condition
    $hashCond = getHashCondition($ticketFilters);
    if ($hashCond === null) {
        return 0;
    }

    // Emulator condition
    $emulatorCond = getEmulatorCondition($ticketFilters);

    $modeCond = getModeCondition($ticketFilters);
    if ($modeCond === null) {
        return 0;
    }

    // Developer Active condition
    $devJoin = "";
    $devActiveCond = getDevActiveCondition($ticketFilters);
    if ($devActiveCond === null) {
        return 0;
    }
    if ($devActiveCond != "") {
        $devJoin = "LEFT JOIN UserAccounts AS ua3 ON ua3.User = ach.Author";
    }

    // Not Reporter condition - warning: excludes unresolved tickets
    $reporterJoin = "";
    $notReporterCond = getResolvedByNonReporterCondition($ticketFilters);
    if ($notReporterCond != "") {
        $reporterJoin = "LEFT JOIN UserAccounts AS ua ON ua.ID = tick.reporter_id";
    }

    // Not Author condition - warning: excludes unresolved tickets
    $resolverJoin = "";
    $notAuthorCond = getResolvedByNonAuthorCondition($ticketFilters);
    if ($notAuthorCond != "" || $notReporterCond != "") {
        $resolverJoin = "LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.resolver_id AND tick.ReportState IN (" . TicketState::Closed . "," . TicketState::Resolved . ")";
    }

    // Author condition
    $authorCond = "";
    if ($assignedToUser != null) {
        $authorCond = " AND ach.Author = :assignedToUser";
        $bindings['assignedToUser'] = $assignedToUser;
    }

    // Reporter condition
    $reporterCond = "";
    if ($reportedByUser != null) {
        $reporterJoin = "LEFT JOIN UserAccounts AS ua ON ua.ID = tick.reporter_id";
        $reporterCond = " AND ua.User = :reportedByUsername";
        $bindings['reportedByUsername'] = $reportedByUser;
    }

    // Resolver condition
    $resolverCond = "";
    if ($resolvedByUser != null) {
        $resolverCond = " AND ua2.User = :resolvedByUsername";
        $bindings['resolvedByUsername'] = $resolvedByUser;
        $resolverJoin = "LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.resolver_id AND tick.ReportState IN (" . TicketState::Closed . "," . TicketState::Resolved . ")";
    }

    // Progression condition
    $progressionCond = getProgressionCondition($ticketFilters);

    // Game condition
    $gameCond = "";
    if ($gameID != null) {
        $gameCond = " AND ach.GameID = $gameID";
    }
    if ($achievementID != null) {
        $gameCond .= " AND ach.ID = $achievementID";
    }

    $achFlagCond = $unofficialFlag ? "ach.Flags = '5'" : "ach.Flags = '3'";

    $query = "
        SELECT count(*) as count
        FROM Ticket AS tick
        LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        $reporterJoin
        $resolverJoin
        $devJoin
        WHERE $achFlagCond $stateCond $gameCond $modeCond $reportTypeCond $hashCond $emulatorCond $authorCond $devActiveCond $notAuthorCond $notReporterCond $reporterCond $resolverCond $progressionCond";

    $results = legacyDbFetch($query, $bindings);

    return ($results != null) ? $results['count'] : 0;
}

function gamesSortedByOpenTickets(int $count): array
{
    if ($count < 1) {
        $count = 20;
    }

    $query = "
        SELECT
            gd.ID AS GameID,
            gd.Title AS GameTitle,
            gd.ImageIcon AS GameIcon,
            cons.Name AS Console,
            COUNT(*) as OpenTickets
        FROM
            Ticket AS tick
        LEFT JOIN
            Achievements AS ach ON ach.ID = tick.AchievementID
        LEFT JOIN
            GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN
            Console AS cons ON cons.ID = gd.ConsoleID
        WHERE
            tick.ReportState IN (" . TicketState::Open . "," . TicketState::Request . ") AND ach.Flags = " . AchievementFlag::OfficialCore . "
        GROUP BY
            gd.ID
        ORDER BY
            OpenTickets DESC
        LIMIT 0, $count";

    return legacyDbFetchAll($query)->toArray();
}

/**
 * Gets the ticket state condition to put into the main ticket query.
 */
function getStateCondition(int $ticketFilters): ?string
{
    $states = [];
    if ($ticketFilters & TicketFilters::StateOpen) {
        $states[] = TicketState::Open;
    }
    if ($ticketFilters & TicketFilters::StateClosed) {
        $states[] = TicketState::Closed;
    }
    if ($ticketFilters & TicketFilters::StateResolved) {
        $states[] = TicketState::Resolved;
    }
    if ($ticketFilters & TicketFilters::StateRequest) {
        $states[] = TicketState::Request;
    }

    if (count($states) == 4) {
        // all states selected, no need to filter
        return "";
    }

    if (count($states) == 0) {
        // no states selected, can't matching anything
        return null;
    }

    return " AND tick.ReportState IN (" . implode(',', $states) . ')';
}

/**
 * Gets the ticket report type condition to put into the main ticket query.
 */
function getReportTypeCondition(int $ticketFilters): ?string
{
    $triggeredTickets = ($ticketFilters & TicketFilters::TypeTriggeredAtWrongTime);
    $didNotTriggerTickets = ($ticketFilters & TicketFilters::TypeDidNotTrigger);

    if ($triggeredTickets && $didNotTriggerTickets) {
        return "";
    }
    if ($triggeredTickets) {
        return " AND tick.ReportType LIKE 1";
    }
    if ($didNotTriggerTickets) {
        return " AND tick.ReportType NOT LIKE 1";
    }

    return null;
}

/**
 * Gets the ticket hash condition to put into the main ticket query.
 */
function getHashCondition(int $ticketFilters): ?string
{
    $hashKnownTickets = ($ticketFilters & TicketFilters::HashKnown);
    $hashUnknownTickets = ($ticketFilters & TicketFilters::HashUnknown);

    if ($hashKnownTickets && $hashUnknownTickets) {
        return "";
    }
    if ($hashKnownTickets) {
        return " AND (tick.ReportNotes REGEXP '(MD5|RetroAchievements Hash): [a-fA-F0-9]{32}')";
    }
    if ($hashUnknownTickets) {
        return " AND (tick.ReportNotes NOT REGEXP '(MD5|RetroAchievements Hash): [a-fA-F0-9]{32}')";
    }

    return null;
}

function getModeCondition(int $ticketFilters): ?string
{
    $modeUnknown = ($ticketFilters & TicketFilters::HardcoreUnknown);
    $modeHardcore = ($ticketFilters & TicketFilters::HardcoreOn);
    $modeSoftcore = ($ticketFilters & TicketFilters::HardcoreOff);

    if ($modeUnknown && $modeHardcore && $modeSoftcore) {
        return "";
    }

    if (!$modeUnknown && !$modeHardcore && !$modeSoftcore) {
        return null;
    }

    $subquery = "AND (";
    $added = false;
    if ($modeUnknown) {
        $subquery .= "Hardcore IS NULL";
        $added = true;
    }

    if ($modeHardcore) {
        if ($added) {
            $subquery .= " OR ";
        }
        $subquery .= "Hardcore = " . UnlockMode::Hardcore;
        $added = true;
    }
    if ($modeSoftcore) {
        if ($added) {
            $subquery .= " OR ";
        }
        $subquery .= "Hardcore = " . UnlockMode::Softcore;
        $subquery .= "";
    }
    $subquery .= ")";

    return $subquery;
}

/**
 * Gets the developer active condition to put into the main ticket query.
 */
function getDevActiveCondition(int $ticketFilters): ?string
{
    $devInactive = ($ticketFilters & TicketFilters::DevInactive);
    $devActive = ($ticketFilters & TicketFilters::DevActive);
    $devJunior = ($ticketFilters & TicketFilters::DevJunior);

    if ($devInactive && $devActive && $devJunior) {
        return "";
    }

    if ($devInactive || $devActive || $devJunior) {
        $stateCond = " AND ua3.Permissions IN (";
        if ($devInactive) {
            $stateCond .= "-1,0,1";
        }

        if ($devActive) {
            if ($devInactive) {
                $stateCond .= ",";
            }
            $stateCond .= "3,4";
        }

        if ($devJunior) {
            if ($devInactive || $devActive) {
                $stateCond .= ",";
            }
            $stateCond .= "2";
        }
        $stateCond .= ")";

        return $stateCond;
    }

    return null;
}

/**
 * Gets the Not Author condition to put into the main ticket query.
 * Warning: excludes unresolved tickets
 */
function getResolvedByNonAuthorCondition(int $ticketFilters): string
{
    $notAuthorTickets = ($ticketFilters & TicketFilters::ResolvedByNonAuthor);

    if ($notAuthorTickets) {
        return "AND ua2.User IS NOT NULL AND ua2.User <> ach.Author";
    }

    return "";
}

/**
 * Gets the Not Reporter condition to put into the main ticket query.
 * Warning: excludes unresolved tickets
 */
function getResolvedByNonReporterCondition(int $ticketFilters): string
{
    $notAuthorTickets = ($ticketFilters & TicketFilters::ResolvedByNonReporter);

    if ($notAuthorTickets) {
        return "AND ua.User IS NOT NULL AND ua.User <> ua2.User";
    }

    return "";
}

/**
 * Gets the Progression condition to put into the main ticket query.
 */
function getProgressionCondition(int $ticketFilters): string
{
    $progressionOnly = ($ticketFilters & TicketFilters::ProgressionOnly);

    if ($progressionOnly) {
        return "AND ach.type IS NOT NULL";
    }

    return "";
}

/**
 * Gets the ticket emulator condition to put into the main ticket query.
 */
function getEmulatorCondition(int $ticketFilters): string
{
    $parts = [];

    if ($ticketFilters & TicketFilters::EmulatorRA) {
        $parts[] = "tick.ReportNotes Like '%Emulator: RA%' ";
    }

    if ($ticketFilters & TicketFilters::EmulatorRetroArchCoreSpecified) {
        $parts[] = "tick.ReportNotes LIKE '%Emulator: RetroArch (_%)%' ";
    }

    if ($ticketFilters & TicketFilters::EmulatorRetroArchCoreNotSpecified) {
        $parts[] = "tick.ReportNotes LIKE '%Emulator: RetroArch ()%'";
    }

    if ($ticketFilters & TicketFilters::EmulatorOther) {
        $parts[] = "(tick.ReportNotes LIKE '%Emulator: %' AND tick.ReportNotes NOT LIKE '%Emulator: RA%' AND tick.ReportNotes NOT LIKE '%Emulator: RetroArch%')";
    }

    if ($ticketFilters & TicketFilters::EmulatorUnknown) {
        $parts[] = "tick.ReportNotes NOT LIKE '%Emulator: %'";
    }

    if (count($parts) == 0 || count($parts) == 5) {
        /* no filters selected, or all filters selected. don't filter */
        return '';
    }

    return ' AND (' . implode(' OR ', $parts) . ')';
}

/**
 * Gets the total number of tickets and ticket states for a specific user.
 */
function getTicketsForUser(string $user): array
{
    $retVal = [];
    $query = "SELECT t.AchievementID, ReportState, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              WHERE a.Author = :username AND a.Flags = " . AchievementFlag::OfficialCore . "
              GROUP BY t.AchievementID, ReportState
              ORDER BY t.AchievementID";

    return legacyDbFetchAll($query, ['username' => $user])->toArray();
}

/**
 * Gets the user developed game with the most amount of tickets.
 */
function getUserGameWithMostTickets(string $user): ?array
{
    sanitize_sql_inputs($user);

    $query = "SELECT gd.ID as GameID, gd.Title as GameTitle, gd.ImageIcon as GameIcon, c.Name as ConsoleName, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author = '$user'
              AND a.Flags = '3'
              AND t.ReportState != " . TicketState::Closed . "
              GROUP BY gd.Title
              ORDER BY TicketCount DESC
              LIMIT 1";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }

    return null;
}

/**
 * Gets the user developed achievement with the most amount of tickets.
 */
function getUserAchievementWithMostTickets(string $user): ?array
{
    sanitize_sql_inputs($user);

    $query = "SELECT a.ID, a.Title, a.Description, a.Points, a.BadgeName, gd.Title AS GameTitle, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author = '$user'
              AND a.Flags = '3'
              AND t.ReportState != " . TicketState::Closed . "
              GROUP BY a.ID
              ORDER BY TicketCount DESC
              LIMIT 1";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }

    return null;
}

/**
 * Gets the user who created the most tickets for another user.
 */
function getUserWhoCreatedMostTickets(string $user): ?array
{
    sanitize_sql_inputs($user);

    $query = "SELECT ua.User as TicketCreator, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN UserAccounts as ua ON ua.ID = t.reporter_id
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              WHERE a.Author = '$user'
              AND t.ReportState != " . TicketState::Closed . "
              GROUP BY t.reporter_id
              ORDER BY TicketCount DESC
              LIMIT 1";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }

    return null;
}

/**
 * Gets the number of tickets closed/resolved for other users.
 */
function getNumberOfTicketsClosedForOthers(string $user): array
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT a.Author, COUNT(a.Author) AS TicketCount,
              SUM(CASE WHEN t.ReportState = " . TicketState::Closed . " THEN 1 ELSE 0 END) AS ClosedCount,
              SUM(CASE WHEN t.ReportState = " . TicketState::Resolved . " THEN 1 ELSE 0 END) AS ResolvedCount
              FROM Ticket AS t
              LEFT JOIN UserAccounts as ua ON ua.ID = t.reporter_id
              LEFT JOIN UserAccounts as ua2 ON ua2.ID = t.resolver_id
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              WHERE t.ReportState IN (" . TicketState::Closed . "," . TicketState::Resolved . ")
              AND ua.User NOT LIKE '$user'
              AND a.Author NOT LIKE '$user'
              AND ua2.User LIKE '$user'
              AND a.Flags = '3'
              GROUP BY a.Author
              ORDER BY TicketCount DESC, Author";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}

/**
 * Gets the number of tickets closed/resolved for achievements written by the user.
 */
function getNumberOfTicketsClosed(string $user): array
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT ua2.User AS ResolvedByUser, COUNT(ua2.User) AS TicketCount,
              SUM(CASE WHEN t.ReportState = " . TicketState::Closed . " THEN 1 ELSE 0 END) AS ClosedCount,
              SUM(CASE WHEN t.ReportState = " . TicketState::Resolved . " THEN 1 ELSE 0 END) AS ResolvedCount
              FROM Ticket AS t
              LEFT JOIN UserAccounts as ua ON ua.ID = t.reporter_id
              LEFT JOIN UserAccounts as ua2 ON ua2.ID = t.resolver_id
              LEFT JOIN Achievements as a ON a.ID = t.AchievementID
              WHERE t.ReportState IN (" . TicketState::Closed . "," . TicketState::Resolved . ")
              AND ua.User NOT LIKE '$user'
              AND a.Author LIKE '$user'
              AND a.Flags = '3'
              GROUP BY ResolvedByUser
              ORDER BY TicketCount DESC, ResolvedByUser";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}

function GetTicketModel(int $ticketId): ?TicketViewModel
{
    $ticketDbResult = getTicket($ticketId);

    if ($ticketDbResult == null) {
        return null;
    }

    return new TicketViewModel($ticketDbResult);
}
