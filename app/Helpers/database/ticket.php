<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\TicketState;
use App\Community\ViewModels\Ticket as TicketViewModel;
use App\Enums\UserPreference;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
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

function constructAchievementTicketBugReportDetails(
    Ticket $ticket,
    Game $game,
    Achievement $achievement
): string {
    $problemTypeStr = ($ticket->type === 1) ? "Triggers at wrong time" : "Doesn't trigger";
    $ticketUrl = route('ticket.show', ['ticket' => $ticket]);

    $bugReportDetails = "
Achievement: {$achievement->title}
Game: {$game->title}
Problem: {$problemTypeStr}
Comment: {$ticket->body}

This ticket will be raised and will be available for all developers to inspect and manage at the following URL:
<a href='{$ticketUrl}'>{$ticketUrl}</a>

Thanks!";

    return $bugReportDetails;
}

function sendInitialTicketEmailToAssignee(Ticket $ticket, Game $game, Achievement $achievement): void
{
    $emailHeader = "Bug Report ({$game->title})";
    $bugReportDetails = constructAchievementTicketBugReportDetails(
        $ticket,
        $game,
        $achievement,
    );

    if ($achievement->developer && BitSet($achievement->developer->websitePrefs, UserPreference::EmailOn_PrivateMessage)) {
        $emailBody = "Hi, {$achievement->developer->display_name}!

{$ticket->reporter->display_name} would like to report a bug with an achievement you've created:
$bugReportDetails";
        sendRAEmail($achievement->developer->EmailAddress, $emailHeader, $emailBody);
    }
}

function sendInitialTicketEmailsToSubscribers(Ticket $ticket, Game $game, Achievement $achievement): void
{
    $emailHeader = "Bug Report ({$game->title})";
    $bugReportDetails = constructAchievementTicketBugReportDetails(
        $ticket,
        $game,
        $achievement,
    );

    $subscribers = getSubscribersOf(SubscriptionSubjectType::GameTickets, $game->id, 1 << UserPreference::EmailOn_PrivateMessage);
    foreach ($subscribers as $sub) {
        if ($sub['User'] !== $achievement->developer->User && $sub['User'] != $ticket->reporter->username) {
            $emailBody = "Hi, " . $sub['User'] . "!

{$ticket->reporter->display_name} would like to report a bug with an achievement you're subscribed to:
$bugReportDetails";
            sendRAEmail($sub['EmailAddress'], $emailHeader, $emailBody);
        }
    }
}

function _createTicket(User $user, int $achievementId, int $reportType, ?int $hardcore, string $note): int
{
    $achievement = Achievement::find($achievementId);
    if (!$achievement) {
        return 0;
    }

    $hardcoreValue = $hardcore === null ? 'NULL' : (string) $hardcore;

    $newTicket = Ticket::create([
        'AchievementID' => $achievement->id,
        'reporter_id' => $user->id,
        'ticketable_author_id' => $achievement->developer->id,
        'ReportType' => $reportType,
        'Hardcore' => $hardcoreValue,
        'ReportNotes' => $note,
    ]);

    expireUserTicketCounts($achievement->developer->User);

    sendInitialTicketEmailToAssignee($newTicket, $achievement->game, $achievement);

    // notify subscribers other than the achievement's author
    sendInitialTicketEmailsToSubscribers($newTicket, $achievement->game, $achievement);

    return $newTicket->id;
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

function getTicket(int $ticketID): ?array
{
    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.type AS AchievementType, ach.Points, ach.BadgeName,
                ua3.User AS AchievementAuthor, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
                tick.ReportedAt, tick.ReportType, tick.ReportState, tick.Hardcore, tick.ReportNotes, ua.User AS ReportedBy, tick.ResolvedAt, ua2.User AS ResolvedBy
              FROM Ticket AS tick
              LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = tick.reporter_id
              LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.resolver_id
              LEFT JOIN UserAccounts AS ua3 ON ua3.ID = tick.ticketable_author_id
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
        "Click <a href='" . route('ticket.show', ['ticket' => $ticketID]) . "'>here</a> to view the ticket" .
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
    $retVal = [
        TicketState::Open => 0,
        TicketState::Request => 0,
    ];

    $counts = Ticket::with('achievement')
        ->where('ticketable_author_id', $dev->id)
        ->whereHas('achievement', function ($query) {
            $query->whereIn('Flags', [AchievementFlag::OfficialCore, AchievementFlag::Unofficial]);
        })
        ->whereIn('ReportState', [TicketState::Open, TicketState::Request])
        ->select('ReportState', DB::raw('count(*) as Count'))
        ->groupBy('ReportState')
        ->pluck('Count', 'ReportState');

    foreach ($counts as $state => $count) {
        $retVal[$state] = (int) $count;
    }

    return $retVal;
}

// TODO use $user->id
function expireUserTicketCounts(string $username): void
{
    $cacheKey = CacheKey::buildUserRequestTicketsCacheKey($username);
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
 * Gets the total number of tickets and ticket states for a specific user.
 */
function getTicketsForUser(User $user): array
{
    $query = Ticket::select('AchievementID', 'ReportState', DB::raw('COUNT(*) as TicketCount'))
        ->whereHas('author', function ($query) use ($user) {
            $query->where('ID', $user->id);
        })
        ->whereHas('achievement', function ($query) {
            $query->where('Flags', AchievementFlag::OfficialCore);
        })
        ->groupBy('AchievementID', 'ReportState')
        ->orderBy('AchievementID')
        ->get();

    return $query->toArray();
}

/**
 * Gets the user developed game with the most amount of tickets.
 */
function getUserGameWithMostTickets(User $user): ?array
{
    $query = "SELECT gd.ID as GameID, gd.Title as GameTitle, gd.ImageIcon as GameIcon, c.Name as ConsoleName, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN Achievements as ach ON ach.ID = t.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE t.ticketable_author_id = {$user->id}
              AND ach.Flags = " . AchievementFlag::OfficialCore . "
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
function getUserAchievementWithMostTickets(User $user): ?array
{
    $query = "SELECT ach.ID, ach.Title, ach.Description, ach.Points, ach.BadgeName, gd.Title AS GameTitle, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN Achievements as ach ON ach.ID = t.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE t.ticketable_author_id = {$user->id}
              AND ach.Flags = " . AchievementFlag::OfficialCore . "
              AND t.ReportState != " . TicketState::Closed . "
              GROUP BY ach.ID
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
function getUserWhoCreatedMostTickets(User $user): ?array
{
    $query = "SELECT ua.User as TicketCreator, COUNT(*) as TicketCount
              FROM Ticket AS t
              LEFT JOIN UserAccounts as ua ON ua.ID = t.reporter_id
              LEFT JOIN Achievements as ach ON ach.ID = t.AchievementID
              WHERE t.ticketable_author_id = {$user->id}
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
function getNumberOfTicketsClosedForOthers(User $user): array
{
    $retVal = [];
    $query = "SELECT ua3.User AS Author, COUNT(t.ticketable_author_id) AS TicketCount,
              SUM(CASE WHEN t.ReportState = " . TicketState::Closed . " THEN 1 ELSE 0 END) AS ClosedCount,
              SUM(CASE WHEN t.ReportState = " . TicketState::Resolved . " THEN 1 ELSE 0 END) AS ResolvedCount
              FROM Ticket AS t
              LEFT JOIN UserAccounts as ua ON ua.ID = t.reporter_id
              LEFT JOIN UserAccounts as ua2 ON ua2.ID = t.resolver_id
              LEFT JOIN Achievements as ach ON ach.ID = t.AchievementID
              LEFT JOIN UserAccounts as ua3 ON ua3.ID = t.ticketable_author_id
              WHERE t.ReportState IN (" . TicketState::Closed . "," . TicketState::Resolved . ")
              AND ua.ID != {$user->id}
              AND t.ticketable_author_id != {$user->id}
              AND ua2.ID = {$user->id}
              AND ach.Flags = " . AchievementFlag::OfficialCore . "
              GROUP BY t.ticketable_author_id
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
function getNumberOfTicketsClosed(User $user): array
{
    $retVal = [];
    $query = "SELECT ua2.User AS ResolvedByUser, COUNT(ua2.User) AS TicketCount,
              SUM(CASE WHEN t.ReportState = " . TicketState::Closed . " THEN 1 ELSE 0 END) AS ClosedCount,
              SUM(CASE WHEN t.ReportState = " . TicketState::Resolved . " THEN 1 ELSE 0 END) AS ResolvedCount
              FROM Ticket AS t
              LEFT JOIN UserAccounts as ua2 ON ua2.ID = t.resolver_id
              LEFT JOIN Achievements as ach ON ach.ID = t.AchievementID
              WHERE t.ReportState IN (" . TicketState::Closed . "," . TicketState::Resolved . ")
              AND t.reporter_id != {$user->id}
              AND t.ticketable_author_id = {$user->id}
              AND ach.Flags = " . AchievementFlag::OfficialCore . "
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
