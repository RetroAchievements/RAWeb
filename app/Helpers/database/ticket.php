<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\TicketState;
use App\Enums\UserPreference;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketStatusUpdatedMail;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
    $user = User::whereName($userSubmitter)->first();

    if (!$user->exists() || !$user->can('create', Ticket::class)) {
        $returnMsg['Success'] = false;

        return $returnMsg;
    }

    $note = $noteIn;

    $gameHash = GameHash::where('md5', '=', $RAHash)->first();
    if (!$gameHash) {
        $note .= "\nRetroAchievements Hash: $RAHash";
    }

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
            if ($gameHash) {
                Ticket::where('id', $ticketID)->update(['game_hash_id' => $gameHash->id]);
            }

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

function sendInitialTicketEmailToAssignee(Ticket $ticket, Game $game, Achievement $achievement): void
{
    $maintainer = $achievement->getMaintainerAt(now());

    if (
        $maintainer
        && $maintainer->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])
        && BitSet($maintainer->websitePrefs, UserPreference::EmailOn_TicketActivity)
    ) {
        Mail::to($maintainer->EmailAddress)->queue(
            new TicketCreatedMail($maintainer, $ticket, $game, $achievement, isMaintainer: true)
        );
    }
}

function sendInitialTicketEmailsToSubscribers(Ticket $ticket, Game $game, Achievement $achievement): void
{
    $subscribers = getSubscribersOf(SubscriptionSubjectType::GameTickets, $game->id, 1 << UserPreference::EmailOn_TicketActivity);
    foreach ($subscribers as $sub) {
        if ($sub['User'] !== $achievement->developer->User && $sub['User'] != $ticket->reporter->username) {
            // Find the user model for the subscriber.
            $subscriberUser = User::whereName($sub['User'])->first();
            if ($subscriberUser) {
                Mail::to($sub['EmailAddress'])->queue(
                    new TicketCreatedMail($subscriberUser, $ticket, $game, $achievement, isMaintainer: false)
                );
            }
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
    $maintainer = $achievement->getMaintainerAt(now());

    $newTicket = Ticket::create([
        'AchievementID' => $achievement->id,
        'reporter_id' => $user->id,
        'ticketable_author_id' => $maintainer?->id,
        'ReportType' => $reportType,
        'Hardcore' => $hardcoreValue,
        'ReportNotes' => $note,
    ]);

    expireUserTicketCounts($maintainer);

    sendInitialTicketEmailToAssignee($newTicket, $achievement->game, $achievement);

    // notify subscribers other than the achievement's author
    sendInitialTicketEmailsToSubscribers($newTicket, $achievement->game, $achievement);

    return $newTicket->id;
}

function getExistingTicketID(User $user, int $achievementID): int
{
    $ticket = Ticket::whereReporterId($user->id)
        ->where('AchievementID', $achievementID)
        ->whereNotIn('ReportState', [TicketState::Closed, TicketState::Resolved])
        ->first();

    return $ticket ? $ticket->id : 0;
}

function getTicket(int $ticketID): ?array
{
    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.type AS AchievementType, ach.Points, ach.BadgeName,
                COALESCE(ua3.display_name, ua3.User) AS AchievementAuthor, ua3.ulid AS AchievementAuthorULID, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
                tick.ReportedAt, tick.ReportType, tick.ReportState, tick.Hardcore, tick.ReportNotes, COALESCE(ua.display_name, ua.User) AS ReportedBy, ua.ulid AS ReportedByULID, tick.ResolvedAt, COALESCE(ua2.display_name, ua2.User) AS ResolvedBy, ua2.ulid AS ResolvedByULID
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

function updateTicket(User $userModel, int $ticketID, int $ticketVal, ?string $reason = null): bool
{
    $ticket = Ticket::with(['reporter', 'author', 'achievement.game.system'])->find($ticketID);

    if (!$ticket) {
        return false;
    }

    $previousState = $ticket->ReportState;
    $ticket->ReportState = $ticketVal;

    if ($ticketVal == TicketState::Resolved || $ticketVal == TicketState::Closed) {
        $ticket->ResolvedAt = now();
        $ticket->resolver_id = $userModel->id;
    } elseif (in_array($previousState, [TicketState::Resolved, TicketState::Closed])) {
        // Clear any resolver info when reopening a previously resolved ticket.
        $ticket->ResolvedAt = null;
        $ticket->resolver_id = null;
    }

    $ticket->save();

    $status = TicketState::toString($ticketVal);
    $comment = null;

    switch ($ticketVal) {
        case TicketState::Closed:
            if ($reason == TicketState::REASON_DEMOTED && $ticket->achievement) {
                updateAchievementFlag($ticket->achievement->id, AchievementFlag::Unofficial);
                addArticleComment("Server", ArticleType::Achievement, $ticket->achievement->id, "{$userModel->display_name} demoted this achievement to Unofficial.", $userModel->display_name);
            }
            $comment = "Ticket closed by {$userModel->display_name}. Reason: \"$reason\".";
            break;

        case TicketState::Open:
            if ($previousState == TicketState::Request) {
                $comment = "Ticket reassigned to author by {$userModel->display_name}.";
            } else {
                $comment = "Ticket reopened by {$userModel->display_name}.";
            }
            break;

        case TicketState::Resolved:
            $comment = "Ticket resolved as fixed by {$userModel->display_name}.";
            break;

        case TicketState::Request:
            $comment = "Ticket reassigned to reporter by {$userModel->display_name}.";
            break;
    }

    // add the system comment without generating an email. subscribers get an email below.
    $serverUserId = getUserIDFromUser('Server');
    if ($serverUserId > 0) {
        Comment::create([
            'ArticleType' => ArticleType::AchievementTicket,
            'ArticleID' => $ticketID,
            'Payload' => $comment,
            'user_id' => $serverUserId,
        ]);
    }

    if ($ticket->author) {
        expireUserTicketCounts($ticket->author);
    }

    if ($ticket->reporter) {
        expireUserTicketCounts($ticket->reporter);

        // Only send email if the reporter has email notifications enabled for ticket activity.
        if (BitSet($ticket->reporter->websitePrefs, UserPreference::EmailOn_TicketActivity)) {
            Mail::to($ticket->reporter->EmailAddress)->queue(
                new TicketStatusUpdatedMail($ticket, $userModel, $status, $comment)
            );
        }
    }

    return true;
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
            $query->whereIn('Flags', [AchievementFlag::OfficialCore->value, AchievementFlag::Unofficial->value]);
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

function expireUserTicketCounts(?User $user): void
{
    if ($user) {
        $cacheKey = CacheKey::buildUserRequestTicketsCacheKey($user->User);
        Cache::forget($cacheKey);
    }
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
            tick.ReportState IN (" . TicketState::Open . "," . TicketState::Request . ") AND ach.Flags = " . AchievementFlag::OfficialCore->value . "
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
            $query->where('Flags', AchievementFlag::OfficialCore->value);
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
              AND ach.Flags = " . AchievementFlag::OfficialCore->value . "
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
              AND ach.Flags = " . AchievementFlag::OfficialCore->value . "
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
              AND ach.Flags = " . AchievementFlag::OfficialCore->value . "
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
              AND ach.Flags = " . AchievementFlag::OfficialCore->value . "
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
