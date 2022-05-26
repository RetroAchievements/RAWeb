<?php

use RA\Models\TicketModel;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

RenderHtmlStart();
RenderSharedHeader();

function tooltip_row(string $text): void
{
    $badge = '00000';
    echo "    <tr>\n";

    $alt = attributeEscape($text);
    echo "        <td><a href=\"/game/1234\"><img class=\"goldimage\" alt=\"$alt\" title=\"$alt\" src=\"/Badge/$badge.png\" width=\"48\" height=\"48\" /></a></td>\n";

    echo "        <td>";
    echo GetGameAndTooltipDiv(1, $text, "/Badge/$badge.png", $text);

    echo "</td>\n        <td>";
    echo GetAchievementAndTooltipDiv(1, $text, $text, 5, $text, $badge, true);

    echo "</td>\n        <td>";
    echo GetLeaderboardAndTooltipDiv(1, $text, $text, $text, "/Badge/$badge.png", $text);

    echo "</td>\n        <td>";
    $userCardInfo = [
        'TotalPoints' => 12345,
        'TotalTruePoints' => 34567,
        'Permissions' => 1,
        'Motto' => htmlspecialchars($text), // ASSERT: getUserCardData does this escaping
        'Rank' => 56,
        'Untracked' => 0,
        'LastActivity' => '1/2/2022',
        'MemberSince' => '7/8/2018',
    ];
    echo _GetUserAndTooltipDiv($text, $userCardInfo);

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
    $ticket = new TicketModel($ticketData);
    echo "</td>\n        <td>";
    echo GetTicketAndTooltipDiv($ticket);

    echo "</td>\n";
    echo "    </tr>\n";
}

?>
<body>
<script src='/vendor/wz_tooltip.js'></script>
<div style="width:1024px">
    <h1>Tooltip</h1>
    <table><th>alt</th><th>game</th><th>achievement</th><th>leaderboard</th><th>user</th><th>ticket</th>
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
</body>
