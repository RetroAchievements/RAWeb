<?php

use RA\Ticket;
use RA\TicketState;
use RA\TicketType;

function GetTicketAndTooltipDiv(Ticket $ticket): string
{
    $ticketStateClass = match ($ticket->ticketState) {
        TicketState::Open => 'open',
        TicketState::Closed => 'closed',
        TicketState::Resolved => 'closed',
        default => '',
    };

    $achNameAttr = attributeEscape($ticket->achievementTitle);
    $smallBadgePath = "/Badge/" . $ticket->badgeName . ".png";

    return "<a class='ticket-block inline-block $ticketStateClass' href='/ticketmanager.php?i=" . $ticket->id
            . "' onmouseover=\"Tip(loadCard('ticket', {$ticket->id}))\" onmouseout=\"UnTip()\">" .
            "<img loading='lazy' width='32' height='32' src=\"" . media_asset($smallBadgePath) . "\" alt='$achNameAttr' title='$achNameAttr' class='badgeimg' />" .
            "<div class='ticket-displayable-block'>Ticket #$ticket->id</div>" .
        "</a>";
}

function renderTicketCard(int $ticketId): string
{
    $ticket = GetTicketModel($ticketId);

    $ticketStateClass = match ($ticket->ticketState) {
        TicketState::Open => 'open',
        TicketState::Closed => 'closed',
        TicketState::Resolved => 'closed',
        default => '',
    };

    $tooltip =
        "<div class='tooltip-body flex items-start' style='max-width: 400px'>" .
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

    return $tooltip;
}
