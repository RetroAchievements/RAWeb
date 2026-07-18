<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Enums\PlayerGameActivityEventType;
use App\Enums\PlayerGameActivitySessionType;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\TicketableType;

class TicketViewService
{
    public int $unlocksSinceReported = 0;
    public array $openTickets = [];
    public array $closedTickets = [];
    public string $contactReporterUrl = '';
    public ?PlayerAchievement $existingUnlock = null;
    public ?LeaderboardEntry $reporterLeaderboardEntry = null;
    public string $ticketNotes = '';

    public function __construct(
        protected PlayerGameActivityService $activity = new PlayerGameActivityService(),
    ) {
    }

    public function load(Ticket $ticket): void
    {
        $ticketable = $ticket->getTicketableModel();
        $isAchievement = $ticket->ticketable_type === TicketableType::Achievement->value;

        if ($ticket->reporter) {
            $msgTitle = rawurlencode("Bug Report ({$ticketable->getTicketableGame()->title})");
            $msgPayload = "Hi [user={$ticket->reporter->display_name}], I'm contacting you about [ticket={$ticket->id}]";
            $msgPayload = rawurlencode($msgPayload);
            $this->contactReporterUrl = route('message-thread.create') . "?to={$ticket->reporter->display_name}&subject=$msgTitle&message=$msgPayload";

            if ($isAchievement) {
                $this->existingUnlock = PlayerAchievement::where('user_id', $ticket->reporter->id)
                    ->where('achievement_id', $ticketable->id)
                    ->first();
            } else {
                $this->reporterLeaderboardEntry = LeaderboardEntry::where('leaderboard_id', $ticketable->id)
                    ->where('user_id', $ticket->reporter->id)
                    ->first();
            }

            $this->openTickets = [];
            $this->closedTickets = [];
            $relatedTickets = Ticket::where('ticketable_id', $ticket->ticketable_id)
                ->where('ticketable_type', $ticket->ticketable_type)
                ->where('id', '!=', $ticket->id)
                ->get();
            foreach ($relatedTickets as $otherTicket) {
                if ($otherTicket->state->isOpen()) {
                    $this->openTickets[] = $otherTicket->id;
                } else {
                    $this->closedTickets[] = $otherTicket->id;
                }
            }
        }

        $this->unlocksSinceReported = 0;
        if ($isAchievement && $ticket->state->isOpen()) {
            $this->unlocksSinceReported = PlayerAchievement::where('achievement_id', $ticketable->id)
                ->where(function ($query) use ($ticket) {
                    $query->where('unlocked_at', '>', $ticket->created_at)
                        ->orWhere('unlocked_hardcore_at', '>', $ticket->created_at);
                })->count();
        }

        $this->ticketNotes = nl2br($ticket->body);
        $game = $ticketable->getTicketableGame();
        foreach ($game->hashes as $hash) {
            if (stripos($this->ticketNotes, $hash->md5) !== false) {
                $hashesRoute = route('game.hashes.index', ['game' => $game]);
                $escapedHashName = attributeEscape($hash->name);
                $replacement = "<a href='{$hashesRoute}' title='{$escapedHashName}'>{$hash->md5}</a>";

                $this->ticketNotes = str_ireplace($hash->md5, $replacement, $this->ticketNotes);
            }
        }
    }

    public function buildHistory(Ticket $ticket, User $actingUser): array
    {
        $canViewHistory = $actingUser->canany(['manage', 'viewHistory'], Ticket::class);
        if (!$canViewHistory || !$ticket->reporter) {
            return [];
        }

        $ticketable = $ticket->getTicketableModel();
        $this->activity->initialize($ticket->reporter, $ticketable->getTicketableGame());

        $this->activity->addCustomEvent($ticket->created_at, PlayerGameActivitySessionType::TicketCreated,
            "Ticket created - " . $ticket->type->label() . ": {$ticketable->getTicketableTitle()}");

        if ($ticket->ticketable_type === TicketableType::Achievement->value) {
            foreach ($this->activity->sessions as &$session) {
                foreach ($session['events'] as &$event) {
                    if ($event['type'] === PlayerGameActivityEventType::Unlock
                        && $event['id'] === $ticketable->id) {
                        $event['note'] = 'reported achievement';
                    }
                }
            }
        }

        return $this->activity->sessions;
    }

    public function getClientBreakdown(UserAgentService $userAgentService): array
    {
        return $this->activity->getClientBreakdown($userAgentService);
    }
}
