<?php

use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use App\Site\Enums\Permissions;
use Illuminate\Support\Facades\Blade;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    abort(401);
}

$fullModifyOK = $permissions >= Permissions::Developer;

$gameID = requestInputSanitized('g', null, 'integer');
$flag = requestInputSanitized('f', 3, 'integer');

$partialModifyOK =
    $permissions == Permissions::JuniorDeveloper
    && (
        checkIfSoleDeveloper($user, $gameID)
        || hasSetClaimed($user, $gameID, false)
    );

$achievementList = [];
$gamesList = [];

$codeNotes = [];

$achievementData = null;
$consoleName = null;
$gameIcon = null;
$gameTitle = null;
$gameIDSpecified = isset($gameID) && $gameID != 0;
$canHaveBeatenTypes = false;
if ($gameIDSpecified) {
    getGameMetadata($gameID, null, $achievementData, $gameData, 0, null, $flag);
    $gameTitle = $gameData['Title'];
    $consoleName = $gameData['ConsoleName'];
    $gameIcon = $gameData['ImageIcon'];
    sanitize_outputs($gameTitle, $consoleName);

    getCodeNotes($gameID, $codeNotes);

    $canHaveBeatenTypes = (
        mb_strpos($gameTitle, "[Subset") === false
        && mb_strpos($gameTitle, "~Test Kit~") === false
        && $gameData['ConsoleID'] !== 101
    );
} else {
    getGamesList(null, $gamesList);
}

$progressionLabel = __('achievement-type.' . AchievementType::Progression);
$winConditionLabel = __('achievement-type.' . AchievementType::WinCondition);

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
</script>
<article>
<?php
if ($flag === AchievementFlag::Unofficial) {
    echo "<h2>Unofficial Achievement Inspector</h2>";
}
if ($flag === AchievementFlag::OfficialCore) {
    echo "<h2>Core Achievement Inspector</h2>";
}

if ($gameIDSpecified) {
    if (!empty($gameData)) {
        echo gameAvatar($gameData);
        echo "<br><br>";
    }

    echo "<div class='grid gap-y-4 mb-8'>";
    if ($partialModifyOK || $fullModifyOK) {
        echo <<<HTML
            <p align="justify">
                <span class="font-bold">Instructions:</span> This is the game's achievement list as displayed on the website
                or in the emulator. The achievements will be ordered by 'Display Order', the column found on the right, in
                order from smallest to greatest. Adjust the numbers on the right to set an order for them to appear in. Any
                changes you make on this page will instantly take effect on the website, but you will need to press 'Refresh List'
                to see the new order on this page.
            </p>
        HTML;

        if ($canHaveBeatenTypes) {
            echo <<<HTML
                <p align="justify">
                    You can mark multiple achievements as '$progressionLabel' or '$winConditionLabel'. To do this, check the desired
                    checkboxes in the far-left column and click either the 'Set Selected to $progressionLabel' or 'Set Selected to $winConditionLabel'
                    button, depending on your needs. A game is considered 'beaten' when all $progressionLabel achievements and at least
                    one $winConditionLabel achievement are unlocked. If there are no $winConditionLabel achievements, the game is beaten
                    if all $progressionLabel achievements are unlocked. If there are no $progressionLabel achievements, the game is beaten
                    if any $winConditionLabel achievements are unlocked.
                </p>
            HTML;
        } else {
            echo <<<HTML
                <p align="justify">
                    This achievement set is either a subset, test kit, or an event. For this reason, it does not support types.
                </p>
            HTML;
        }

        if ($fullModifyOK) {
            echo "<p>You can " . ($flag === AchievementFlag::Unofficial ? "promote" : "demote") . " multiple achievements at the same time from this page by checking " .
                "the desired checkboxes in the far left column and clicking the '" . ($flag === AchievementFlag::Unofficial ? "Promote" : "Demote") . " Selected' " .
                "link. You can check or uncheck all checkboxes by clicking the 'All' or 'None' links in the first row of the table.</p>";
        }
    }
    echo "</div>";

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

    $bgColorClassNames = ["!bg-box-bg", "!bg-embed"];
    $currentBgColorIndex = 1;

    // Display all achievements
    foreach ((array) $achievementData as $achievementEntry) {
        // Alternate the background color of the achievement rows.
        $currentBgColorIndex = $currentBgColorIndex === 1 ? 0 : 1;

        $achID = $achievementEntry['ID'];
        // $gameConsoleID = $achievementEntry['ConsoleID'];
        $achTitle = $achievementEntry['Title'];
        $achDesc = $achievementEntry['Description'];
        $achMemAddr = htmlspecialchars($achievementEntry['MemAddr']);
        $achPoints = $achievementEntry['Points'];
        $achType = trim($achievementEntry['type'] ?? '');
        $achTypeLabel = $achType ? __('achievement-type.' . $achType) : 'none';

        // $achCreated = $achievementEntry['DateCreated'];
        // $achModified = $achievementEntry['DateModified'];
        $achCreated = getNiceDate(strtotime($achievementEntry['DateCreated']));
        $achModified = getNiceDate(strtotime($achievementEntry['DateModified']));

        $achBadgeName = $achievementEntry['BadgeName'];
        $achDisplayOrder = $achievementEntry['DisplayOrder'];
        $achBadgeFile = media_asset("Badge/$achBadgeName.png");

        sanitize_outputs($achTitle, $achDesc);

        echo "<tr class='$bgColorClassNames[$currentBgColorIndex]'>";
        if ($partialModifyOK || $fullModifyOK) {
            if ($achievementData[$achID]['Author'] === $user || $permissions >= Permissions::Developer) {
                echo "<td><span style='white-space: nowrap'><input type='checkbox' name='achievement" . $achID . "' value='" . $achID . "'> <label for='achievement'>$achID</label></span></td>";
            } else {
                echo "<td>$achID</td>";
            }
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

        $typeLabelClassNames = !$achType ? "text-muted" : "";

        if ($canHaveBeatenTypes) {
            echo <<<HTML
                <tr class="$bgColorClassNames[$currentBgColorIndex]">
                    <td><span class="font-bold">Type:</span></td>
                    <td colspan="7" class="p-2.5 $typeLabelClassNames">$achTypeLabel</td>
                </tr>
            HTML;
        }

        echo "<tr class='code-row hidden $bgColorClassNames[$currentBgColorIndex]'>";
        echo "<td><b>Code:</b></td>";
        echo "<td colspan='7' class='p-2.5' style='word-break:break-all;'>";
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
?>
</article>
<?php
if ($gameIDSpecified) {
    view()->share('sidebar', true);
    echo "<aside class='flex flex-col gap-y-8'>";

    $modificationLevel = 'none';
    if ($partialModifyOK) {
        $modificationLevel = 'partial';
    } elseif ($fullModifyOK) {
        $modificationLevel = 'full';
    }

    echo "<div>";
    echo Blade::render('
        <x-developer.inspector-toolbox
            :canHaveBeatenTypes="$canHaveBeatenTypes"
            :gameId="$gameId"
            :isManagingCoreAchievements="$isManagingCoreAchievements"
            :modificationLevel="$modificationLevel"
        />
    ', [
        'canHaveBeatenTypes' => $canHaveBeatenTypes,
        'gameId' => $gameID,
        'isManagingCoreAchievements' => $flag === AchievementFlag::OfficialCore,
        'modificationLevel' => $modificationLevel,
    ]);
    echo "</div>";

    if (!empty($codeNotes)) {
        echo "<div>";
        echo "<h3>Code Notes</h3>";
        RenderCodeNotes($codeNotes);
        echo "</div>";
    }
    echo "</aside>";
}
?>
<?php RenderContentEnd(); ?>
