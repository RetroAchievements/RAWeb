<?php

use App\Community\ViewModels\Ticket;

RenderContentStart();

function tooltip_row(string $text): void
{
    $badge = '00000';
    echo "    <tr>\n";

    $alt = attributeEscape($text);
    echo "        <td><a href=\"/game/1234\"><img class=\"goldimage\" alt=\"$alt\" title=\"$alt\" src=\"/Badge/$badge.png\" width=\"48\" height=\"48\" /></a></td>\n";

    echo "        <td>";
    echo gameAvatar(1, icon: "/Badge/$badge.png");

    echo "</td>\n        <td>";
    echo achievementAvatar(1);

    echo "</td>\n        <td>";
    echo userAvatar('luchaos');

    $ticketData = [
        'ID' => 1,
        'AchievementID' => 2,
        'AchievementTitle' => $text,
        'AchievementDesc' => $text,
        'Points' => 5,
        'BadgeName' => $badge,
        'AchievementAuthor' => 'Author',
        'GameID' => 1,
        'ConsoleName' => 'Console',
        'GameTitle' => $text,
        'ReportedAt' => '',
        'ReportType' => 1,
        'ReportState' => 1,
        'ReportNotes' => '',
        'ReportedBy' => 'User',
        'ResolvedAt' => null,
        'ResolvedBy' => null,
    ];
    $ticket = new Ticket($ticketData);
    echo "</td>\n        <td>";
    echo ticketAvatar($ticket);

    echo "</td>\n";
    echo "    </tr>\n";
}
?>
<div style="width:1024px">
    <h1>Tooltip</h1>
    <table><th>alt</th><th>game</th><th>achievement</th><th>user</th><th>ticket</th>
<?php
    tooltip_row("Simple text");
    tooltip_row("Text\nwith\nmultiple\nlines");
    tooltip_row("What's a single quote?");
    tooltip_row("Double \"quoted\"");
    tooltip_row("Uñïçødé text � (テスト)");
    tooltip_row("12/7/2021 | 47½°");
    tooltip_row("🏆 Emoticon 😀");
    tooltip_row("<script>alert('Oops')</script>");
?>
    </table>
</div>
<?php RenderContentEnd(); ?>
