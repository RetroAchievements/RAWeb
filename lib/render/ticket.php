<?php

use RA\Enums\TicketStates;
use RA\Enums\TicketTypes;
use RA\Models\TicketModel;

function GetTicketAndTooltipDiv(TicketModel $ticket): string
{
    $tooltipIconSize = 64;
    $ticketStateClass = '';

    switch ($ticket->ticketState) {
        case TicketStates::OPEN:
            $ticketStateClass = 'open';
            break;
        case TicketStates::CLOSED:
            $ticketStateClass = 'closed';
            break;
        case TicketStates::RESOLVED:
            $ticketStateClass = 'closed';
            break;
    }

    $tooltip =
    "<div id='objtooltip' style='display:flex;max-width:400px'>" .
        "<img style='margin-right:5px' src='" . getenv('ASSET_URL') . "/Badge/" . $ticket->badgeName . ".png' width='$tooltipIconSize' height='$tooltipIconSize' />" .
        "<div class='ticket-tooltip-info $ticketStateClass'>" .
            "<div><b>" . $ticket->achievementTitle . "</b> <i>(" . $ticket->gameTitle . ")</i></div>" .
            "<div>Reported by $ticket->createdBy</div>" .
            "<div>Issue: " . TicketTypes::renderType($ticket->ticketType) . "</div>" .
            "<div class='tooltip-closer'>Closed by $ticket->closedBy, " . getNiceDate(strtotime($ticket->closedOn)) . "</div>" .
            "<div class='tooltip-opened-date'> Opened " . getNiceDate(strtotime($ticket->createdOn)) . "</div>" .
        "</div>" .
        "<div class='ticket-tooltip-state'>" . TicketStates::renderState($ticket->ticketState) . "</div>" .
    "</div>";

    $tooltip = str_replace("'", "\'", $tooltip);

    sanitize_outputs($tooltip);

    $achNameAttr = htmlspecialchars($ticket->achievementTitle, ENT_QUOTES);
    $smallBadgePath = "/Badge/" . $ticket->badgeName . ".png";

    return "<a class='ticket-block bb_inline $ticketStateClass' href='/ticketmanager.php?i=" . $ticket->ticketId
            . "' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\">" .
            "<img loading='lazy' width='32' height='32' src=\"" . getenv('ASSET_URL') . "$smallBadgePath\" alt='$achNameAttr' title='$achNameAttr' class='badgeimg' />" .
            "<div class='ticket-displayable-block'>Ticket #$ticket->ticketId</div>" .
        "</a>";
}
