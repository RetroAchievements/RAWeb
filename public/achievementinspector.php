<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);
$modifyOK = ($permissions >= \RA\Permissions::Developer);

$gameID = requestInputSanitized('g', null, 'integer');
$errorCode = requestInputSanitized('e');
$flag = requestInputSanitized('f', 3, 'integer');

$achievementList = [];
$gamesList = [];

$codeNotes = [];

$achievementData = null;
$consoleName = null;
$gameIcon = null;
$gameTitle = null;
$gameIDSpecified = (isset($gameID) && $gameID != 0);
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

<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>


<script>
  //Sleeps for the given amount of milliseconds
  function sleep(milliseconds) {
    var start = new Date().getTime();
    for (var i = 0; i < 1e7; i++) {
      if ((new Date().getTime() - start) > milliseconds){
        break;
      }
    }
  }

  //Checks or unchecks all boxes
  function toggle(status) {
    checkboxes = document.querySelectorAll("[name^='acvhievement']");
    for(var i=0, n=checkboxes.length;i<n;i++) {
      checkboxes[i].checked = status;
    }
  }

  //Sends update achiecements request
  function updateAchievements(user, achievements, flag) {
    $.ajax(
      {
        type: "POST",
        url: '/request/achievement/update.php?a=-1&f=4&u=' + user + '&v=' + flag,
        data: {"achievementArray" : achievements},
        success: function (result) {
        },
        error: function (temp, temp1, temp2) {
          alert('Error ' + temp + temp1 + temp2);
        },
      });
  }

  //When clicked, creates an array of checked achievement IDs and sends it to the updateAchievements function
  $(function () {
    $('.updateAchievements').click(function () {
      checkboxes = document.querySelectorAll("[name^='acvhievement']");
      var achievements = [];
      for(var i=0, n=checkboxes.length;i<n;i++) {
        if (checkboxes[i].checked == true) {
            achievements.push(checkboxes[i].getAttribute("value"));
        }
      }
      if (achievements.length > 0) {
        updateAchievements('<?php echo $user; ?>', achievements, document.getElementsByClassName('updateAchievements')[0].getAttribute("value"));
        sleep(100);
        document.location.reload(true)
      }
    });
  });
</script>

<div id="mainpage">
    <?php
    if (count($codeNotes) > 0) {
        echo "<div id='leftcontainer'>";
    } else {
        echo "<div id='fullcontainer'>";
    }
    echo "<div id='warning' class='rightfloat'>Status: OK!</div>";

    if ($flag == 5) {
        echo "<h2 class='longheader'>Unofficial Achievement Inspector</h2>";
    } else {
        echo "<h2 class='longheader'>Core Achievement Inspector</h2>";
    }

    if ($gameIDSpecified) {
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 96);
        echo "<br><br>";

        if ($modifyOK) {
            echo "<p align='justify'><b>Instructions:</b> This is the game's achievement list as displayed on the website or in the emulator. " .
                "The achievements will be ordered by 'Display Order', the column found on the right, in order from smallest to greatest. " .
                "Adjust the numbers on the right to set an order for them to appear in. Any changes you make on this page will instantly " .
                "take effect on the website, but you will need to press 'Refresh List' to see the new order on this page.</br></br>" .
                "You can " . ($flag == 5 ? "promote" : "demote") . " multiple achievements at the same time from this page by checking " .
                "the desired checkboxes in the far left column and clicking the '" . ($flag == 5 ? "Promote" : "Demote") . " Selected' " .
                "link. You can check or uncheck all checkboxes by clicking the 'All' or 'None' links in the first row of the table.</p><br>";
        }

        echo "<div style='text-align:center'><p><a href='/achievementinspector.php?g=$gameID&f=$flag'>Refresh Page</a> | ";
        if ($flag == 5) {
            if ($modifyOK) {
                echo "<a class='updateAchievements' value='3'>Promote Selected</a> | ";
            }
            echo "<a href='/achievementinspector.php?g=$gameID'>Core Achievement Inspector</a> | ";
        } else {
            if ($modifyOK) {
                echo "<a class='updateAchievements' value='5'>Demote Selected</a> | ";
            }
            echo "<a href='/achievementinspector.php?g=$gameID&f=5'>Unofficial Achievement Inspector</a> | ";
        }
        echo "<a href='/achievementinspector.php'>Back to List</a></p></div><br>";

        echo "<table><tbody>";
        echo "<tr>";
        if ($modifyOK) {
            echo "<th>Select <a onClick='toggle(true)'>All</a> | <a onClick='toggle(false)'>None</a></th>";
        }
        echo "<th>ID</th>";
        echo "<th>Badge</th>";
        echo "<th>Title</th>";
        echo "<th>Description</th>";
        //echo "<th>Mem</th>";
        echo "<th>Points</th>";
        echo "<th>Created/Modified</th>";
        echo "<th>Display Order</th>";
        echo "</tr>";

        //	Display all achievements
        foreach ((array) $achievementData as $achievementEntry) {
            $achID = $achievementEntry['ID'];
            //$gameConsoleID = $achievementEntry['ConsoleID'];
            $achTitle = $achievementEntry['Title'];
            $achDesc = $achievementEntry['Description'];
            $achMemAddr = htmlspecialchars($achievementEntry['MemAddr']);
            $achPoints = $achievementEntry['Points'];

            //$achCreated = $achievementEntry['DateCreated'];
            //$achModified = $achievementEntry['DateModified'];
            $achCreated = getNiceDate(strtotime($achievementEntry['DateCreated']));
            $achModified = getNiceDate(strtotime($achievementEntry['DateModified']));

            $achBadgeName = $achievementEntry['BadgeName'];
            $achDisplayOrder = $achievementEntry['DisplayOrder'];
            $achBadgeFile = getenv('ASSET_URL') . "/Badge/$achBadgeName" . ".png";

            sanitize_outputs($achTitle, $achDesc);

            echo "<tr>";
            if ($modifyOK) {
                echo "<td align='center'><input type='checkbox' name='acvhievement" . $achID . "' value='" . $achID . "'></td>";
            }
            echo "<td>$achID</td>";
            echo "<td><code>$achBadgeName</code><br><img alt='' style='float:left;' src='$achBadgeFile' /></td>";
            echo "<td>$achTitle</td>";
            echo "<td>$achDesc</td>";
            //echo "<td>$achMemAddr</td>";
            echo "<td>$achPoints</td>";
            echo "<td><span class='smalldate'>$achCreated</span><br><span class='smalldate'>$achModified</span></td>";
            if ($modifyOK) {
                echo "<td><input class='displayorderedit' id='ach_$achID' type='text' value='$achDisplayOrder' onchange=\"updateDisplayOrder('$user', 'ach_$achID')\" size='3' /></td>";
            } else {
                echo "<td>$achDisplayOrder</td>";
            }    //	Just remove the input

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

    if (count($codeNotes) > 0) {
        echo "<div id='rightcontainer'>";
        RenderCodeNotes($codeNotes);
        echo "</div>";
    }
    ?>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
