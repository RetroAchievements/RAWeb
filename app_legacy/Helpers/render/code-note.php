<?php

declare(strict_types=1);

function RenderCodeNotes(array $codeNotes): void
{
    echo "<table class='table-highlight'><tbody>";

    echo "<tr class='do-not-highlight'><th style='font-size:100%;'>Mem</th><th style='font-size:100%;'>Note</th><th style='font-size:100%;'>Author</th></tr>";

    foreach ($codeNotes as $nextCodeNote) {
        if (empty(trim($nextCodeNote['Note'])) || $nextCodeNote['Note'] == "''") {
            continue;
        }

        echo "<tr>";

        $addr = $nextCodeNote['Address'];
        $addrInt = hexdec($addr);

        $addrFormatted = sprintf("%04x", $addrInt);
        $memNote = $nextCodeNote['Note'];

        sanitize_outputs($memNote);

        $memNote = nl2br($memNote);

        echo "<td style='width: 25%;'>";
        echo "<span class='font-mono'>0x$addrFormatted</span>";
        echo "</td>";

        echo "<td>";
        echo "<div class='font-mono' style='word-break:break-word'>$memNote</div>";
        echo "</td>";

        echo "<td>";
        echo userAvatar($nextCodeNote['User'], label: false, iconSize: 24);
        echo "</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";
}
