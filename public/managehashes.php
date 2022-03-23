<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

use RA\ArticleType;

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer)) {
    //	Immediate redirect if we cannot validate user!	//TBD: pass args?
    header("Location: " . getenv('APP_URL'));
    exit;
}

$gameID = requestInputSanitized('g', null, 'integer');
$errorCode = requestInputSanitized('e');

$achievementList = [];
$gamesList = [];

if (empty($gameID)) {
    //	Immediate redirect: this is pointless otherwise!
    header("Location: " . getenv('APP_URL'));
}

getGameMetadata($gameID, $user, $achievementData, $gameData);

$hashes = getHashListByGameID($gameID);
$numLinks = count($hashes);

$consoleName = $gameData['ConsoleName'];
$consoleID = $gameData['ConsoleID'];
$gameTitle = $gameData['Title'];
$gameIcon = $gameData['ImageIcon'];

sanitize_outputs(
    $consoleName,
    $gameTitle,
);

//$numGames = getGamesListWithNumAchievements( $consoleID, $gamesList, 0 );
//var_dump( $gamesList );
RenderHtmlStart();
RenderHtmlHead("Manage Game Hashes");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<script>
  function UpdateHashDetails(user, hash) {
    var name = $.trim($('#HASH_' + hash + '_Name').val());
    var labels = $.trim($('#HASH_' + hash + '_Labels').val());
    var posting = $.post('/request/game/modify.php', { u: user, g: <?php echo $gameID ?>, f: 4, v: hash, n: name, l: labels });
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
<div id="mainpage">
    <div id="fullcontainer">
        <h2>Manage Hashes</h2>

        <?php
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 64);

        echo "<br><div id='warning'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=RAdmin&s=Attempt to Unlink $gameTitle'>leave a message for admins</a> and they'll help sort it.</div><br>";

        echo "Currently this game has <b>$numLinks</b> unique hashes registered for it:<br><br>";

        echo "<div class='table-wrapper'><table><tbody>";
        echo "<th>RetroAchievements Hash</th><th>Linked By</th><th>Description</th><th>Labels</th><th>Actions</th><th></th>\n";

        foreach ($hashes as $hashData) {
            $hash = $hashData['Hash'];

            echo "<tr>";
            echo "<td>$hash&nbsp;</td>";

            if (!empty($hashData['User'])) {
                echo "<td style='width: 10%; white-space: nowrap'>";
                echo GetUserAndTooltipDiv($hashData['User']);
                echo "</td>";
            } else {
                echo "<td style='width: 10%'></td>";
            }

            echo "<td style='width: 60%'><input type='text' id='HASH_${hash}_Name' value='" . $hashData['Name'] . "' style='width: 100%'></td>";
            echo "<td style='width: 20%'><input type='text' id='HASH_${hash}_Labels' value='" . $hashData['Labels'] . "' style='width: 100%'></td>";
            echo "<td style='width: 5%'><input type='submit' value='Update' onclick=\"UpdateHashDetails('$user', '$hash');\"></td>";
            echo "<td style='width: 5%'><form method='post' action='/request/game/modify.php' onsubmit=\"return confirm('Are you sure you want to unlink the hash $hash?');\">";
            echo "<input type='hidden' name='u' value='$user'>";
            echo "<input type='hidden' name='g' value='$gameID'>";
            echo "<input type='hidden' name='f' value='3'>";
            echo "<input type='hidden' name='v' value='$hash'>";
            echo "<input type='submit' value='Unlink'></form></td>";
            echo "</tr>\n";
        }

        echo "</tbody></table><br><br>";
        $numLogs = getArticleComments(ArticleType::Hash, $gameID, 0, 1000, $logs);
        RenderCommentsComponent($user,
            $numLogs,
            $logs,
            $gameID,
            ArticleType::Hash,
            $permissions
        );
        echo "</div>";
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
