<?php

use RA\ArticleType;
use RA\GameAction;
use RA\Permissions;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    // Immediate redirect if we cannot validate user!	//TBD: pass args?
    header("Location: " . getenv('APP_URL'));
    exit;
}

$gameID = requestInputSanitized('g', null, 'integer');
$errorCode = requestInputSanitized('e');

$achievementList = [];
$gamesList = [];

if (empty($gameID)) {
    // Immediate redirect: this is pointless otherwise!
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

RenderHtmlStart();
RenderHtmlHead("Manage Game Hashes");
?>
<body>
<?php RenderHeader($userDetails); ?>
<script>
function UpdateHashDetails(user, hash) {
    showStatusMessage('Updating...');
    var name = $.trim($('#HASH_' + hash + '_Name').val());
    var labels = $.trim($('#HASH_' + hash + '_Labels').val());
    $.ajax({
        type: "POST",
        url: '/request/game/modify.php',
        dataType: "json",
        data: {
            'g': <?= $gameID ?>,
            'f': <?= GameAction::UpdateHash ?>,
            'v': hash,
            'n': name,
            'l': labels
        },
        error: function (xhr, status, error) {
            showStatusFailure('Error: ' + (error || 'unknown error'));
        }
    }).done(function (data) {
        if (!data.success) {
            showStatusFailure('Error: ' + (data.error || 'unknown error'));
            return;
        }

        // Get comment date
        var date = new Date();
        var dateStr = date.getUTCDate() + ' ' + shortMonths[date.getUTCMonth()] + ' ' +  date.getUTCFullYear() + '<br>' + date.getUTCHours() + ':' + ('0' + date.getUTCMinutes()).slice(-2);

        $('.comment-textarea').parents('tr').before('<tr class="feed_comment localuser system"><td class="smalldate">' + dateStr + '</td><td class="iconscommentsingle"></td><td class="commenttext">' + hash + ' updated by ' + user + '. Description: "' + name + '". Label: "' + labels + '"</td></tr>');

        showStatusSuccess('Succeeded');
    });
}

function UnlinkHash(user, gameID, hash, elem) {
    if (confirm('Are you sure you want to unlink the hash ' + hash + '?') === false) {
        return;
    }
    var $warning = $('#warning');
    $warning.html('Status: updating...');
    $.ajax({
        type: "POST",
        url: '/request/game/modify.php',
        dataType: "json",
        data: {
            'g': <?= $gameID ?>,
            'f': <?= GameAction::UnlinkHash ?>,
            'v': hash
        },
        error: function (xhr, status, error) {
            showStatusFailure('Error: ' + (error || 'unknown error'));
        }
    }).done(function (data) {
        if (!data.success) {
            showStatusFailure('Error: ' + (data.error || 'unknown error'));
            return;
        }

        // Remove hash from table
        $(elem).closest('tr').remove();

        // Update number of hashes linked
        var cnt = $('#hashTable tr').length - 1
        $("#hashCount").html("Currently this game has <b>" + cnt + "</b> unique hashes registered for it:");

        // Get comment date
        var date = new Date();
        var dateStr = date.getUTCDate() + ' ' + shortMonths[date.getUTCMonth()] + ' ' +  date.getUTCFullYear() + '<br>' + date.getUTCHours() + ':' + ('0' + date.getUTCMinutes()).slice(-2);

        $('.comment-textarea').parents('tr').before('<tr class="feed_comment localuser system"><td class="smalldate">' + dateStr + '</td><td class="iconscommentsingle"></td><td class="commenttext">' + hash + ' unlinked by ' + user + '</td></tr>');

        showStatusSuccess('Succeeded');
    })
}
</script>
<div id="mainpage">
    <div id="fullcontainer">
        <h2>Manage Hashes</h2>

        <?php
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 64);

        echo "<br><div id='warning'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=RAdmin&s=Attempt to Unlink $gameTitle'>leave a message for admins</a> and they'll help sort it.</div>";
        RenderStatusWidget();

        echo "<div id='hashCount'>Currently this game has <b>$numLinks</b> unique hashes registered for it:</div><br>";

        echo "<div class='table-wrapper'><table id='hashTable'><tbody>";
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

            echo "<td style='width: 60%'><input type='text' id='HASH_${hash}_Name' value='" . attributeEscape($hashData['Name'] ?? '') . "' style='width: 100%'></td>";
            echo "<td style='width: 20%'><input type='text' id='HASH_${hash}_Labels' value='" . attributeEscape($hashData['Labels'] ?? '') . "' style='width: 100%'></td>";
            echo "<td style='width: 5%'><input type='submit' value='Update' onclick=\"UpdateHashDetails('$user', '$hash');\"></td>";
            echo "<td style='width: 5%'><input class='btnDelete' type='submit' value='Unlink' onclick=\"UnlinkHash('$user', '$gameID', '$hash', this);\"></td>";
        }

        echo "</tbody></table><br><br>";
        $numLogs = getArticleComments(ArticleType::GameHash, $gameID, 0, 1000, $logs);
        RenderCommentsComponent($user,
            $numLogs,
            $logs,
            $gameID,
            ArticleType::GameHash,
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
