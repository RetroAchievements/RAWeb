<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$gameID = (int) request()->query('g');
if (empty($gameID)) {
    abort(404);
}

$achievementList = [];
$gamesList = [];

getGameMetadata($gameID, $user, $achievementData, $gameData);

if (empty($gameData)) {
    abort(404);
}

$consoleName = $gameData['ConsoleName'];
$consoleID = $gameData['ConsoleID'];
$gameTitle = $gameData['Title'];
$gameIcon = $gameData['ImageIcon'];

sanitize_outputs(
    $consoleName,
    $gameTitle,
);

RenderContentStart("Rename Game Entry ($consoleName)");
?>
<div id="mainpage">
    <div id="fullcontainer">
        <h2>Rename Game Entry</h2>
        <?php
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName);
        echo "<br><br>";

        echo "Renaming game entry <a href='/game/$gameID'>$gameTitle</a> for $consoleName.<br>";
        echo "Please enter a new name below:<br><br>";

        echo "<form method='post' action='/request/game/update-title.php'>";
        echo csrf_field();
        echo "<input type='hidden' name='game' value='$gameID' />";
        echo "New Name: <input type='text' name='title' value=\"$gameTitle\" maxlength='80' size='60' />";
        echo "<button>Submit</button>";
        echo "</form>";

        echo "<br><div class='text-danger'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=RAdmin&s=Attempt%20to%20Rename%20a%20title'>leave a message for admins</a> and they'll help sort it.</div>";
        ?>
        <br>
    </div>
</div>
<?php RenderContentEnd(); ?>
