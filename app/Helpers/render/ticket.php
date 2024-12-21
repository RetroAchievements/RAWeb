<?php

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
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

    $ticketStateClass = match ($safeTicket->ReportState) {
        TicketState::Open, TicketState::Request => 'open',
        TicketState::Closed, TicketState::Resolved => 'closed',
        default => '',
    };

    return avatar(
        resource: 'ticket',
        id: $safeTicket->id,
        label: "Ticket #{$safeTicket->id}",
        link: route('ticket.show', ['ticket' => $safeTicket->id]),
        tooltip: is_array($tooltip) ? renderAchievementCard($tooltip) : $tooltip,
        class: "ticket-avatar $ticketStateClass",
        iconUrl: media_asset("/Badge/" . $safeTicket->achievement->badgeName . ".png"),
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

    $ticketStateClass = match ($ticket->ReportState) {
        TicketState::Open, TicketState::Request => 'open',
        TicketState::Closed, TicketState::Resolved => 'closed',
        default => '',
    };

    return "<div class='tooltip-body flex items-start' style='max-width: 400px'>" .
        "<img style='margin-right:5px' src='" . media_asset('/Badge/' . $ticket->achievement->badgeName . '.png') . "' width='64' height='64' />" .
        "<div class='ticket-tooltip-info $ticketStateClass'>" .
        "<div><b>" . $ticket->achievement->title . "</b> <i>(" . $ticket->achievement->game->title . ")</i></div>" .
        "<div>Reported by {$ticket->reporter->display_name}</div>" .
        "<div>Issue: " . TicketType::toString($ticket->ReportType) . "</div>" .
        ($ticket->resolver ? "<div class='tooltip-closer'>Closed by {$ticket->resolver->display_name}, " . getNiceDate(strtotime($ticket->ResolvedAt)) . "</div>" : "") .
        "<div class='tooltip-opened-date'> Opened " . getNiceDate(strtotime($ticket->ReportedAt)) . "</div>" .
        "</div>" .
        "<div class='ticket-tooltip-state'>" . TicketState::toString($ticket->ReportState) . "</div>" .
        "</div>";
}
