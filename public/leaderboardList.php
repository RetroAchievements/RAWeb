<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

use RA\Permissions;

$consoleList = getConsoleList();
$consoleIDInput = requestInputSanitized('c', 0, 'integer');

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

getCookie($user, $cookie);

$permissions = 0;
if (isset($user)) {
    $permissions = getUserPermissions($user);
}

$maxCount = 25;

$count = 25;
$offset = requestInputSanitized('o', 0, 'integer');

$gameID = requestInputSanitized('g', null, 'integer');

// If a game is picked, sort the LBs by DisplayOrder
$sortBy = requestInputSanitized('s', empty($gameID) ? 3 : 0, 'integer');

$lbCount = getLeaderboardsList($consoleIDInput, $gameID, $sortBy, $count, $offset, $lbData);

$gameData = null;
$codeNotes = [];
if ($gameID != 0) {
    $gameData = getGameData($gameID);
    getCodeNotes($gameID, $codeNotes);
}

//var_dump( $lbData );
//var_dump( $gameData );

$requestedConsole = "";
if ($consoleIDInput) {
    $requestedConsole = " " . $consoleList[$consoleIDInput];
}

if (empty($consoleIDInput) && empty($gameID)) {
    header("Location: " . getenv('APP_URL'));
    return;
}

sanitize_outputs(
    $requestedConsole,
    $gameData['Title'],
);

$pageTitle = "Leaderboard List" . $requestedConsole;

$errorCode = requestInputSanitized('e');
RenderHtmlStart();
RenderHtmlHead($pageTitle);
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<script>
  function ReloadLBPageByConsole() {
    var ID = $('#consoleselector').val();
    location.href = '/leaderboardList.php?c=' + ID.replace('c_', '');
  }

  function ReloadLBPageByGame() {
    var ID = $('#gameselector').val();
    if (ID.indexOf('c_') === 0) {
      location.href = '/leaderboardList.php?c=' + ID.replace('c_', '');
      return;
    }
    location.href = '/leaderboardList.php?g=' + ID;
  }
</script>
<?php if ($permissions >= \RA\Permissions::Developer): ?>
    <script>
      function UpdateLeaderboard(user, lbID) {
        var lbTitle = $.trim($('#LB_' + lbID + '_Title').val());
        var lbDesc = $.trim($('#LB_' + lbID + '_Desc').val());
        var lbFormat = $.trim($('#LB_' + lbID + '_Format').val());
        var lbDisplayOrder = $.trim($('#LB_' + lbID + '_DisplayOrder').val());
        var lbMem1 = $.trim($('#LB_' + lbID + '_Mem1').val());
        var lbMem2 = $.trim($('#LB_' + lbID + '_Mem2').val());
        var lbMem3 = $.trim($('#LB_' + lbID + '_Mem3').val());
        var lbMem4 = $.trim($('#LB_' + lbID + '_Mem4').val());

        var lbMem = 'STA:' + lbMem1 + '::CAN:' + lbMem2 + '::SUB:' + lbMem3 + '::VAL:' + lbMem4;
        var lbLowerIsBetter = $('#LB_' + lbID + '_LowerIsBetter').is(':checked') ? '1' : '0';

        var posting = $.post('/request/leaderboard/update.php', { u: user, i: lbID, t: lbTitle, d: lbDesc, f: lbFormat, m: lbMem, l: lbLowerIsBetter, o: lbDisplayOrder });
        posting.done(onUpdateComplete);

        $('#warning').html('Status: updating...');
      }

      function onUpdateComplete(data) {
        //alert( data );
        if (data !== 'OK') {
          $('#warning').html('Status: Errors...' + data);
          //alert( data );
        } else {
          $('#warning').html('Status: OK!');
        }
      }

    </script>
<?php endif ?>
<div id="mainpage">
    <?php
    if (count($codeNotes) > 0) {
        echo "<div id='leftcontainer'>";
    } else {
        echo "<div id='fullcontainer'>";
    }
    echo "<div>";
    echo "<div class='navpath'>";
    if ($gameID != 0) {
        echo "<a href='/leaderboardList.php'>Leaderboard List</a>";
        echo " &raquo; <b>" . $gameData['Title'] . "</b>";
    } else {
        echo "<b>Leaderboard List</b>";    //	NB. This will be a stub page
    }
    echo "</div>";

    echo "<div class='detaillist'>";
    echo "<h3 class='longheader'>Leaderboard List</h3>";

    if (isset($gameData['ID'])) {
        echo "<div>";
        echo "Displaying leaderboards for: ";
        echo GetGameAndTooltipDiv($gameData['ID'], $gameData['Title'], $gameData['ImageIcon'], $gameData['ConsoleName']);
        echo "</div>";

        echo "<div>";
        echo "<a href='/leaderboardList.php'>Return to full list</a>";
        echo "</div>";
    }

    if (isset($user) && $permissions >= Permissions::Developer) {
        $numGames = getGamesList(0, $gamesList);

        echo "<div class='devbox'>";
        echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Dev (Click to show):</span><br>";
        echo "<div id='devboxcontent'>";

        echo "<ul>";
        if (isset($gameID)) {
            echo "<li>";
            echo "<a href='/request/leaderboard/create.php?u=$user&c=$cookie&g=$gameID'>Add New Leaderboard to " . $gameData['Title'] . "</a></br>";
            echo "<form method='post' action='/request/leaderboard/create.php'> ";
            echo "<input type='hidden' name='u' value='$user' />";
            echo "<input type='hidden' name='c' value='$cookie' />";
            echo "<input type='hidden' name='g' value='$gameID' />";
            echo "Duplicate leaderboard ID: ";
            echo "<input style='width: 10%;' type='number' min=1 value=1 name='l' /> ";
            echo "Number of times: ";
            echo "<input style='width: 10%;' type='number' min=1 max=25 value=1 name='n' />";
            echo "&nbsp;&nbsp;";
            echo "<input type='submit' value='Duplicate'/>";
            echo "</form>";
            echo "</li>";
        } else {
            echo "<li>Add new leaderboard<br>";
            echo "<form method='post' action='/request/leaderboard/create.php' >";
            echo "<input type='hidden' name='u' value='$user' />";
            echo "<input type='hidden' name='c' value='$cookie' />";
            echo "<select name='g'>";
            foreach ($gamesList as $nextGame) {
                $nextGameID = $nextGame['ID'];
                $nextGameTitle = $nextGame['Title'];
                $nextGameConsole = $nextGame['ConsoleName'];
                echo "<option value='$nextGameID'>$nextGameTitle ($nextGameConsole)</option>";
            }
            echo "</select>";
            echo "&nbsp;<input type='submit' value='Create New Leaderboard' /><br><br>";
            echo "</form>";
            echo "</li>";
        }
        echo "</ul>";

        echo "</div>";
        echo "</div>";
    }

    if (isset($gameData) && isset($user) && $permissions >= Permissions::Developer) {
        echo "<div id='warning'>Status: OK!</div>";
    }

    if (!isset($gameData)) {
        $uniqueGameList = [];
        foreach ($lbData as $nextLB) {
            if (!isset($uniqueGameList[$nextLB['GameID']])) {
                $uniqueGameList[$nextLB['GameID']] = $nextLB;
                $uniqueGameList[$nextLB['GameID']]['NumLeaderboards'] = 1;
            } else {
                $uniqueGameList[$nextLB['GameID']]['NumLeaderboards']++;
            }
        }

        echo "<select id='consoleselector' onchange=\"ReloadLBPageByConsole()\">";
        echo "<option value='c_'>" . ($consoleIDInput ? 'All Consoles' : 'Filter by Console') . "</option>";
        $lastConsoleName = '';
        foreach ($uniqueGameList as $gameID => $nextEntry) {
            if ($nextEntry['ConsoleName'] !== $lastConsoleName) {
                $lastConsoleName = $nextEntry['ConsoleName'];
                $isSelected = $nextEntry['ConsoleID'] == $consoleIDInput;
                echo "<option value='c_{$nextEntry['ConsoleID']}' " . ($isSelected ? 'selected' : '') . ">$lastConsoleName</option>";
            }
        }
        echo "</select>";

        echo "<select id='gameselector' onchange=\"ReloadLBPageByGame()\">";
        echo "<option>Pick a Game</option>";
        $lastConsoleName = '';
        foreach ($uniqueGameList as $gameID => $nextEntry) {
            if (!$consoleIDInput && $nextEntry['ConsoleName'] !== $lastConsoleName) {
                $lastConsoleName = $nextEntry['ConsoleName'];
                $isSelected = $nextEntry['ConsoleID'] == $consoleIDInput;
                echo "<option value='c_{$nextEntry['ConsoleID']}' " . ($isSelected ? 'selected' : '') . ">-= $lastConsoleName =-</option>";
            }
            echo "<option value='$gameID'> " . $nextEntry['GameTitle'] . " (" . $nextEntry['ConsoleName'] . ") (" . $nextEntry['NumLeaderboards'] . " LBs) " . "</option>";
        }
        echo "</select>";
    }

    echo "<table class='smalltable xsmall'><tbody>";

    $sort1 = ($sortBy == 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 3) ? 13 : 3;
    $sort4 = ($sortBy == 4) ? 14 : 4;
    $sort5 = ($sortBy == 5) ? 15 : 5;
    $sort6 = ($sortBy == 6) ? 16 : 6;
    $sort7 = ($sortBy == 7) ? 17 : 7;

    if (isset($gameData) && isset($user) && $permissions >= Permissions::Developer) {
        echo "<th>ID</th>";
        echo "<th>Title/Description</th>";
        echo "<th>Type</th>";
        echo "<th>Lower Is Better</th>";
        echo "<th>Display Order</th>";
    } else {
        echo "<th><a href='/leaderboardList.php?s=$sort1'>ID</a></th>";
        echo "<th></th>";
        echo "<th><a href='/leaderboardList.php?s=$sort2'>Game</a></th>";
        // echo "<th><a href='/leaderboardList.php?s=$sort3'>Console</a></th>";
        echo "<th><a href='/leaderboardList.php?s=$sort4'>Title</a></th>";
        echo "<th><a href='/leaderboardList.php?s=$sort5'>Description</a></th>";
        echo "<th><a href='/leaderboardList.php?s=$sort6'>Type</a></th>";
        echo "<th><a href='/leaderboardList.php?s=$sort7'>Entries</a></th>";
    }

    $listCount = 0;

    foreach ($lbData as $nextLB) {
        $lbID = $nextLB['ID'];
        $lbTitle = $nextLB['Title'];
        $lbDesc = $nextLB['Description'];
        $lbMem = $nextLB['Mem'];
        $lbFormat = $nextLB['Format'];
        $lbLowerIsBetter = $nextLB['LowerIsBetter'];
        $lbNumEntries = $nextLB['NumResults'];
        settype($lbNumEntries, 'integer');
        $lbDisplayOrder = $nextLB['DisplayOrder'];
        $gameID = $nextLB['GameID'];
        $gameTitle = $nextLB['GameTitle'];
        $gameIcon = $nextLB['GameIcon'];
        $consoleName = $nextLB['ConsoleName'];

        $niceFormat = ($lbLowerIsBetter ? "Smallest " : "Largest ") . (($lbFormat == "SCORE") ? "Score" : "Time");

        if ($listCount++ % 2 == 0) {
            echo "<tr>";
        } else {
            echo "<tr>";
        }

        if (isset($gameData) && isset($user) && $permissions >= Permissions::Developer) {
            echo "<td>";
            echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbID</a>";
            echo "</td>";

            //echo "<td>";
            //echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName );
            //echo "</td>";

            // echo "<td>";
            // echo "$consoleName";
            // echo "</td>";

            echo "<td>";
            echo "<input style='width: 60%;' type='text' value='$lbTitle' id='LB_" . $lbID . "_Title' /><br>";
            echo "<input style='width: 100%;' type='text' value='$lbDesc' id='LB_" . $lbID . "_Desc' />";
            echo "</td>";

            echo "<td style='width: 20%;'>";
            echo "<select id='LB_" . $lbID . "_Format' name='i' >";
            $selected = $lbFormat == "SCORE" ? "selected" : "";
            echo "<option value='SCORE' $selected>Score</option>";
            $selected = $lbFormat == "TIME" ? "selected" : "";
            echo "<option value='TIME' $selected >Time (Frames)</option>";
            $selected = $lbFormat == "MILLISECS" ? "selected" : "";
            echo "<option value='MILLISECS' $selected >Time (Milliseconds)</option>";
            $selected = $lbFormat == "VALUE" ? "selected" : "";
            echo "<option value='VALUE' $selected>Value</option>";
            echo "</select>";

            //echo "<input type='text' value='$lbFormat' id='LB_" . $lbID . "_Format' />";
            echo "</td>";

            echo "<td style='width: 10%;'>";
            $checked = ($lbLowerIsBetter ? "checked" : "");
            echo "<input type='checkbox' $checked id='LB_" . $lbID . "_LowerIsBetter' />";
            echo "</td>";

            echo "<td style='width: 10%;'>";
            echo "<input size='3' type='text' value='$lbDisplayOrder' id='LB_" . $lbID . "_DisplayOrder' />";
            echo "</td>";

            echo "</tr>";

            if ($listCount++ % 2 == 0) {
                echo "<tr>";
            } else {
                echo "<tr>";
            }

            echo "<td>";
            //echo "Memory:";
            echo "</td>";
            echo "<td colspan='4'>";
            $memChunks = explode("::", $lbMem);
            foreach ($memChunks as &$memChunk) {
                $memChunk = mb_substr($memChunk, 4);    //	Remove STA: CAN: SUB: and VAL:
            }
            //var_dump( $memChunks );
            //echo "<input typ
            //echo "<textarea id='LB_" . $lbID . "_Mem' style='width:88%' class='fullwidth' onchange=\"UpdateLeaderboard('$user', '$lbID')\" >$lbMem</textarea>";

            echo "<table class='smalltable xsmall nopadding' ><tbody>";

            echo "<tr>";
            echo "<td style='width:10%;' >Start:</td>";
            echo "<td>";
            echo "<input type='text' id='LB_" . $lbID . "_Mem1' value='$memChunks[0]' style='width: 100%;' />";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td style='width:10%;'>Cancel:</td>";
            echo "<td>";
            echo "<input type='text' id='LB_" . $lbID . "_Mem2' value='$memChunks[1]' style='width: 100%;' />";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td style='width:10%;'>Submit:</td>";
            echo "<td>";
            echo "<input type='text' id='LB_" . $lbID . "_Mem3' value='$memChunks[2]' style='width: 100%;' />";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td style='width:10%;'>Value:</td>";
            echo "<td>";
            echo "<input type='text' id='LB_" . $lbID . "_Mem4' value='$memChunks[3]' style='width: 100%;' />";
            echo "</td>";
            echo "</tr>";

            echo "</tbody></table>";

            echo "<div style='float:left;' >";
            echo "&#124;";
            echo "&nbsp;";
            echo "<a href='/request/leaderboard/delete.php?u=$user&amp;i=$lbID&g=$gameID' onclick='return confirm(\"Are you sure you want to permanently delete this leaderboard?\")'>Permanently Delete?</a>";
            echo "&nbsp;";
            echo "&#124;";
            echo "&nbsp;";
            if ($lbNumEntries > 0) {
                echo "<a href='/request/leaderboard/reset.php?u=$user&amp;i=$lbID' onclick='return confirm(\"Are you sure you want to permanently delete all entries of this leaderboard?\")'>Reset all $lbNumEntries entries?</a>";
            } else {
                echo "0 entries";
            }
            echo "&nbsp;";
            echo "&#124;";
            echo "</div>";

            echo "<div class='rightalign'><input type='submit' name='Update' onclick=\"UpdateLeaderboard('$user', '$lbID')\" value='Update'></div>";

            echo "</td>";

            echo "</td>";
        } else {
            echo "<td>";
            echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbID</a>";
            echo "</td>";

            echo "<td>";
            echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, true, 32, false);
            echo "</td>";

            echo "<td>";
            echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 32, true);
            echo "</td>";

            // echo "<td class='text-nowrap'>";
            // echo "$consoleName";
            // echo "</td>";

            echo "<td>";
            echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbTitle</a>";
            echo "</td>";

            echo "<td>";
            echo "$lbDesc";
            echo "</td>";

            echo "<td class='text-nowrap'>";
            echo "$niceFormat";
            echo "</td>";

            echo "<td>";
            echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbNumEntries</a>";
            echo "</td>";
        }

        echo "</tr>";
    }

    //	hack:
    if (isset($gameData) && isset($user) && $permissions >= Permissions::Developer) {
        $listCount /= 2;
    }

    echo "</tbody></table>";
    echo "</div>";

    echo "<div class='rightalign row'>";
    if ($offset > 0) {
        $prevOffset = $offset - $maxCount;
        echo "<a href='/achievementList.php?s=$sortBy&amp;o=$prevOffset'>&lt; Previous $maxCount</a> - ";
    }
    if ($listCount == $maxCount) {
        //	Max number fetched, i.e. there are more. Can goto next 25.
        $nextOffset = $offset + $maxCount;
        echo "<a href='/achievementList.php?s=$sortBy&amp;o=$nextOffset'>Next $maxCount &gt;</a>";
    }
    echo "</div>";
    ?>
    <br>
</div>
</div>

<?php
if (count($codeNotes) > 0 && $permissions >= Permissions::Developer) {
        echo "<div id='rightcontainer'>";
        RenderCodeNotes($codeNotes);
        echo "</div>";
    }
?>

</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
