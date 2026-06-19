<?php

use App\Community\Enums\TicketState;
use App\Models\Ticket;

function ticketAvatar(
    int|string|Ticket $ticket,
    ?bool $label = null,
    bool|int|string|null $icon = null,
    int $iconSize = 32,
    string $iconClass = 'badgeimg',
    bool|string|array $tooltip = true,
    ?string $context = null,
): string {
    if (is_int($ticket)) {
        $ticket = Ticket::find($ticket);
    }

    if ($ticket === null || $ticket->ticketable === null) {
        return '';
    }

    /** @var Ticket $safeTicket */
    $safeTicket = $ticket;

    $ticketStateClass = match ($safeTicket->state) {
        TicketState::Open, TicketState::Request => 'open',
        TicketState::Closed, TicketState::Resolved, TicketState::Quarantined => 'closed',
    };

    $badgeUrl = $safeTicket->getTicketableModel()->getTicketableIconUrl();

    return avatar(
        resource: 'ticket',
        id: $safeTicket->id,
        label: "Ticket #{$safeTicket->id}",
        link: route('ticket.show', ['ticket' => $safeTicket->id]),
        tooltip: is_array($tooltip) ? renderAchievementCard($tooltip) : $tooltip,
        class: "ticket-avatar $ticketStateClass",
        iconUrl: $badgeUrl,
        iconSize: $iconSize,
        iconClass: $iconClass,
        context: $context,
    );
}

function renderTicketCard(int|Ticket $ticket): string
{
    if (is_int($ticket)) {
        $ticket = Ticket::find($ticket);
    }

    if (!$ticket || !$ticket->ticketable) {
        return '';
    }

    $ticketStateClass = match ($ticket->state) {
        TicketState::Open, TicketState::Request => 'open',
        TicketState::Closed, TicketState::Resolved, TicketState::Quarantined => 'closed',
    };

    $ticketable = $ticket->getTicketableModel();
    $game = $ticketable->getTicketableGame();
    $badgeUrl = $ticketable->getTicketableIconUrl();

    return "<div class='tooltip-body flex items-start' style='max-width: 400px'>" .
        "<img style='margin-right:5px' src='" . $badgeUrl . "' width='64' height='64' />" .
        "<div class='ticket-tooltip-info $ticketStateClass'>" .
        "<div><b>" . $ticketable->getTicketableTitle() . "</b> <i>(" . $game->title . ")</i></div>" .
        "<div>Reported by {$ticket->reporter->display_name}</div>" .
        "<div>Issue: " . $ticket->type->label() . "</div>" .
        ($ticket->resolver ? "<div class='tooltip-closer'>Closed by {$ticket->resolver->display_name}, " . getNiceDate(strtotime($ticket->resolved_at)) . "</div>" : "") .
        "<div class='tooltip-opened-date'> Opened " . getNiceDate(strtotime($ticket->created_at)) . "</div>" .
        "</div>" .
        "<div class='ticket-tooltip-state'>" . $ticket->state->label() . "</div>" .
        "</div>";
}
