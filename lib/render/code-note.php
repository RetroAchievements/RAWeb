<?php

function RenderCodeNotes($codeNotes, $showDisclaimer = false): void
{
    
	echo "<h3 class='longheader'>Code Notes</h3>";

    if ($showDisclaimer) {
        echo "The RetroAchievements addressing scheme for most systems is to access the system memory " .
             "at address $00000000, immediately followed by the cartridge memory. As such, the addresses " .
             "displayed below may not directly correspond to the addresses on the real hardware.";
        echo "<br/><br/>";
    }

    echo "<table class='smalltable xsmall'><tbody>";

    echo "<tr><th style='font-size:100%;'>Mem</th><th style='font-size:100%;'>Note</th><th style='font-size:100%;'>Author</th></tr>";

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
