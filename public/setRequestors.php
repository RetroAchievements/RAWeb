<?php

use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$gameID = requestInputSanitized('g', null, 'integer');
if (empty($gameID)) {
    abort(404);
}

$gameData = getGameData($gameID);
$consoleName = $gameData['ConsoleName'];
$consoleID = $gameData['ConsoleID'];
$gameTitle = $gameData['Title'];
$gameIcon = $gameData['ImageIcon'];
$requestors = getSetRequestorsList($gameID);

RenderContentStart("Set Requests");
?>
<article>
    <h2>List of Set Requests</h2>
    <?php
    echo gameAvatar($gameData, iconSize: 96);
    echo "<br><br>";
    echo "A set for this game has been requested by the following users:<br><br>";
    echo "<ul>";
    foreach ($requestors as $requestor) {
        echo "<code><li>" . userAvatar($requestor['Requestor']) . "</code></li>";
    }
    echo "</ul>";
    echo "<br>";
    ?>
    <br>
</article>
<?php RenderContentEnd(); ?>
