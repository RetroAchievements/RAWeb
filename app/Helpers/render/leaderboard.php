<?php

function ExplainLeaderboardTrigger(string $name, string $triggerDef, array $codeNotes): void
{
    echo "<div class='devbox'>";
    echo "<span onclick=\"$('#devbox{$name}content').toggle(); return false;\">$name ▼</span>";
    echo "<div id='devbox{$name}content' style='display: none'>";

    echo "<div>";

    echo "<li>Mem:</li>";
    echo "<code>" . htmlspecialchars($triggerDef) . "</code>";

    if ($name === 'Value') {
        $triggerDef = ValueToTrigger($triggerDef);
    }

    echo "<li>Mem explained:</li>";
    echo "<code>" . getAchievementPatchReadableHTML($triggerDef, $codeNotes) . "</code>";
    echo "</div>";

    echo "</div>"; // devboxcontent
    echo "</div>"; // devbox
}
