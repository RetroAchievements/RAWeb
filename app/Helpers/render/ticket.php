<?php

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Community\ViewModels\Ticket;

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
        $ticket = GetTicketModel($ticket);
    }

    if ($ticket === null) {
        return '';
    }

    $ticketStateClass = match ($ticket->ticketState) {
        TicketState::Open, TicketState::Request => 'open',
        TicketState::Closed, TicketState::Resolved => 'closed',
        default => '',
    };

    return avatar(
        resource: 'ticket',
        id: $ticket->id,
        label: "Ticket #{$ticket->id}",
        link: '/ticketmanager.php?i=' . $ticket->id,
        tooltip: is_array($tooltip) ? renderAchievementCard($tooltip) : $tooltip,
        class: "ticket-avatar $ticketStateClass",
        iconUrl: media_asset("/Badge/" . $ticket->badgeName . ".png"),
        iconSize: $iconSize,
        iconClass: $iconClass,
        context: $context,
    );
}

function renderTicketCard(int|Ticket $ticket): string
{
    if (is_int($ticket)) {
        $ticket = GetTicketModel($ticket);
    }

    if ($ticket === null) {
        return '';
    }

    $ticketStateClass = match ($ticket->ticketState) {
        TicketState::Open, TicketState::Request => 'open',
        TicketState::Closed, TicketState::Resolved => 'closed',
        default => '',
    };

    return "<div class='tooltip-body flex items-start' style='max-width: 400px'>" .
        "<img style='margin-right:5px' src='" . media_asset('/Badge/' . $ticket->badgeName . '.png') . "' width='64' height='64' />" .
        "<div class='ticket-tooltip-info $ticketStateClass'>" .
        "<div><b>" . $ticket->achievementTitle . "</b> <i>(" . $ticket->gameTitle . ")</i></div>" .
        "<div>Reported by $ticket->createdBy</div>" .
        "<div>Issue: " . TicketType::toString($ticket->ticketType) . "</div>" .
        "<div class='tooltip-closer'>Closed by $ticket->closedBy, " . getNiceDate(strtotime($ticket->closedOn)) . "</div>" .
        "<div class='tooltip-opened-date'> Opened " . getNiceDate(strtotime($ticket->createdOn)) . "</div>" .
        "</div>" .
        "<div class='ticket-tooltip-state'>" . TicketState::toString($ticket->ticketState) . "</div>" .
        "</div>";
}
