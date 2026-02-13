<?php

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Community\Services\SubscriptionService;
use App\Enums\UserPreference;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerSession;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Ticket\TicketCreatedNotification;
use App\Notifications\Ticket\TicketStatusUpdatedNotification;
use App\Platform\Services\UserAgentService;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function submitNewTicketsJSON(
    string $userSubmitter,
    string $idsCSV,
    int $reportType,
    string $noteIn,
    string $RAHash,
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

function sendInitialTicketEmailToAssignee(Ticket $ticket, Game $game, Achievement $achievement): void
{
    $maintainer = $achievement->getMaintainerAt(now());

    if (
        $maintainer
        && $maintainer->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])
        && BitSet($maintainer->preferences_bitfield, UserPreference::EmailOn_TicketActivity)
    ) {
        $maintainer->notify(new TicketCreatedNotification($ticket, $game, $achievement, isMaintainer: true));
    }
}

function sendInitialTicketEmailsToSubscribers(Ticket $ticket, Game $game, Achievement $achievement): void
{
    $maintainer = $achievement->getMaintainerAt(now());

    $subscriptionService = new SubscriptionService();
    $subscribers = $subscriptionService->getSubscribers(SubscriptionSubjectType::GameTickets, $game->id)
        ->filter(fn ($s) => isset($s->email) && BitSet($s->preferences_bitfield, UserPreference::EmailOn_TicketActivity));

    foreach ($subscribers as $subscriber) {
        if ($subscriber->is($maintainer)) {
            // maintainer explicitly notified regardless of subscription state via
            // sendInitialTicketEmailToAssignee. don't notify them again.
        } elseif ($subscriber->is($ticket->reporter)) {
            // reporter doesn't need to be notified of the new ticket. they just created it!
        } else {
            $subscriber->notify(new TicketCreatedNotification($ticket, $game, $achievement, isMaintainer: false));
        }
    }
}

function _createTicket(User $user, int $achievementId, int $reportType, ?int $hardcore, string $note): int
{
    $achievement = Achievement::find($achievementId);
    if (!$achievement) {
        return 0;
    }

    $hardcoreValue = $hardcore === null ? null : (bool) $hardcore;
    $maintainer = $achievement->getMaintainerAt(now());

    $newTicket = Ticket::create([
        'ticketable_type' => 'achievement',
        'ticketable_id' => $achievement->id,
        'reporter_id' => $user->id,
        'ticketable_author_id' => $maintainer?->id,
        'type' => TicketType::fromLegacyInteger($reportType),
        'hardcore' => $hardcoreValue,
        'body' => $note,
    ]);

    expireUserTicketCounts($maintainer);

    $newTicket->state = TicketState::Open; // normalize to a proper enum value

    // Quarantine a ticket when it's filed from a core with a restriction.
    $latestSession = PlayerSession::where('user_id', $user->id)
        ->where('game_id', $achievement->game_id)
        ->latest()
        ->first();
    if ($latestSession?->user_agent) {
        $userAgentService = new UserAgentService();
        $coreRestriction = $userAgentService->getCoreRestrictionForUserAgent($latestSession->user_agent);

        if ($coreRestriction) {
            $newTicket->state = TicketState::Quarantined;
        }
    }

    $newTicket->save();

    // Don't notify developers about quarantined tickets.
    if ($newTicket->state !== TicketState::Quarantined) {
        sendInitialTicketEmailToAssignee($newTicket, $achievement->game, $achievement);
        sendInitialTicketEmailsToSubscribers($newTicket, $achievement->game, $achievement);
    }

    return $newTicket->id;
}

function getExistingTicketID(User $user, int $achievementID): int
{
    $ticket = Ticket::where('reporter_id', $user->id)
        ->where('ticketable_id', $achievementID)
        ->where('ticketable_type', 'achievement')
        ->whereNotIn('state', [TicketState::Closed, TicketState::Resolved])
        ->first();

    return $ticket ? $ticket->id : 0;
}

function getTicket(int $ticketID): ?array
{
    $query = "SELECT tick.id AS ID, tick.ticketable_id AS AchievementID, ach.title AS AchievementTitle, ach.description AS AchievementDesc, ach.type AS AchievementType, ach.points AS Points, ach.image_name AS BadgeName,
                COALESCE(ua3.display_name, ua3.username) AS AchievementAuthor, ua3.ulid AS AchievementAuthorULID, ach.game_id AS GameID, s.name AS ConsoleName, gd.title AS GameTitle, gd.image_icon_asset_path AS GameIcon,
                tick.created_at AS ReportedAt, tick.type AS ReportType, tick.state AS ReportState, tick.hardcore AS Hardcore, tick.body AS ReportNotes, COALESCE(ua.display_name, ua.username) AS ReportedBy, ua.ulid AS ReportedByULID, tick.resolved_at AS ResolvedAt, COALESCE(ua2.display_name, ua2.username) AS ResolvedBy, ua2.ulid AS ResolvedByULID
              FROM tickets AS tick
              LEFT JOIN achievements AS ach ON ach.id = tick.ticketable_id
              LEFT JOIN games AS gd ON gd.id = ach.game_id
              LEFT JOIN systems AS s ON s.id = gd.system_id
              LEFT JOIN users AS ua ON ua.id = tick.reporter_id
              LEFT JOIN users AS ua2 ON ua2.id = tick.resolver_id
              LEFT JOIN users AS ua3 ON ua3.id = tick.ticketable_author_id
              WHERE tick.id = $ticketID
              AND tick.ticketable_type = 'achievement'
              ";

    return legacyDbFetch($query);
}

function updateTicket(User $userModel, int $ticketID, TicketState $ticketVal, ?string $reason = null): bool
{
    $ticket = Ticket::with(['reporter', 'author', 'achievement.game.system'])->find($ticketID);

    if (!$ticket) {
        return false;
    }

    $previousState = $ticket->state;
    $ticket->state = $ticketVal;

    if ($ticketVal === TicketState::Resolved || $ticketVal === TicketState::Closed) {
        $ticket->resolved_at = now();
        $ticket->resolver_id = $userModel->id;
    } elseif (in_array($previousState, [TicketState::Resolved, TicketState::Closed])) {
        // Clear any resolver info when reopening a previously resolved ticket.
        $ticket->resolved_at = null;
        $ticket->resolver_id = null;
    }

    $ticket->save();

    $status = $ticketVal->label();
    $comment = null;

    switch ($ticketVal) {
        case TicketState::Closed:
            if ($reason == TicketState::REASON_DEMOTED && $ticket->achievement) {
                updateAchievementPromotedStatus($ticket->achievement->id, false);
                addArticleComment("Server", CommentableType::Achievement, $ticket->achievement->id, "{$userModel->display_name} demoted this achievement to Unofficial.", $userModel->display_name);
            }
            $comment = "Ticket closed by {$userModel->display_name}. Reason: \"$reason\".";
            break;

        case TicketState::Open:
            if ($previousState === TicketState::Request) {
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
            'commentable_type' => CommentableType::AchievementTicket,
            'commentable_id' => $ticketID,
            'body' => $comment,
            'user_id' => $serverUserId,
        ]);
    }

    if ($ticket->author) {
        expireUserTicketCounts($ticket->author);
    }

    if ($ticket->reporter) {
        expireUserTicketCounts($ticket->reporter);

        // Only send email if the reporter has email notifications enabled for ticket activity.
        if (BitSet($ticket->reporter->preferences_bitfield, UserPreference::EmailOn_TicketActivity)) {
            $ticket->reporter->notify(new TicketStatusUpdatedNotification($ticket, $userModel, $status, $comment));
        }
    }

    return true;
}

function countRequestTicketsByUser(?User $user = null): int
{
    if ($user === null) {
        return 0;
    }

    $cacheKey = CacheKey::buildUserRequestTicketsCacheKey($user->username);

    return Cache::remember($cacheKey, Carbon::now()->addHours(20), function () use ($user) {
        return Ticket::where('state', TicketState::Request)
            ->where('reporter_id', $user->id)
            ->count();
    });
}

function countOpenTicketsByDev(User $dev): array
{
    $retVal = [
        TicketState::Open->value => 0,
        TicketState::Request->value => 0,
    ];

    $counts = Ticket::with('achievement')
        ->where('ticketable_author_id', $dev->id)
        ->whereHas('achievement')
        ->whereIn('state', [TicketState::Open, TicketState::Request])
        ->select('state', DB::raw('count(*) as Count'))
        ->groupBy('state')
        ->pluck('Count', 'state');

    foreach ($counts as $state => $count) {
        $retVal[$state] = (int) $count;
    }

    return $retVal;
}

function expireUserTicketCounts(?User $user): void
{
    if ($user) {
        $cacheKey = CacheKey::buildUserRequestTicketsCacheKey($user->username);
        Cache::forget($cacheKey);
    }
}

function countOpenTicketsByAchievement(int $achievementID): int
{
    if ($achievementID <= 0) {
        return 0;
    }

    return Ticket::where('ticketable_id', $achievementID)
        ->where('ticketable_type', 'achievement')
        ->whereIn('state', [TicketState::Open, TicketState::Request])
        ->count();
}

function gamesSortedByOpenTickets(int $count): array
{
    if ($count < 1) {
        $count = 20;
    }

    $query = "
        SELECT
            gd.id AS GameID,
            gd.title AS GameTitle,
            gd.image_icon_asset_path AS GameIcon,
            s.name AS Console,
            COUNT(*) as OpenTickets
        FROM
            tickets AS tick
        LEFT JOIN
            achievements AS ach ON ach.id = tick.ticketable_id
        LEFT JOIN
            games AS gd ON gd.id = ach.game_id
        LEFT JOIN
            systems AS s ON s.id = gd.system_id
        WHERE
            tick.state IN ('open', 'request')
            AND tick.ticketable_type = 'achievement'
            AND ach.is_promoted = 1
        GROUP BY
            gd.id
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
    $query = Ticket::select('ticketable_id', 'state', DB::raw('COUNT(*) as TicketCount'))
        ->whereHas('author', function ($query) use ($user) {
            $query->where('id', $user->id);
        })
        ->whereHas('achievement', function ($query) {
            $query->where('is_promoted', true);
        })
        ->groupBy('ticketable_id', 'state')
        ->orderBy('ticketable_id')
        ->get();

    return $query->toArray();
}

/**
 * Gets the user developed game with the most amount of tickets.
 */
function getUserGameWithMostTickets(User $user): ?array
{
    $query = "SELECT gd.id as GameID, gd.title as GameTitle, gd.image_icon_asset_path as GameIcon, s.name as ConsoleName, COUNT(*) as TicketCount
              FROM tickets AS t
              LEFT JOIN achievements as ach ON ach.id = t.ticketable_id
              LEFT JOIN games AS gd ON gd.id = ach.game_id
              LEFT JOIN systems AS s ON s.id = gd.system_id
              WHERE t.ticketable_author_id = {$user->id}
              AND t.ticketable_type = 'achievement'
              AND ach.is_promoted = 1
              AND t.state != 'closed'
              GROUP BY gd.title
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
    $query = "SELECT ach.id AS ID, ach.title AS Title, ach.description AS Description, ach.points AS Points, ach.image_name AS BadgeName, gd.title AS GameTitle, COUNT(*) as TicketCount
              FROM tickets AS t
              LEFT JOIN achievements as ach ON ach.id = t.ticketable_id
              LEFT JOIN games AS gd ON gd.id = ach.game_id
              LEFT JOIN systems AS s ON s.id = gd.system_id
              WHERE t.ticketable_author_id = {$user->id}
              AND t.ticketable_type = 'achievement'
              AND ach.is_promoted = 1
              AND t.state != 'closed'
              GROUP BY ach.id
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
    $query = "SELECT ua.username as TicketCreator, COUNT(*) as TicketCount
              FROM tickets AS t
              LEFT JOIN users as ua ON ua.id = t.reporter_id
              LEFT JOIN achievements as ach ON ach.id = t.ticketable_id
              WHERE t.ticketable_author_id = {$user->id}
              AND t.ticketable_type = 'achievement'
              AND t.state != 'closed'
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
    $query = "SELECT ua3.username AS Author, COUNT(t.ticketable_author_id) AS TicketCount,
              SUM(CASE WHEN t.state = 'closed' THEN 1 ELSE 0 END) AS ClosedCount,
              SUM(CASE WHEN t.state = 'resolved' THEN 1 ELSE 0 END) AS ResolvedCount
              FROM tickets AS t
              LEFT JOIN users as ua ON ua.id = t.reporter_id
              LEFT JOIN users as ua2 ON ua2.id = t.resolver_id
              LEFT JOIN achievements as ach ON ach.id = t.ticketable_id
              LEFT JOIN users as ua3 ON ua3.id = t.ticketable_author_id
              WHERE t.state IN ('closed', 'resolved')
              AND t.ticketable_type = 'achievement'
              AND ua.id != {$user->id}
              AND t.ticketable_author_id != {$user->id}
              AND ua2.id = {$user->id}
              AND ach.is_promoted = 1
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
    $query = "SELECT ua2.username AS ResolvedByUser, COUNT(ua2.username) AS TicketCount,
              SUM(CASE WHEN t.state = 'closed' THEN 1 ELSE 0 END) AS ClosedCount,
              SUM(CASE WHEN t.state = 'resolved' THEN 1 ELSE 0 END) AS ResolvedCount
              FROM tickets AS t
              LEFT JOIN users as ua2 ON ua2.id = t.resolver_id
              LEFT JOIN achievements as ach ON ach.id = t.ticketable_id
              WHERE t.state IN ('closed', 'resolved')
              AND t.ticketable_type = 'achievement'
              AND t.reporter_id != {$user->id}
              AND t.ticketable_author_id = {$user->id}
              AND ach.is_promoted = 1
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
