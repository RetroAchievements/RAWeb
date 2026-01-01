<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Enums\PlayerGameActivityEventType;
use App\Enums\PlayerGameActivitySessionType;
use App\Models\PlayerAchievement;
use App\Models\Ticket;
use App\Models\User;

class TicketViewService
{
    public int $unlocksSinceReported = 0;
    public array $openTickets = [];
    public array $closedTickets = [];
    public string $contactReporterUrl = '';
    public ?PlayerAchievement $existingUnlock = null;
    public string $ticketNotes = '';

    public function __construct(
        protected PlayerGameActivityService $activity = new PlayerGameActivityService(),
    ) {

    }

    public function load(Ticket $ticket): void
    {
        if ($ticket->reporter) {
            $msgTitle = rawurlencode("Bug Report ({$ticket->achievement->game->title})");
            $msgPayload = "Hi [user={$ticket->reporter->display_name}], I'm contacting you about [ticket={$ticket->id}]";
            $msgPayload = rawurlencode($msgPayload);
            $this->contactReporterUrl = route('message-thread.create') . "?to={$ticket->reporter->display_name}&subject=$msgTitle&message=$msgPayload";

            $this->existingUnlock = PlayerAchievement::where('user_id', $ticket->reporter->id)
                ->where('achievement_id', $ticket->achievement->id)
                ->first();

            $this->openTickets = [];
            $this->closedTickets = [];
            $achievementTickets = Ticket::where('ticketable_id', $ticket->achievement->id)
                ->where('ticketable_type', 'achievement');
            foreach ($achievementTickets->get() as $otherTicket) {
                if ($otherTicket->id !== $ticket->id) {
                    if ($otherTicket->state->isOpen()) {
                        $this->openTickets[] = $otherTicket->id;
                    } else {
                        $this->closedTickets[] = $otherTicket->id;
                    }
                }
            }
        }

        $this->unlocksSinceReported = 0;
        if ($ticket->state->isOpen()) {
            $this->unlocksSinceReported = PlayerAchievement::where('achievement_id', $ticket->achievement->id)
                ->where(function ($query) use ($ticket) {
                    $query->where('unlocked_at', '>', $ticket->created_at)
                        ->orWhere('unlocked_hardcore_at', '>', $ticket->created_at);
                })->count();
        }

        $this->ticketNotes = nl2br($ticket->body);
        foreach ($ticket->achievement->game->hashes as $hash) {
            if (stripos($this->ticketNotes, $hash->md5) !== false) {
                $hashesRoute = route('game.hashes.index', ['game' => $ticket->achievement->game]);
                $escapedHashName = attributeEscape($hash->name);
                $replacement = "<a href='{$hashesRoute}' title='{$escapedHashName}'>{$hash->md5}</a>";

                $this->ticketNotes = str_ireplace($hash->md5, $replacement, $this->ticketNotes);
            }
        }
    }

    public function buildHistory(Ticket $ticket, User $actingUser): array
    {
        $this->clients = [];

        $canManageTicket = $actingUser->can('manage', Ticket::class);
        if (!$canManageTicket || !$ticket->reporter) {
            return [];
        }

        $this->activity->initialize($ticket->reporter, $ticket->achievement->game);

        $this->activity->addCustomEvent($ticket->created_at, PlayerGameActivitySessionType::TicketCreated,
            "Ticket created - " . $ticket->type->label() . ": {$ticket->achievement->title}");

        foreach ($this->activity->sessions as &$session) {
            foreach ($session['events'] as &$event) {
                if ($event['type'] === PlayerGameActivityEventType::Unlock
                    && $event['id'] === $ticket->achievement->id) {
                    $event['note'] = 'reported achievement';
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
