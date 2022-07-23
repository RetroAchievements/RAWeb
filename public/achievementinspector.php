<?php

use RA\AchievementType;
use RA\Permissions;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    header("Location: " . getenv('APP_URL'));
    exit;
}

$fullModifyOK = $permissions >= Permissions::Developer;

$gameID = requestInputSanitized('g', null, 'integer');
$errorCode = requestInputSanitized('e');
$flag = requestInputSanitized('f', 3, 'integer');

$partialModifyOK = $permissions == Permissions::JuniorDeveloper && checkIfSoleDeveloper($user, $gameID);

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
    getGamesList(0, $gamesList);
}

RenderHtmlStart();
RenderHtmlHead("Manage Achievements");
?>
<body>
<?php RenderHeader($userDetails); ?>
<script>
  // Checks or unchecks all boxes
  function toggle(status) {
    var checkboxes = document.querySelectorAll("[name^='achievement']");
    for (var i = 0, n = checkboxes.length; i < n; i++) {
      checkboxes[i].checked = status;
    }
  }

  function updateDisplayOrder(objID) {
    var inputText = $('#' + objID).val();
    var inputNum = Math.max(0, Math.min(Number(inputText), 10000));
    showStatusMessage('Updating...');
    $.ajax({
      type: 'POST',
      url: '/request/achievement/update.php',
      dataType: 'json',
      data: {
        u: '<?= $user ?>',
        a: objID.substr(4),
        g: <?= $gameID ?>,
        f: 1,
        v: inputNum,
      },
      error: function (xhr, status, serror) {
        showStatusFailure('Error: ' + (error || 'unknown error'));
      }
   })
     .done(function (data) {
       if (!data.success) {
         showStatusFailure('Error: ' + (data.error || 'unknown error'));
         return;
       }
       showStatusSuccess('Succeeded');
     });
  }

  function updateAchievementsTypeFlag(typeFlag) {
    // Creates an array of checked achievement IDs and sends it to the updateAchievements function
    var checkboxes = document.querySelectorAll("[name^='achievement']");
    var achievements = [];
    for (var i = 0, n = checkboxes.length; i < n; i++) {
      if (checkboxes[i].checked) {
        achievements.push(checkboxes[i].getAttribute("value"));
      }
    }

    if (!confirm(`Are you sure you want to ${(typeFlag === <?= AchievementType::OfficialCore ?> ? 'promote' : 'demote')} these achievements?`)) {
      return;
    }

    if (achievements.length === 0) {
      return;
    }

    showStatusMessage('Updating...');
    $.ajax({
      type: "POST",
      url: '/request/achievement/update.php',
      dataType: "json",
      data: {
        'a': achievements,
        'f': 3,
        'u': '<?= $user ?>',
        'v': typeFlag
      },
      error: function (xhr, status, error) {
        showStatusFailure('Error: ' + (error || 'unknown error'));
      }
    })
      .done(function (data) {
        if (!data.success) {
          showStatusFailure('Error: ' + (data.error || 'unknown error'));
          return;
        }
        document.location.reload();
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

    RenderStatusWidget();

    if ($flag === AchievementType::Unofficial) {
        echo "<h2 class='longheader'>Unofficial Achievement Inspector</h2>";
    }
    if ($flag === AchievementType::OfficialCore) {
        echo "<h2 class='longheader'>Core Achievement Inspector</h2>";
    }

    if ($gameIDSpecified) {
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 96);
        echo "<br><br>";

        if ($partialModifyOK || $fullModifyOK) {
            echo "<p align='justify'><b>Instructions:</b> This is the game's achievement list as displayed on the website or in the emulator. " .
                "The achievements will be ordered by 'Display Order', the column found on the right, in order from smallest to greatest. " .
                "Adjust the numbers on the right to set an order for them to appear in. Any changes you make on this page will instantly " .
                "take effect on the website, but you will need to press 'Refresh List' to see the new order on this page.";
        }

        if ($fullModifyOK) {
            echo "</br></br>You can " . ($flag === AchievementType::Unofficial ? "promote" : "demote") . " multiple achievements at the same time from this page by checking " .
                "the desired checkboxes in the far left column and clicking the '" . ($flag === AchievementType::Unofficial ? "Promote" : "Demote") . " Selected' " .
                "link. You can check or uncheck all checkboxes by clicking the 'All' or 'None' links in the first row of the table.</p><br>";
        }

        echo "<div style='text-align:center'><p><a href='/achievementinspector.php?g=$gameID&f=$flag'>Refresh Page</a> | ";
        if ($flag === AchievementType::Unofficial) {
            if ($fullModifyOK) {
                echo "<a class='pointer' onclick='updateAchievementsTypeFlag(" . AchievementType::OfficialCore . ")'>Promote Selected</a> | ";
            }
            echo "<a href='/achievementinspector.php?g=$gameID'>Core Achievement Inspector</a> | ";
        }
        if ($flag === AchievementType::OfficialCore) {
            if ($fullModifyOK) {
                echo "<a class='pointer'onclick='updateAchievementsTypeFlag(" . AchievementType::Unofficial . ")'>Demote Selected</a> | ";
            }
            echo "<a href='/achievementinspector.php?g=$gameID&f=5'>Unofficial Achievement Inspector</a> | ";
        }
        echo "<a href='/achievementinspector.php'>Back to List</a></p></div><br>";

        echo "<table><tbody>";
        echo "<tr>";
        if ($fullModifyOK) {
            echo "<th>Select <a onClick='toggle(true)'>All</a> | <a onClick='toggle(false)'>None</a></th>";
        }
        echo "<th>ID</th>";
        echo "<th>Badge</th>";
        echo "<th>Title</th>";
        echo "<th>Description</th>";
        // echo "<th>Mem</th>";
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
            $achBadgeFile = asset("Badge/$achBadgeName.png");

            sanitize_outputs($achTitle, $achDesc);

            echo "<tr>";
            if ($fullModifyOK) {
                echo "<td align='center'><input type='checkbox' name='achievement" . $achID . "' value='" . $achID . "'></td>";
            }
            echo "<td>$achID</td>";
            echo "<td><code>$achBadgeName</code><br><img alt='$achBadgeName' style='float:left;max-height: 64px' src='$achBadgeFile' /></td>";
            echo "<td>$achTitle</td>";
            echo "<td>$achDesc</td>";
            // echo "<td>$achMemAddr</td>";
            echo "<td>$achPoints</td>";
            echo "<td><span class='smalldate'>$achCreated</span><br><span class='smalldate'>$achModified</span></td>";
            if ($partialModifyOK || $fullModifyOK) {
                echo "<td><input class='displayorderedit' id='ach_$achID' type='text' value='$achDisplayOrder' onchange=\"updateDisplayOrder('ach_$achID')\" size='3' /></td>";
            } else {
                echo "<td>$achDisplayOrder</td>";
            }    // Just remove the input

            echo "</tr>";
            echo "<tr>";
            echo "<td><b>Code:</b></td>";
            echo "<td colspan='7' style='padding: 10px; word-break:break-all;'>";
            echo "<code style='word-break:break-all;'>$achMemAddr</code>";
            echo "</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<h3>Pick a game to modify:</h3>";

        echo "<table><tbody>";

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
        RenderCodeNotes($codeNotes);
        echo "</div>";
    }
    ?>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
