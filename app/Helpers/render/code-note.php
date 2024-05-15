<?php

function RenderCodeNotes(array $codeNotes): void
{
    echo "<table class='table-highlight'>";

    echo "<thead>";
    echo "<tr class='do-not-highlight'>";
    echo "<th style='font-size:100%;'>Mem</th>";
    echo "<th style='font-size:100%;'>Note</th>";
    echo "<th style='font-size:100%;'>Author</th>";
    echo "</tr>";
    echo "</thead>";

    echo "<tbody>";

    $rowIndex = 0;
    foreach ($codeNotes as $nextCodeNote) {
        if (empty(trim($nextCodeNote['Note'])) || $nextCodeNote['Note'] == "''") {
            continue;
        }

        echo "<tr id='row-$rowIndex' class='note-row'>";

        $addr = $nextCodeNote['Address'];
        $addrInt = hexdec($addr);

        $addrFormatted = sprintf("%04x", $addrInt);
        $originalMemNote = $nextCodeNote['Note'];

        sanitize_outputs($originalMemNote);

        $memNote = nl2br($originalMemNote);

        echo "<td data-address='$addr' style='width: 25%;'>";
        echo "<span class='font-mono'>0x$addrFormatted</span>";
        echo "</td>";

        echo <<<HTML
            <td>
                <div class="font-mono block" style="word-break: break-word;">$memNote</div>
            </td>
        HTML;

        echo "<td class='note-author-avatar' data-current-author='" . $nextCodeNote['User'] . "'>";
        echo userAvatar($nextCodeNote['User'], label: false, iconSize: 24);
        echo "</td>";

        echo "</tr>";

        $rowIndex++;
    }

    echo "</tbody></table>";
}
