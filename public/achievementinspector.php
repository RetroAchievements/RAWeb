<?php

use App\Community\Enums\ClaimSetType;
use App\Platform\Enums\AchievementFlags;
use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    abort(401);
}

$fullModifyOK = $permissions >= Permissions::Developer;

$gameID = requestInputSanitized('g', null, 'integer');
$flag = requestInputSanitized('f', 3, 'integer');

$partialModifyOK = $permissions == Permissions::JuniorDeveloper && (checkIfSoleDeveloper($user, $gameID) || hasSetClaimed($user, $gameID, true, ClaimSetType::NewSet));

$achievementList = [];
$gamesList = [];

$codeNotes = [];

$achievementData = null;
$consoleName = null;
$gameIcon = null;
$gameTitle = null;
$gameIDSpecified = isset($gameID) && $gameID != 0;
if ($gameIDSpecified) {
    getGameMetadata($gameID, $user, $achievementData, $gameData, 0, null, $flag);
    $gameTitle = $gameData['Title'];
    $consoleName = $gameData['ConsoleName'];
    $gameIcon = $gameData['ImageIcon'];
    sanitize_outputs($gameTitle, $consoleName);

    getCodeNotes($gameID, $codeNotes);
} else {
    getGamesList(null, $gamesList);
}

RenderContentStart("Manage Achievements");
?>
<script>
// Checks or unchecks all boxes
function toggle(status) {
    var checkboxes = document.querySelectorAll('[name^=\'achievement\']');
    for (var i = 0, n = checkboxes.length; i < n; i++) {
        checkboxes[i].checked = status;
    }
}

function updateDisplayOrder(objID) {
    var inputText = $('#' + objID).val();
    var inputNum = Math.max(0, Math.min(Number(inputText), 10000));
    showStatusMessage('Updating...');
    $.post('/request/achievement/update-display-order.php', {
        achievement: objID.substr(4),
        game: <?= $gameID ?>,
        number: inputNum,
    });
}

/**
 * @param {3 | 5} newFlag - see AchievementFlags.php
 */
function updateAchievementsFlag(newFlag) {
    // Creates an array of checked achievement IDs and sends it to the updateAchievements function
    var checkboxes = document.querySelectorAll('[name^=\'achievement\']');
    var achievements = [];
    for (var i = 0, n = checkboxes.length; i < n; i++) {
        if (checkboxes[i].checked) {
            achievements.push(checkboxes[i].getAttribute('value'));
        }
    }

    if (!confirm(`Are you sure you want to ${(newFlag === <?= AchievementFlags::OfficialCore ?> ? 'promote' : 'demote')} these achievements?`)) {
        return;
    }

    if (achievements.length === 0) {
        return;
    }

    showStatusMessage('Updating...');
    $.post('/request/achievement/update-flag.php', {
        achievements: achievements,
        flag: newFlag,
    })
        .done(function () {
            location.reload();
        });
}
</script>
<div id="mainpage">
    <?php
    if (!empty($codeNotes)) {
        echo "<div id='leftcontainer'>";
    } else {
        echo "<div id='fullcontainer'>";
    }

    if ($flag === AchievementFlags::Unofficial) {
        echo "<h2>Unofficial Achievement Inspector</h2>";
    }
    if ($flag === AchievementFlags::OfficialCore) {
        echo "<h2>Core Achievement Inspector</h2>";
    }

    if ($gameIDSpecified) {
        if (!empty($gameData)) {
            echo gameAvatar($gameData);
            echo "<br><br>";
        }

        if ($partialModifyOK || $fullModifyOK) {
            echo "<p align='justify'><b>Instructions:</b> This is the game's achievement list as displayed on the website or in the emulator. " .
                "The achievements will be ordered by 'Display Order', the column found on the right, in order from smallest to greatest. " .
                "Adjust the numbers on the right to set an order for them to appear in. Any changes you make on this page will instantly " .
                "take effect on the website, but you will need to press 'Refresh List' to see the new order on this page.";
        }

        if ($fullModifyOK) {
            echo "</br></br>You can " . ($flag === AchievementFlags::Unofficial ? "promote" : "demote") . " multiple achievements at the same time from this page by checking " .
                "the desired checkboxes in the far left column and clicking the '" . ($flag === AchievementFlags::Unofficial ? "Promote" : "Demote") . " Selected' " .
                "link. You can check or uncheck all checkboxes by clicking the 'All' or 'None' links in the first row of the table.</p><br>";
        }

        echo "<div style='text-align:center'><p class='embedded'><a href='/achievementinspector.php?g=$gameID&f=$flag'>Refresh Page</a> | ";
        if ($flag === AchievementFlags::Unofficial) {
            if ($fullModifyOK) {
                echo "<a class='cursor-pointer' onclick='updateAchievementsFlag(" . AchievementFlags::OfficialCore . ")'>Promote Selected</a> | ";
            }
            echo "<a href='/achievementinspector.php?g=$gameID'>Core Achievement Inspector</a> | ";
        }
        if ($flag === AchievementFlags::OfficialCore) {
            if ($fullModifyOK) {
                echo "<a class='cursor-pointer' onclick='updateAchievementsFlag(" . AchievementFlags::Unofficial . ")'>Demote Selected</a> | ";
            }
            echo "<a href='/achievementinspector.php?g=$gameID&f=5'>Unofficial Achievement Inspector</a> | ";
        }
        echo "<a href='/achievementinspector.php'>Back to List</a></p></div><br>";

        echo "Select <a onClick='toggle(true)'>All</a> | <a onClick='toggle(false)'>None</a><br/>";

        echo "<table class='table-highlight'><tbody>";
        echo "<tr class='do-not-highlight'>";
        echo "<th>ID</th>";
        echo "<th>Badge</th>";
        echo "<th width='99%'>Title/Description</th>";
        echo "<th>Points</th>";
        echo "<th>Created/Modified</th>";
        echo "<th>Display Order</th>";
        echo "</tr>";

        // Display all achievements
        foreach ((array) $achievementData as $achievementEntry) {
            $achID = $achievementEntry['ID'];
            // $gameConsoleID = $achievementEntry['ConsoleID'];
            $achTitle = $achievementEntry['Title'];
            $achDesc = $achievementEntry['Description'];
            $achMemAddr = htmlspecialchars($achievementEntry['MemAddr']);
            $achPoints = $achievementEntry['Points'];

            // $achCreated = $achievementEntry['DateCreated'];
            // $achModified = $achievementEntry['DateModified'];
            $achCreated = getNiceDate(strtotime($achievementEntry['DateCreated']));
            $achModified = getNiceDate(strtotime($achievementEntry['DateModified']));

            $achBadgeName = $achievementEntry['BadgeName'];
            $achDisplayOrder = $achievementEntry['DisplayOrder'];
            $achBadgeFile = media_asset("Badge/$achBadgeName.png");

            sanitize_outputs($achTitle, $achDesc);

            echo "<tr>";
            if ($fullModifyOK) {
                echo "<td><span style='white-space: nowrap'><input type='checkbox' name='achievement" . $achID . "' value='" . $achID . "'> <label for='achievement'>$achID</label></span></td>";
            } else {
                echo "<td>$achID</td>";
            }
            echo "<td><img alt='$achBadgeName' style='float:left;max-height: 64px' src='$achBadgeFile' /><br><span class='smalltext'>$achBadgeName</span></td>";
            echo "<td>$achTitle<br/><span class='smalltext'>$achDesc</span></td>";
            echo "<td>$achPoints</td>";
            echo "<td><span class='smalldate'>$achCreated</span><br><span class='smalldate'>$achModified</span></td>";
            if ($partialModifyOK || $fullModifyOK) {
                echo "<td><input class='displayorderedit' id='ach_$achID' type='text' value='$achDisplayOrder' onchange=\"updateDisplayOrder('ach_$achID')\" size='3' /></td>";
            } else {
                echo "<td>$achDisplayOrder</td>";
            }    // Just remove the input

            echo "</tr>";
            echo "<tr class='do-not-highlight'>";
            echo "<td><b>Code:</b></td>";
            echo "<td colspan='7' style='padding: 10px; word-break:break-all;'>";
            echo "<code style='word-break:break-all;'>$achMemAddr</code>";
            echo "</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<h3>Pick a game to modify:</h3>";

        echo "<table class='table-highlight'><tbody>";

        $lastConsole = 'NULL';
        foreach ($gamesList as $gameEntry) {
            $gameID = $gameEntry['ID'];
            $gameTitle = $gameEntry['Title'];
            $console = $gameEntry['ConsoleName'];
            sanitize_outputs($gameTitle, $console);

            if ($lastConsole == 'NULL') {
                echo "<tr><td>$console:</td>";
                echo "<td><select class='gameselector' onchange='window.location = \"/achievementinspector.php?g=\" + this.options[this.selectedIndex].value'><option>--</option>";
                $lastConsole = $console;
            } else {
                if ($lastConsole !== $console) {
                    echo "<tr><td></select>$console:</td>";
                    echo "<td><select class='gameselector' onchange='window.location = \"/achievementinspector.php?g=\" + this.options[this.selectedIndex].value'><option>--</option>";
                    $lastConsole = $console;
                }
            }

            echo "<option value='$gameID'>$gameTitle</option>";
            echo "<a href=\"/achievementinspector.php?g=$gameID\">$gameTitle</a><br>";
        }
        echo "</td>";
        echo "</select>";
        echo "</tbody></table>";
    }
    echo "</div>";

    if (!empty($codeNotes)) {
        echo "<div id='rightcontainer'>";
        echo "<h3>Code Notes</h3>";
        RenderCodeNotes($codeNotes);
        echo "</div>";
    }
    ?>
</div>
<?php RenderContentEnd(); ?>
