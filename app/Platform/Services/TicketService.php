<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\ArticleType;
use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Enums\Permissions;
use App\Enums\PlayerGameActivityEventType;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Services\PlayerGameActivityService;
use App\Platform\Services\TriggerDecoderService;
use App\Platform\Services\UserAgentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TicketService
{
    public int $unlocksSinceReported = 0;
    public array $openTicketLinks = [];
    public array $closedTicketLinks = [];
    public array $userAgentLinks = [];
    public array $history = [];
    public string $contactReporterUrl = '';
    public ?PlayerAchievement $existingUnlock = null;
    public string $ticketNotes = '';

    public function load(Ticket $ticket): void
    {
        $msgTitle = rawurlencode("Bug Report ({$ticket->achievement->game->Title})");
        if ($ticket->reporter) {
            $msgPayload = "Hi [user={$ticket->reporter->User}], I'm contacting you about [ticket={$ticket->ID}]";
            $msgPayload = rawurlencode($msgPayload);
            $this->contactReporterUrl = route('message.create') . "?to={$ticket->reporter->User}&subject=$msgTitle&message=$msgPayload";

            $this->existingUnlock = PlayerAchievement::where('user_id', $ticket->reporter->id)
                ->where('achievement_id', $ticket->achievement->id)
                ->first();

            $this->openTicketLinks = [];
            $this->closedTicketLinks = [];
            $achievementTickets = Ticket::where('AchievementID', $ticket->achievement->id);
            foreach ($achievementTickets->get() as $otherTicket) {
                if ($otherTicket->ID !== $ticket->ID) {
                    $url = '<a href="' . route('ticket.show', ['ticket' => $otherTicket]) . '">' . $otherTicket->ID . '</a>';
                    if (TicketState::isOpen($otherTicket->ReportState)) {
                        $this->openTicketLinks[] = $url;
                    } else {
                        $this->closedTicketLinks[] = $url;
                    }
                }
            }
        }

        $this->unlocksSinceReported = 0;
        if (TicketState::isOpen($ticket->ReportState)) {
            $this->unlocksSinceReported = PlayerAchievement::where('achievement_id', $ticket->achievement->id)
                ->where(function($query) use ($ticket) {
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

    public function buildHistory(Ticket $ticket, User $user): void
    {
        $this->history = [];
        $this->userAgentLinks = [];

        $canManageTicket = $user->can('manage', Ticket::class);
        if (!$canManageTicket || !$ticket->reporter) {
            return;
        }

        $userAgentService = new UserAgentService();
        $activity = new PlayerGameActivityService();
        $activity->initialize($ticket->reporter, $ticket->achievement->game);

        $userAgents = [];
        foreach ($activity->sessions as $session) {
            if ($session['userAgent'] ?? null) {
                if (!in_array($session['userAgent'], $userAgents)) {
                    $userAgents[] = $session['userAgent'];
                }
            }
            foreach ($session['events'] as $event) {
                if ($event['type'] === PlayerGameActivityEventType::Unlock) {
                    $this->history[] = $event;
                }
            }
        }

        $this->history[] = [
            'type' => 'ticket',
            'when' => $ticket->ReportedAt,    
        ];

        usort($this->history, function ($a, $b) {
            $diff = $b['when']->timestamp - $a['when']->timestamp;
            if ($diff === 0) {
                // two events at same time should be sub-sorted by ID
                $diff = ($a['ID'] ?? 0) - ($b['ID'] ?? 0);
            }

            return $diff;
        });

        $clients = [];
        foreach ($userAgents as $userAgent) {
            $decoded = $userAgentService->decode($userAgent);
            $client = $decoded['client'];
            if ($decoded['clientVersion'] !== 'Unknown') {
                $client .= ' (' . $decoded['clientVersion'] . ')';
            }
            if (array_key_exists('clientVariation', $decoded)) {
                $client .= ' - ' . $decoded['clientVariation'];
            }
            $clients[$client][] = $userAgent;
        }

        foreach ($clients as $client => $agents) {
            $this->userAgentLinks[] = "<span title=\"" . implode("\n", $agents) . "\">$client</span>";
        }
    }
}
