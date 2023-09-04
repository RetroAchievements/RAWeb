<?php

use App\Platform\Models\System;
use App\Site\Enums\Permissions;

$consoleList = System::get(['ID', 'Name'])->keyBy('ID')->map(fn ($system) => $system['Name']);

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$offset = requestInputSanitized('o', 0, 'integer');

$gameID = requestInputSanitized('g', null, 'integer');

// If a game is picked, sort the LBs by DisplayOrder
$sortBy = requestInputSanitized('s', empty($gameID) ? 3 : 0, 'integer');

if (empty($gameID)) {
    abort(404);
}

$pageTitle = "Leaderboards - ";

$gameData = getGameData($gameID);
$codeNotes = [];
if ($permissions >= Permissions::JuniorDeveloper) {
    getCodeNotes($gameID, $codeNotes);
}
$pageTitle .= $gameData['Title'];

$lbData = getLeaderboardsList($gameID, $sortBy);

if (empty($lbData)) {
    return redirect(route('game.show', $gameID));
}

sanitize_outputs(
    $requestedConsole,
    $gameData['Title'],
);

RenderContentStart($pageTitle);
?>
<?php if ($permissions >= Permissions::JuniorDeveloper): ?>
    <script>
    function UpdateLeaderboard(lbID) {
        showStatusMessage('Updating...');
        $.post('/request/leaderboard/update.php', {
            leaderboard: lbID,
            title: $.trim($('#LB_' + lbID + '_Title').val()),
            description: $.trim($('#LB_' + lbID + '_Desc').val()),
            format: $.trim($('#LB_' + lbID + '_Format').val()),
            trigger: 'STA:' + $.trim($('#LB_' + lbID + '_Mem1').val())
                + '::CAN:' + $.trim($('#LB_' + lbID + '_Mem2').val())
                + '::SUB:' + $.trim($('#LB_' + lbID + '_Mem3').val())
                + '::VAL:' + $.trim($('#LB_' + lbID + '_Mem4').val()),
            lowerIsBetter: $('#LB_' + lbID + '_LowerIsBetter').is(':checked') ? '1' : '0',
            order:  $.trim($('#LB_' + lbID + '_DisplayOrder').val())
        });
    }
    </script>
<?php endif ?>
<article>
<?php
echo "<div>";
echo "<div class='navpath'>";
if ($gameID != 0) {
    echo renderGameBreadcrumb($gameData);
    echo " &raquo; <b>Leaderboards</b>";
} else {
    echo "<b>Leaderboards</b>";    // NB. This will be a stub page
}
echo "</div>";

echo "<div class='detaillist'>";
echo "<h3>Leaderboards</h3>";

if (isset($gameData['ID'])) {
    echo "<div>";
    echo gameAvatar($gameData, iconSize: 64);
    echo "</div>";
}

if ($permissions >= Permissions::JuniorDeveloper) {
    $numGames = getGamesList(0, $gamesList);

    echo "<div class='devbox'>";
    echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Dev ▼</span>";
    echo "<div id='devboxcontent' style='display: none'>";

    echo "<ul>";
    echo "<li>";
    echo "<form method='post' action='/request/leaderboard/create.php'>";
    echo csrf_field();
    echo "<input type='hidden' name='game' value='$gameID' />";
    echo "<button class='btn'>Create Leaderboard</button>";
    echo "</form>";
    echo "<form method='post' action='/request/leaderboard/create.php'>";
    echo csrf_field();
    echo "<input type='hidden' name='game' value='$gameID'>";
    echo "Duplicate leaderboard ID: ";
    echo "<input style='width: 10%;' min='1' name='leaderboard'> ";
    echo "Amount: ";
    echo "<input style='width: 10%;' type='number' min='1' max='25' value='1' name='amount'>";
    echo "&nbsp;&nbsp;";
    echo "<button class='btn'>Duplicate</button>";
    echo "</form>";
    echo "</li>";
    echo "</ul>";

    echo "</div>";
    echo "</div>";
}

echo "<table><tbody>";

$sort1 = ($sortBy == 1) ? 11 : 1;
$sort2 = ($sortBy == 2) ? 12 : 2;
$sort3 = ($sortBy == 3) ? 13 : 3;
$sort4 = ($sortBy == 4) ? 14 : 4;
$sort5 = ($sortBy == 5) ? 15 : 5;
$sort6 = ($sortBy == 6) ? 16 : 6;
$sort7 = ($sortBy == 7) ? 17 : 7;

if ($permissions >= Permissions::JuniorDeveloper) {
    echo "<th>ID</th>";
    echo "<th>Title/Description</th>";
    echo "<th>Type</th>";
    echo "<th>Lower Is Better</th>";
    echo "<th>Display Order</th>";
} else {
    echo "<th><a href='/leaderboardList.php?g=$gameID&s=$sort1'>ID</a></th>";
    echo "<th></th>";
    echo "<th><a href='/leaderboardList.php?g=$gameID&s=$sort2'>Game</a></th>";
    // echo "<th><a href='/leaderboardList.php?g=$gameID&s=$sort3'>Console</a></th>";
    echo "<th><a href='/leaderboardList.php?g=$gameID&s=$sort4'>Title</a></th>";
    echo "<th><a href='/leaderboardList.php?g=$gameID&s=$sort5'>Description</a></th>";
    echo "<th><a href='/leaderboardList.php?g=$gameID&s=$sort6'>Type</a></th>";
    echo "<th><a href='/leaderboardList.php?g=$gameID&s=$sort7'>Entries</a></th>";
}

$listCount = 0;

foreach ($lbData as $nextLB) {
    $lbID = $nextLB['ID'];
    $lbTitle = attributeEscape($nextLB['Title']);
    $lbDesc = attributeEscape($nextLB['Description']);
    $lbMem = $nextLB['Mem'];
    $lbFormat = $nextLB['Format'];
    $lbLowerIsBetter = $nextLB['LowerIsBetter'];
    $lbNumEntries = $nextLB['NumResults'];
    $lbNumEntries = (int) $lbNumEntries;
    $lbDisplayOrder = $nextLB['DisplayOrder'];
    $lbAuthor = $nextLB['Author'];
    $gameID = $nextLB['GameID'];
    $gameTitle = $nextLB['GameTitle'];
    $gameIcon = $nextLB['GameIcon'];
    $consoleName = $nextLB['ConsoleName'];

    $niceFormat = ($lbLowerIsBetter ? "Smallest " : "Largest ") . (($lbFormat == "SCORE") ? "Score" : "Time");

    echo "<tr>";

    if ($permissions >= Permissions::JuniorDeveloper) {
        // Allow leaderboard edits for devs and jr. devs if they are the author
        if ($permissions >= Permissions::Developer || ($lbAuthor == $user && $permissions === Permissions::JuniorDeveloper)) {
            $editAllowed = true;
        } else {
            $editAllowed = false;
        }

        echo "<td>";
        echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbID</a>";
        echo "</td>";

        // echo "<td>";
        // echo gameAvatar($gameData);
        // echo "</td>";

        // echo "<td>";
        // echo "$consoleName";
        // echo "</td>";

        echo "<td>";
        echo "<input style='width: 60%;' type='text' value='$lbTitle' id='LB_" . $lbID . "_Title' " . ($editAllowed ? "" : "readonly") . "/><br>";
        echo "<input style='width: 100%;' type='text' value='$lbDesc' id='LB_" . $lbID . "_Desc' " . ($editAllowed ? "" : "readonly") . "/>";
        echo "</td>";

        echo "<td style='width: 20%;'>";
        echo "<select id='LB_" . $lbID . "_Format' name='i' " . ($editAllowed ? "" : "disabled='true'") . ">";
        $selected = $lbFormat == "SCORE" ? "selected" : "";
        echo "<option value='SCORE' $selected>Score</option>";
        $selected = $lbFormat == "TIME" ? "selected" : "";
        echo "<option value='TIME' $selected >Time (Frames)</option>";
        $selected = $lbFormat == "MILLISECS" ? "selected" : "";
        echo "<option value='MILLISECS' $selected >Time (Centiseconds)</option>";
        $selected = $lbFormat == "TIMESECS" ? "selected" : "";
        echo "<option value='TIMESECS' $selected >Time (Seconds)</option>";
        $selected = $lbFormat == "MINUTES" ? "selected" : "";
        echo "<option value='MINUTES' $selected >Time (Minutes)</option>";
        $selected = $lbFormat == "VALUE" ? "selected" : "";
        echo "<option value='VALUE' $selected>Value</option>";
        echo "</select>";

        // echo "<input type='text' value='$lbFormat' id='LB_" . $lbID . "_Format' />";
        echo "</td>";

        echo "<td style='width: 10%;'>";
        $checked = ($lbLowerIsBetter ? "checked" : "");
        echo "<input type='checkbox' $checked id='LB_" . $lbID . "_LowerIsBetter' " . ($editAllowed ? "" : "onclick='return false'") . "/>";
        echo "</td>";

        echo "<td style='width: 10%;'>";
        echo "<input size='3' type='text' value='$lbDisplayOrder' id='LB_" . $lbID . "_DisplayOrder' " . ($editAllowed ? "" : "readonly") . "/>";
        echo "</td>";

        echo "</tr>";

        echo "<tr>";

        echo "<td>";
        // echo "Memory:";
        echo "</td>";
        echo "<td colspan='4'>";
        $memStart = "";
        $memCancel = "";
        $memSubmit = "";
        $memValue = "";
        $memChunks = explode("::", $lbMem);
        foreach ($memChunks as &$memChunk) {
            $part = substr($memChunk, 0, 4);
            if ($part == 'STA:') {
                $memStart = substr($memChunk, 4);
            } elseif ($part == 'CAN:') {
                $memCancel = substr($memChunk, 4);
            } elseif ($part == 'SUB:') {
                $memSubmit = substr($memChunk, 4);
            } elseif ($part == 'VAL:') {
                $memValue = substr($memChunk, 4);
            }
        }

        echo "<table class='table-highlight mb-3'><tbody>";
        echo "<tr>";
        echo "<td style='width:10%;'>Start:</td>";
        echo "<td>";
        echo "<input type='text' id='LB_" . $lbID . "_Mem1' value='$memStart' style='width: 100%;' " . ($editAllowed ? "" : "readonly") . "/>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='width:10%;'>Cancel:</td>";
        echo "<td>";
        echo "<input type='text' id='LB_" . $lbID . "_Mem2' value='$memCancel' style='width: 100%;' " . ($editAllowed ? "" : "readonly") . "/>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='width:10%;'>Submit:</td>";
        echo "<td>";
        echo "<input type='text' id='LB_" . $lbID . "_Mem3' value='$memSubmit' style='width: 100%;' " . ($editAllowed ? "" : "readonly") . "/>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='width:10%;'>Value:</td>";
        echo "<td>";
        echo "<input type='text' id='LB_" . $lbID . "_Mem4' value='$memValue' style='width: 100%;' " . ($editAllowed ? "" : "readonly") . "/>";
        echo "</td>";
        echo "</tr>";

        echo "</tbody></table>";

        // Only display the entry count for jr. devs
        echo "<div class='flex justify-between items-center'>";
        echo "<a href='/leaderboardinfo.php?i=$lbID'>" . $lbNumEntries . " entries</a>";
        echo "<div class='flex gap-2'>";
        if ($permissions >= Permissions::Developer) {
            if ($lbNumEntries > 0) {
                echo "<form action='/request/leaderboard/reset.php' method='post' onsubmit='return confirm(\"Are you sure you want to permanently delete all entries of this leaderboard?\")'>";
                echo csrf_field();
                echo "<input type='hidden' name='leaderboard' value='$lbID'>";
                echo "<button class='btn btn-danger'>Reset entries</button>";
                echo "</form>";
            }
            echo "<form action='/request/leaderboard/delete.php' method='post' onsubmit='return confirm(\"Are you sure you want to permanently delete this leaderboard?\")'>";
            echo csrf_field();
            echo "<input type='hidden' name='leaderboard' value='$lbID'>";
            echo "<button class='btn btn-danger'>Delete leaderboard</button>";
            echo "</form>";
        }
        echo "</div>";
        if ($editAllowed) {
            echo "<button type='button' class='btn' onclick=\"UpdateLeaderboard('$lbID')\">Update</button>";
        }
        echo "</div>";

        echo "</td>";
        echo "</td>";
    } else {
        echo "<td>";
        echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbID</a>";
        echo "</td>";

        echo "<td>";
        echo gameAvatar($nextLB, label: false);
        echo "</td>";

        echo "<td>";
        echo gameAvatar($nextLB, icon: false);
        echo "</td>";

        // echo "<td class='whitespace-nowrap'>";
        // echo "$consoleName";
        // echo "</td>";

        echo "<td>";
        echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbTitle</a>";
        echo "</td>";

        echo "<td>";
        echo "$lbDesc";
        echo "</td>";

        echo "<td class='whitespace-nowrap'>";
        echo "$niceFormat";
        echo "</td>";

        echo "<td>";
        echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbNumEntries</a>";
        echo "</td>";
    }

    echo "</tr>";
}

echo "</tbody></table>";
echo "</div>";
?>
</article>
<?php
if (!empty($codeNotes) && $permissions >= Permissions::JuniorDeveloper) {
    view()->share('sidebar', true);
    echo "<aside>";
    echo "<h3>Code Notes</h3>";
    RenderCodeNotes($codeNotes);
    echo "</aside>";
}
?>
<?php RenderContentEnd(); ?>
