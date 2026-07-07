<?php

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Community\Services\SubscriptionService;
use App\Enums\ClientSupportLevel;
use App\Enums\UserPreference;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\PlayerSession;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Ticket\TicketCreatedNotification;
use App\Notifications\Ticket\TicketStatusUpdatedNotification;
use App\Platform\Services\UserAgentService;
use App\Platform\Services\UserTicketCountService;
use Illuminate\Support\Facades\DB;

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

    if ($maintainer) {
        app(UserTicketCountService::class)->clearForUserId($maintainer->id);
    }

    $newTicket->state = TicketState::Open; // normalize to a proper enum value

    // Quarantine a ticket when it's filed from a restricted core or a casual-only emulator.
    $latestSession = PlayerSession::where('user_id', $user->id)
        ->where('game_id', $achievement->game_id)
        ->latest()
        ->first();
    if ($latestSession?->user_agent) {
        $userAgentService = new UserAgentService();

        [$clientSupportLevel, $coreRestriction] = $userAgentService
            ->getSupportLevelAndCoreRestriction($latestSession->user_agent);

        if ($coreRestriction || $clientSupportLevel === ClientSupportLevel::CasualOnly) {
            $newTicket->state = TicketState::Quarantined;
        }

        // Quarantine a ticket when it's filed from an emulator that lacks developer toolkit support.
        if ($newTicket->state !== TicketState::Quarantined) {
            $emulator = $userAgentService->getEmulatorUserAgent($latestSession->user_agent)?->emulator;
            if ($emulator && !$emulator->can_debug_triggers) {
                $newTicket->state = TicketState::Quarantined;
            }
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

function getTicket(int $ticketID): ?array
{
    $row = DB::table('tickets as tick')
        ->leftJoin('achievements as ach', 'ach.id', '=', 'tick.ticketable_id')
        ->leftJoin('games as gd', 'gd.id', '=', 'ach.game_id')
        ->leftJoin('systems as s', 's.id', '=', 'gd.system_id')
        ->leftJoin('users as ua', 'ua.id', '=', 'tick.reporter_id')
        ->leftJoin('users as ua2', 'ua2.id', '=', 'tick.resolver_id')
        ->leftJoin('users as ua3', 'ua3.id', '=', 'tick.ticketable_author_id')
        ->where('tick.id', $ticketID)
        ->where('tick.ticketable_type', 'achievement')
        ->selectRaw(
            'tick.id AS ID, tick.ticketable_id AS AchievementID, ach.title AS AchievementTitle, ach.description AS AchievementDesc, ach.type AS AchievementType, ach.points AS Points, ach.image_name AS BadgeName,
                COALESCE(ua3.display_name, ua3.username) AS AchievementAuthor, ua3.ulid AS AchievementAuthorULID, ach.game_id AS GameID, s.name AS ConsoleName, gd.title AS GameTitle, gd.image_icon_asset_path AS GameIcon,
                tick.created_at AS ReportedAt, tick.type AS ReportType, tick.state AS ReportState, tick.hardcore AS Hardcore, tick.body AS ReportNotes, COALESCE(ua.display_name, ua.username) AS ReportedBy, ua.ulid AS ReportedByULID, tick.resolved_at AS ResolvedAt, COALESCE(ua2.display_name, ua2.username) AS ResolvedBy, ua2.ulid AS ResolvedByULID'
        )
        ->first();

    return $row ? (array) $row : null;
}

function updateTicket(User $userModel, int $ticketID, TicketState $ticketVal, ?string $reason = null): bool
{
    $ticket = Ticket::with(['reporter', 'author', 'ticketable.game.system'])->find($ticketID);

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
            if ($reason === TicketState::REASON_DEMOTED && $ticket->ticketable) {
                $ticket->getTicketableModel()->demoteForTicket($userModel);
            }
            $comment = "Ticket closed by {$userModel->display_name}. Reason: \"$reason\".";
            break;

        case TicketState::Open:
            if ($previousState === TicketState::Request) {
                $comment = "Ticket reassigned to author by {$userModel->display_name}.";
            } elseif ($previousState === TicketState::Quarantined) {
                $comment = "Ticket approved by {$userModel->display_name}.";
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

    $userTicketCountService = app(UserTicketCountService::class);

    if ($ticket->author) {
        $userTicketCountService->clearForUserId($ticket->author->id);
    }

    if ($ticket->reporter) {
        $userTicketCountService->clearForUserId($ticket->reporter->id);

        // Only send email if the reporter has email notifications enabled for ticket activity.
        if (BitSet($ticket->reporter->preferences_bitfield, UserPreference::EmailOn_TicketActivity)) {
            $ticket->reporter->notify(new TicketStatusUpdatedNotification($ticket, $userModel, $status, $comment));
        }
    }

    return true;
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

    return DB::table('tickets as tick')
        ->leftJoin('achievements as ach', 'ach.id', '=', 'tick.ticketable_id')
        ->leftJoin('games as gd', 'gd.id', '=', 'ach.game_id')
        ->leftJoin('systems as s', 's.id', '=', 'gd.system_id')
        ->whereIn('tick.state', [TicketState::Open->value, TicketState::Request->value])
        ->where('tick.ticketable_type', 'achievement')
        ->where('ach.is_promoted', 1)
        ->groupBy('gd.id')
        ->orderByDesc('OpenTickets')
        ->limit($count)
        ->selectRaw('gd.id AS GameID, gd.title AS GameTitle, gd.image_icon_asset_path AS GameIcon, s.name AS Console, COUNT(*) as OpenTickets')
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();
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
    $row = DB::table('tickets as t')
        ->leftJoin('achievements as ach', 'ach.id', '=', 't.ticketable_id')
        ->leftJoin('games as gd', 'gd.id', '=', 'ach.game_id')
        ->leftJoin('systems as s', 's.id', '=', 'gd.system_id')
        ->where('t.ticketable_author_id', $user->id)
        ->where('t.ticketable_type', 'achievement')
        ->where('ach.is_promoted', 1)
        ->where('t.state', '!=', TicketState::Closed->value)
        ->groupBy('gd.title')
        ->orderByDesc('TicketCount')
        ->selectRaw('gd.id as GameID, gd.title as GameTitle, gd.image_icon_asset_path as GameIcon, s.name as ConsoleName, COUNT(*) as TicketCount')
        ->first();

    return $row ? (array) $row : null;
}

/**
 * Gets the user developed achievement with the most amount of tickets.
 */
function getUserAchievementWithMostTickets(User $user): ?array
{
    $row = DB::table('tickets as t')
        ->leftJoin('achievements as ach', 'ach.id', '=', 't.ticketable_id')
        ->leftJoin('games as gd', 'gd.id', '=', 'ach.game_id')
        ->leftJoin('systems as s', 's.id', '=', 'gd.system_id')
        ->where('t.ticketable_author_id', $user->id)
        ->where('t.ticketable_type', 'achievement')
        ->where('ach.is_promoted', 1)
        ->where('t.state', '!=', TicketState::Closed->value)
        ->groupBy('ach.id')
        ->orderByDesc('TicketCount')
        ->selectRaw('ach.id AS ID, ach.title AS Title, ach.description AS Description, ach.points AS Points, ach.image_name AS BadgeName, gd.title AS GameTitle, COUNT(*) as TicketCount')
        ->first();

    return $row ? (array) $row : null;
}

/**
 * Gets the user who created the most tickets for another user.
 */
function getUserWhoCreatedMostTickets(User $user): ?array
{
    $row = DB::table('tickets as t')
        ->leftJoin('users as ua', 'ua.id', '=', 't.reporter_id')
        ->leftJoin('achievements as ach', 'ach.id', '=', 't.ticketable_id')
        ->where('t.ticketable_author_id', $user->id)
        ->where('t.ticketable_type', 'achievement')
        ->where('t.state', '!=', TicketState::Closed->value)
        ->groupBy('t.reporter_id')
        ->orderByDesc('TicketCount')
        ->selectRaw('ua.username as TicketCreator, COUNT(*) as TicketCount')
        ->first();

    return $row ? (array) $row : null;
}

/**
 * Gets the number of tickets closed/resolved for other users.
 */
function getNumberOfTicketsClosedForOthers(User $user): array
{
    return DB::table('tickets as t')
        ->leftJoin('users as ua', 'ua.id', '=', 't.reporter_id')
        ->leftJoin('users as ua2', 'ua2.id', '=', 't.resolver_id')
        ->leftJoin('achievements as ach', 'ach.id', '=', 't.ticketable_id')
        ->leftJoin('users as ua3', 'ua3.id', '=', 't.ticketable_author_id')
        ->whereIn('t.state', [TicketState::Closed->value, TicketState::Resolved->value])
        ->where('t.ticketable_type', 'achievement')
        ->where('ua.id', '!=', $user->id)
        ->where('t.ticketable_author_id', '!=', $user->id)
        ->where('ua2.id', $user->id)
        ->where('ach.is_promoted', 1)
        ->groupBy('t.ticketable_author_id')
        ->orderByDesc('TicketCount')
        ->orderBy('Author')
        ->selectRaw("ua3.username AS Author, COUNT(t.ticketable_author_id) AS TicketCount,
              SUM(CASE WHEN t.state = 'closed' THEN 1 ELSE 0 END) AS ClosedCount,
              SUM(CASE WHEN t.state = 'resolved' THEN 1 ELSE 0 END) AS ResolvedCount")
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();
}

/**
 * Gets the number of tickets closed/resolved for achievements written by the user.
 */
function getNumberOfTicketsClosed(User $user): array
{
    return DB::table('tickets as t')
        ->leftJoin('users as ua2', 'ua2.id', '=', 't.resolver_id')
        ->leftJoin('achievements as ach', 'ach.id', '=', 't.ticketable_id')
        ->whereIn('t.state', [TicketState::Closed->value, TicketState::Resolved->value])
        ->where('t.ticketable_type', 'achievement')
        ->where('t.reporter_id', '!=', $user->id)
        ->where('t.ticketable_author_id', $user->id)
        ->where('ach.is_promoted', 1)
        ->groupByRaw('ResolvedByUser')
        ->orderByDesc('TicketCount')
        ->orderBy('ResolvedByUser')
        ->selectRaw("ua2.username AS ResolvedByUser, COUNT(ua2.username) AS TicketCount,
              SUM(CASE WHEN t.state = 'closed' THEN 1 ELSE 0 END) AS ClosedCount,
              SUM(CASE WHEN t.state = 'resolved' THEN 1 ELSE 0 END) AS ResolvedCount")
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();
}
