<?php

function RenderCodeNotes($codeNotes)
{
    echo "<h3>Code Notes</h3>";
    echo "<table class='smalltable xsmall'><tbody>";

    echo "<tr><th style='font-size:100%;'>Mem</th><th style='font-size:100%;'>Note</th><th style='font-size:100%;'>Author</th></tr>";

    foreach ($codeNotes as $nextCodeNote) {
        if (empty(trim($nextCodeNote['Note']))) {
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
        echo "<code>0x$addrFormatted</code>";
        echo "</td>";

        echo "<td>";
        echo "<div style='word-break:break-word;'>$memNote</div>";
        echo "</td>";

        echo "<td>";
        echo GetUserAndTooltipDiv($nextCodeNote['User'], true);
        echo "</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";
}
