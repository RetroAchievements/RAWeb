<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';
exit('no');

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

//var_dump( $gameData );
$gameTitle = $gameData['Title'];
$consoleName = $gameData['ConsoleName'];
$consoleID = $gameData['ConsoleID'];
$gameIcon = $gameData['ImageIcon'];

sanitize_outputs(
    $gameTitle,
    $consoleName,
);

$numGames = getGamesListWithNumAchievements($consoleID, $gamesList, 0);
//var_dump( $gamesList );
RenderHtmlStart();
RenderHtmlHead("Merge Game Entry ($consoleName)");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <h2>Merging Game Entry</h2>
        <?php
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 96);
        echo "<br><br>";
        echo " Merging game entry <a href='/game/$gameID'>$gameTitle</a> for $consoleName with another entry for $consoleName.<br>";
        echo "Please select an existing $consoleName game to merge this entry with:<br><br>";

        echo "<FORM method=post action='requestmergegameids.php'>";
        echo "<INPUT TYPE='hidden' NAME='u' VALUE='$user'>";
        echo "<INPUT TYPE='hidden' NAME='g' VALUE='$gameID'>";
        echo "<SELECT NAME='n'>";
        foreach ($gamesList as $gameEntry) {
            $nextGameTitle = $gameEntry['Title'];
            $nextGameID = $gameEntry['ID'];
            $nextGameNumCheevos = $gameEntry['NumAchievements'];
            echo "<option name='n' value='$nextGameID'>$nextGameTitle ($nextGameNumCheevos)</option>";
        }

        echo "</SELECT>";

        echo "&nbsp;<INPUT type='submit' value='Submit' />";
        echo "</FORM>";

        echo "<br><div id='warning'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=RAdmin&s=Attempt%20to%20Merge%20a%20title'>leave me a message for admins</a> and they'll help sort it.</div>";
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
