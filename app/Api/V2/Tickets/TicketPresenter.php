<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Platform\Enums\TicketableType;

/**
 * Resolves polymorphic inline-context fields for a ticket by dispatching on
 * `ticketable_type` so callers don't branch.
 */
final class TicketPresenter
{
    private function __construct(private readonly Ticket $ticket)
    {
    }

    public static function for(Ticket $ticket): self
    {
        return new self($ticket);
    }

    public function ticketableTitle(): ?string
    {
        return match ($this->ticket->ticketable_type) {
            TicketableType::Achievement->value => $this->achievement()?->title,
            TicketableType::Leaderboard->value => $this->leaderboard()?->title,
            default => null,
        };
    }

    public function gameId(): ?int
    {
        return $this->ticketableGame()?->id;
    }

    public function gameTitle(): ?string
    {
        return $this->ticketableGame()?->title;
    }

    public function gameIconUrl(): ?string
    {
        return $this->ticketableGame()?->badge_url;
    }

    public function systemName(): ?string
    {
        return $this->ticketableGame()?->system?->name;
    }

    public function reporterDisplayName(): ?string
    {
        return $this->ticket->reporter?->display_name;
    }

    public function resolverDisplayName(): ?string
    {
        return $this->ticket->resolver?->display_name;
    }

    public function authorDisplayName(): ?string
    {
        return $this->ticket->author?->display_name;
    }

    private function achievement(): ?Achievement
    {
        // the ach/lb relations on Ticket are loose, so we do narrowing here
        return $this->ticket->achievement instanceof Achievement ? $this->ticket->achievement : null;
    }

    private function leaderboard(): ?Leaderboard
    {
        // the ach/lb relations on Ticket are loose, so we do narrowing here
        return $this->ticket->leaderboard instanceof Leaderboard ? $this->ticket->leaderboard : null;
    }

    private function ticketableGame(): ?Game
    {
        return match ($this->ticket->ticketable_type) {
            TicketableType::Achievement->value => $this->achievement()?->game,
            TicketableType::Leaderboard->value => $this->leaderboard()?->game,
            default => null,
        };
    }
}
