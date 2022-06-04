<?php

use RA\GameAction;
use RA\Permissions;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!authenticateFromCookie(
    $user,
    $permissions,
    $userDetails,
    Permissions::Developer
)) {
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

if (empty($gameData)) {
    // Immediate redirect: this is pointless otherwise!
    header("Location: " . getenv('APP_URL') . "?e=unknowngame");
}

$consoleName = $gameData['ConsoleName'];
$consoleID = $gameData['ConsoleID'];
$gameTitle = $gameData['Title'];
$gameIcon = $gameData['ImageIcon'];

sanitize_outputs(
    $consoleName,
    $gameTitle,
);

RenderHtmlStart();
RenderHtmlHead("Rename Game Entry ($consoleName)");
?>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <h2>Rename Game Entry</h2>
        <?php
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName);
        echo "<br><br>";

        echo "Renaming game entry <a href='/game/$gameID'>$gameTitle</a> for $consoleName.<br>";
        echo "Please enter a new name below:<br><br>";

        echo "<form method=post action='/request/game/modify.php'>";
        echo "<input type='hidden' name='g' value='$gameID' />";
        echo "<input type='hidden' name='f' value='" . GameAction::ModifyTitle . "' />";
        echo "New Name: <input type='text' name='v' value=\"$gameTitle\" size='60' />";
        echo "&nbsp;<input type='submit' value='Submit' />";
        echo "</form>";

        echo "<br><div id='warning'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=RAdmin&s=Attempt%20to%20Rename%20a%20title'>leave a message for admins</a> and they'll help sort it.</div>";
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
