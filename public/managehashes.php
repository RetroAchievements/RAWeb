<?php

use RA\ArticleType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$gameID = requestInputSanitized('g', null, 'integer');

if (empty($gameID)) {
    abort(404);
}

$achievementList = [];
$gamesList = [];
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

RenderContentStart("Manage Game Hashes");
?>
<script>
function UpdateHashDetails(user, hash) {
    var name = $.trim($('#HASH_' + hash + '_Name').val());
    var labels = $.trim($('#HASH_' + hash + '_Labels').val());
    showStatusMessage('Updating...');
    $.post('/request/game-hash/update.php', {
        game: <?= $gameID ?>,
        hash: hash,
        name: name,
        labels: labels
    })
        .done(function () {
            // Get comment date
            var date = new Date();
            var dateStr = date.getUTCDate() + ' ' + shortMonths[date.getUTCMonth()] + ' ' + date.getUTCFullYear() + ' ' + date.getUTCHours() + ':' + ('0' + date.getUTCMinutes()).slice(-2);

            $('.comment-textarea').parents('tr').before('<tr class="comment system"><td></td><td class="w-full" colspan="3"><div><span class="smalldate">' + dateStr + '</span></div><div style="word-break: break-word">' + hash + ' updated by ' + user + '. Description: "' + name + '". Label: "' + labels + '"</div></td></tr>');
        });
}

function UnlinkHash(user, gameID, hash, elem) {
    if (confirm('Are you sure you want to unlink the hash ' + hash + '?') === false) {
        return;
    }
    showStatusMessage('Updating...');
    $.post('/request/game-hash/delete.php', {
        game: <?= $gameID ?>,
        hash: hash
    })
        .done(function () {
            // Remove hash from table
            $(elem).closest('tr').remove();

            // Update number of hashes linked
            var cnt = $('#hashTable tr').length - 1;
            $('#hashCount').html('Currently this game has <b>' + cnt + '</b> unique hashes registered for it:');

            // Get comment date
            var date = new Date();
            var dateStr = date.getUTCDate() + ' ' + shortMonths[date.getUTCMonth()] + ' ' + date.getUTCFullYear() + ' ' + date.getUTCHours() + ':' + ('0' + date.getUTCMinutes()).slice(-2);

            $('.comment-textarea').parents('tr').before('<tr class="comment system"><td></td><td class="w-full" colspan="3"><div><span class="smalldate">' + dateStr + '</span></div><div style="word-break: break-word">' + hash + ' unlinked by ' + user + '</div></td></tr>');
        });
}
</script>
<div id="mainpage">
    <div id="fullcontainer">
        <h2>Manage Hashes</h2>

        <?php
        echo gameAvatar($gameData, iconSize: 64);

        echo "<br><div class='text-danger'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=RAdmin&s=Attempt to Unlink $gameTitle'>leave a message for admins</a> and they'll help sort it.</div>";

        echo "<div id='hashCount'>Currently this game has <b>$numLinks</b> unique hashes registered for it:</div><br>";

        echo "<div class='table-wrapper'><table id='hashTable'><tbody>";
        echo "<th>RetroAchievements Hash</th><th>Linked By</th><th>Description</th><th>Labels</th><th>Actions</th><th></th>\n";

        foreach ($hashes as $hashData) {
            $hash = $hashData['Hash'];

            echo "<tr>";
            echo "<td>$hash&nbsp;</td>";

            if (!empty($hashData['User'])) {
                echo "<td style='width: 10%; white-space: nowrap'>";
                echo userAvatar($hashData['User']);
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
<?php RenderContentEnd(); ?>
