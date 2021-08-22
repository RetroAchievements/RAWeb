<?php

use RA\Enums\TicketStates;
use RA\Enums\TicketTypes;
use RA\Models\TicketModel;

function GetTicketAndTooltipDiv(TicketModel $ticket): string
{
    $tooltipIconSize = 64;
    $ticketStateClass = '';

    switch ($ticket->TicketState) {
        case TicketStates::Open:
            $ticketStateClass = 'open';
            break;
        case TicketStates::Closed:
            $ticketStateClass = 'closed';
            break;
        case TicketStates::Resolved:
            $ticketStateClass = 'closed';
            break;
    }

    $tooltip =
    "<div id='objtooltip' style='display:flex;max-width:400px'>" .
        "<img style='margin-right:5px' src='" . getenv('ASSET_URL') . "/Badge/" . $ticket->BadgeName . ".png' width='$tooltipIconSize' height='$tooltipIconSize' />" .
        "<div class='ticket-tooltip-info $ticketStateClass'>" .
            "<div><b>" . $ticket->AchievementTitle . "</b> <i>(" . $ticket->GameTitle . ")</i></div>" .
            "<div>Reported by $ticket->CreatedBy</div>" .
            "<div>Issue: " . TicketTypes::RenderType($ticket->TicketType) . "</div>" .
            "<div class='tooltip-closer'>Closed by $ticket->ClosedBy, " . getNiceDate(strtotime($ticket->ClosedOn)) . "</div>" .
            "<div class='tooltip-opened-date'> Opened " . getNiceDate(strtotime($ticket->CreatedOn)) . "</div>" .
        "</div>" .
        "<div class='ticket-tooltip-state'>" . TicketStates::RenderState($ticket->TicketState) . "</div>" .
    "</div>";

    $tooltip = str_replace("'", "\'", $tooltip);

    sanitize_outputs($tooltip);

    $achNameAttr = htmlspecialchars($ticket->AchievementTitle, ENT_QUOTES);
    $smallBadgePath = "/Badge/" . $ticket->BadgeName . ".png";

    return "<a class='ticket-block bb_inline $ticketStateClass' href='/ticketmanager.php?i=" . $ticket->AchievementId
            . "' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\">" .
            "<img loading='lazy' width='32' height='32' src=\"" . getenv('ASSET_URL') . "$smallBadgePath\" alt='$achNameAttr' title='$achNameAttr' class='badgeimg' />" .
            "<div class='ticket-displayable-block'>Ticket #$ticket->TicketId</div>" .
        "</a>";
}
