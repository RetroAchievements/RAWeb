<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$gameID = requestInputSanitized('g', null, 'integer');
if (empty($gameID)) {
    abort(404);
}

getGameMetadata($gameID, $user, $achievementData, $gameData);
$consoleName = $gameData['ConsoleName'];
$consoleID = $gameData['ConsoleID'];
$gameTitle = $gameData['Title'];
$gameIcon = $gameData['ImageIcon'];
$requestors = getSetRequestorsList($gameID);

RenderContentStart("Set Requests");
?>
<div id="mainpage">
    <div id='fullcontainer'>
        <h2>List of Set Requests</h2>
        <?php
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 96);
        echo "<br><br>";
        echo "A set for this game has been requested by the following users:<br><br>";
        echo "<ul>";
        if (!empty($requestors)) {
            foreach ($requestors as $requestor) {
                echo "<code><li>" . GetUserAndTooltipDiv($requestor['Requestor'], false) . "</code></li>";
            }
        }
        echo "</ul>";
        echo "<br>";
        ?>
        <br>
    </div>
</div>
<?php RenderContentEnd(); ?>
