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

    if ($ticket === null) {
        return '';
    }

    /** @var Ticket $safeTicket */
    $safeTicket = $ticket;

    $ticketStateClass = match ($safeTicket->state) {
        TicketState::Open, TicketState::Request => 'open',
        TicketState::Closed, TicketState::Resolved => 'closed',
    };

    return avatar(
        resource: 'ticket',
        id: $safeTicket->id,
        label: "Ticket #{$safeTicket->id}",
        link: route('ticket.show', ['ticket' => $safeTicket->id]),
        tooltip: is_array($tooltip) ? renderAchievementCard($tooltip) : $tooltip,
        class: "ticket-avatar $ticketStateClass",
        iconUrl: media_asset("/Badge/" . $safeTicket->achievement->image_name . ".png"),
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

    if (!$ticket) {
        return '';
    }

    $ticketStateClass = match ($ticket->state) {
        TicketState::Open, TicketState::Request => 'open',
        TicketState::Closed, TicketState::Resolved => 'closed',
    };

    return "<div class='tooltip-body flex items-start' style='max-width: 400px'>" .
        "<img style='margin-right:5px' src='" . media_asset('/Badge/' . $ticket->achievement->image_name . '.png') . "' width='64' height='64' />" .
        "<div class='ticket-tooltip-info $ticketStateClass'>" .
        "<div><b>" . $ticket->achievement->title . "</b> <i>(" . $ticket->achievement->game->title . ")</i></div>" .
        "<div>Reported by {$ticket->reporter->display_name}</div>" .
        "<div>Issue: " . $ticket->type->label() . "</div>" .
        ($ticket->resolver ? "<div class='tooltip-closer'>Closed by {$ticket->resolver->display_name}, " . getNiceDate(strtotime($ticket->resolved_at)) . "</div>" : "") .
        "<div class='tooltip-opened-date'> Opened " . getNiceDate(strtotime($ticket->created_at)) . "</div>" .
        "</div>" .
        "<div class='ticket-tooltip-state'>" . $ticket->state->label() . "</div>" .
        "</div>";
}
