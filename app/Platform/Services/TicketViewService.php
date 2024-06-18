<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Enums\PlayerGameActivityEventType;
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
        protected PlayerGameActivityService $activity = new PlayerGameActivityService()
    ) {

    }

    public function load(Ticket $ticket): void
    {
        if ($ticket->reporter) {
            $msgTitle = rawurlencode("Bug Report ({$ticket->achievement->game->Title})");
            $msgPayload = "Hi [user={$ticket->reporter->User}], I'm contacting you about [ticket={$ticket->ID}]";
            $msgPayload = rawurlencode($msgPayload);
            $this->contactReporterUrl = route('message.create') . "?to={$ticket->reporter->User}&subject=$msgTitle&message=$msgPayload";

            $this->existingUnlock = PlayerAchievement::where('user_id', $ticket->reporter->id)
                ->where('achievement_id', $ticket->achievement->id)
                ->first();

            $this->openTickets = [];
            $this->closedTickets = [];
            $achievementTickets = Ticket::where('AchievementID', $ticket->achievement->id);
            foreach ($achievementTickets->get() as $otherTicket) {
                if ($otherTicket->ID !== $ticket->ID) {
                    if (TicketState::isOpen($otherTicket->ReportState)) {
                        $this->openTickets[] = $otherTicket->id;
                    } else {
                        $this->closedTickets[] = $otherTicket->id;
                    }
                }
            }
        }

        $this->unlocksSinceReported = 0;
        if (TicketState::isOpen($ticket->ReportState)) {
            $this->unlocksSinceReported = PlayerAchievement::where('achievement_id', $ticket->achievement->id)
                ->where(function ($query) use ($ticket) {
                    $query->where('unlocked_at', '>', $ticket->ReportedAt)
                        ->orWhere('unlocked_hardcore_at', '>', $ticket->ReportedAt);
                })->count();
        }

        $this->ticketNotes = nl2br($ticket->ReportNotes);
        foreach ($ticket->achievement->game->hashes as $hash) {
            if (stripos($this->ticketNotes, $hash->md5) !== false) {
                $replacement = '<a href="/linkedhashes.php?g=' . $ticket->achievement->game->id . '" title="' .
                    attributeEscape($hash->name) . '">' . $hash->md5 . '</a>';
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

        $this->activity->addCustomEvent($ticket->ReportedAt,
            "Ticket created - " . TicketType::toString($ticket->ReportType) . ": {$ticket->achievement->title}");

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
