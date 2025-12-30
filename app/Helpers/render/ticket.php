<?php

use App\Community\Enums\TriggerTicketState;
use App\Models\TriggerTicket;

function ticketAvatar(
    int|string|TriggerTicket $ticket,
    ?bool $label = null,
    bool|int|string|null $icon = null,
    int $iconSize = 32,
    string $iconClass = 'badgeimg',
    bool|string|array $tooltip = true,
    ?string $context = null,
): string {
    if (is_int($ticket)) {
        $ticket = TriggerTicket::find($ticket);
    }

    if ($ticket === null) {
        return '';
    }

    /** @var TriggerTicket $safeTicket */
    $safeTicket = $ticket;

    $ticketStateClass = match ($safeTicket->state) {
        TriggerTicketState::Open, TriggerTicketState::Request => 'open',
        TriggerTicketState::Closed, TriggerTicketState::Resolved => 'closed',
        default => '',
    };

    return avatar(
        resource: 'ticket',
        id: $safeTicket->id,
        label: "Ticket #{$safeTicket->id}",
        link: route('ticket.show', ['triggerTicket' => $safeTicket->id]),
        tooltip: is_array($tooltip) ? renderAchievementCard($tooltip) : $tooltip,
        class: "ticket-avatar $ticketStateClass",
        iconUrl: media_asset("/Badge/" . $safeTicket->achievement->badgeName . ".png"),
        iconSize: $iconSize,
        iconClass: $iconClass,
        context: $context,
    );
}

function renderTicketCard(int|TriggerTicket $ticket): string
{
    if (is_int($ticket)) {
        $ticket = TriggerTicket::find($ticket);
    }

    if (!$ticket) {
        return '';
    }

    $ticketStateClass = match ($ticket->state) {
        TriggerTicketState::Open, TriggerTicketState::Request => 'open',
        TriggerTicketState::Closed, TriggerTicketState::Resolved => 'closed',
        default => '',
    };

    return "<div class='tooltip-body flex items-start' style='max-width: 400px'>" .
        "<img style='margin-right:5px' src='" . media_asset('/Badge/' . $ticket->achievement->badgeName . '.png') . "' width='64' height='64' />" .
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
