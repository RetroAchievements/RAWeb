<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!RA_ReadCookieCredentials(
    $user,
    $points,
    $truePoints,
    $unreadMessageCount,
    $permissions,
    \RA\Permissions::Developer
)) {
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

if (empty($gameData)) {
    //	Immediate redirect: this is pointless otherwise!
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

//$numGames = getGamesListWithNumAchievements( $consoleID, $gamesList, 0 );
//var_dump( $gamesList );
RenderHtmlStart();
RenderHtmlHead("Rename Game Entry ($consoleName)");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <h2>Rename Game Entry</h2>
        <?php

        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 32);
        echo "<br><br>";

        echo "Renaming game entry <a href='/game/$gameID'>$gameTitle</a> for $consoleName.<br>";
        echo "Please enter a new name below:<br><br>";

        echo "<FORM method=post action='/request/game/modify.php'>";
        echo "<INPUT TYPE='hidden' NAME='u' VALUE='$user' />";
        echo "<INPUT TYPE='hidden' NAME='g' VALUE='$gameID' />";
        echo "<INPUT TYPE='hidden' NAME='f' VALUE='1' />";
        echo "New Name: <INPUT TYPE='text' NAME='v' VALUE=\"$gameTitle\" size='60' />";
        echo "&nbsp;<INPUT TYPE='submit' VALUE='Submit' />";
        echo "</FORM>";

        echo "<br><div id='warning'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=RAdmin&s=Attempt%20to%20Rename%20a%20title'>leave a message for admins</a> and they'll help sort it.</div>";
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
