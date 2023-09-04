<?php

use App\Community\Enums\ArticleType;
use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$gameID = requestInputSanitized('g', null, 'integer');

if (empty($gameID)) {
    abort(404);
}

$gamesList = [];
$gameData = getGameData($gameID);

$hashes = getHashListByGameID($gameID);
$numLinks = count($hashes);

$consoleName = $gameData['ConsoleName'];
$consoleID = $gameData['ConsoleID'];
$gameTitle = $gameData['Title'];
$gameIcon = $gameData['ImageIcon'];
$forumTopicID = $gameData['ForumTopicID'];

sanitize_outputs(
    $consoleName,
    $gameTitle,
);

RenderContentStart("Manage Game Hashes - $gameTitle");
?>
<script>
var shortMonths = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'May',
  'Jun',
  'Jul',
  'Aug',
  'Sep',
  'Oct',
  'Nov',
  'Dec'];

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

            $('.comment-textarea').parents('tr').before('<tr class="comment system"><td></td><td class="w-full" colspan="3"><div><span class="smalldate">' + dateStr + '</span></div><div style="word-break: break-word">' + hash + ' updated by ' + user + '. File Name: "' + name + '". Label: "' + labels + '"</div></td></tr>');
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
<article>
    <div class='navpath'>
        <?= renderGameBreadcrumb($gameData) ?>
        &raquo; <b>Manage Hashes</b>
    </div>

    <h3>Manage Hashes</h3>

    <?php
    echo gameAvatar($gameData, iconSize: 64);

    echo "<div class='mt-2 flex flex-col gap-1'>";
    echo " <a href='/linkedhashes.php?g=$gameID'>Supported Game Files</a>";
    if (isset($forumTopicID)) {
        echo " <a href='/viewtopic.php?t=$forumTopicID'>Official Forum Topic</a>";
    }
    echo "</div>";

    echo "<br><div class='text-danger'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=RAdmin&s=Attempt to Unlink $gameTitle'>leave a message for admins</a> and they'll help sort it.</div>";

    echo "<br/><div id='hashCount'>Currently this game has <b>$numLinks</b> unique hashes registered for it:</div>";

    echo "<div class='table-wrapper'><table id='hashTable' class='table-highlight'><tbody>";

    echo "<tr class='do-not-highlight'>";
    echo "<th>RetroAchievements Hash</th><th>Linked By</th><th>File Name</th><th>Labels</th><th>Actions</th><th></th>\n";
    echo "</tr>";

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

        echo "<td style='width: 60%'><input type='text' id='HASH_{$hash}_Name' value='" . attributeEscape($hashData['Name'] ?? '') . "' style='width: 100%'></td>";
        echo "<td style='width: 20%'><input type='text' id='HASH_{$hash}_Labels' value='" . attributeEscape($hashData['Labels'] ?? '') . "' style='width: 100%'></td>";
        echo "<td style='width: 5%'><button type='button' class='btn' onclick=\"UpdateHashDetails('$user', '$hash');\">Update</button></td>";
        echo "<td style='width: 5%'><button type='button' class='btn' onclick=\"UnlinkHash('$user', '$gameID', '$hash', this);\">Unlink</button></td>";
    }

    echo "</tbody></table><br><br>";
    $numLogs = getRecentArticleComments(ArticleType::GameHash, $gameID, $logs);
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
</article>
<?php RenderContentEnd(); ?>
