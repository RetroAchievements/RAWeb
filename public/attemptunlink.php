<?php
require_once __DIR__ . '/../lib/bootstrap.php';

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer)) {
    //	Immediate redirect if we cannot validate user!	//TBD: pass args?
    header("Location: " . getenv('APP_URL'));
    exit;
}

$gameID = seekGET('g');
$errorCode = seekGET('e');

settype($gameID, 'integer');

$achievementList = [];
$gamesList = [];

if (empty($gameID)) {
    //	Immediate redirect: this is pointless otherwise!
    header("Location: " . getenv('APP_URL'));
}

getGameMetadata($gameID, $user, $achievementData, $gameData);

$query = "SELECT MD5 FROM GameHashLibrary WHERE GameID=$gameID";
$dbResult = s_mysql_query($query);

$hashList = [];
while ($db_entry = mysqli_fetch_assoc($dbResult)) {
    $hashList[] = $db_entry['MD5'];
}

$numLinks = count($hashList);

$consoleName = $gameData['ConsoleName'];
$consoleID = $gameData['ConsoleID'];
$gameTitle = $gameData['Title'];
$gameIcon = $gameData['ImageIcon'];

//$numGames = getGamesListWithNumAchievements( $consoleID, $gamesList, 0 );
//var_dump( $gamesList );
RenderHtmlStart();
RenderHtmlHead("Rename Game Entry ($consoleName)");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div class='left'>

        <h2>Unlink Hashes</h2>

        <?php
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 96);
        echo "<br><br>";

        echo "Use this tool when an incorrect link has been made to a game, i.e. when you load a Super Mario Kart ROM, and the achievements for Super Mario World get loaded.<br>";

        echo "<br><div id='warning'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=RAdmin&s=Attempt to Unlink $gameTitle'>leave a message for admins</a> and they'll help you to sort it.</div><br>";

        echo "<h4><b>Unlink a single hash</b></h4>";
        echo "Currently this game has <b>$numLinks</b> unique ROM(s) registered for it with the following MD5s:<br><br>";
        echo "<form method=post action='/request/requestmodifygame.php'>";
        echo "<input type='hidden' name='u' VALUE='$user'>";
        echo "<input type='hidden' name='g' VALUE='$gameID'>";
        echo "<input type='hidden' name='f' VALUE='3'>";
        for ($i = 0; $i < $numLinks; $i++) {
            echo "<label>";
            echo "<input type='radio' name='v' VALUE='" . $hashList[$i] . "' " . ($i == 0 ? "required" : "") . ">";
            echo " <code>" . $hashList[$i] . "</code><br>";
            echo "</label>";
        }
        echo "<br>";
        echo "<input type='submit' value='Unlink selected entry'>";
        echo "</form>";
        echo "<br>";

        /**
         * UPDATE: do not allow dangerous actions anymore until proper failovers are in place
         * See commit df7c534c04ae1029e0f9517717f3b13a9008713d
         */
        //echo "<h4><b>Unlink all hashes</b></h4>";

        //echo "<p><b>WARNING: By clicking 'UNLINK ALL', all hashes linked to $gameTitle will be removed.</b></p>";

        //echo "<form method=post action='requestmodifygame.php'>";
        //echo "<input type='hidden' name='u' VALUE='$user'>";
        //echo "<input type='hidden' name='g' VALUE='$gameID'>";
        //echo "<input type='hidden' name='f' VALUE='2'>";
        //echo "<input type='hidden' name='v' VALUE='1'>";
        //echo "Perform Unlink:&nbsp;<INPUT TYPE='submit' VALUE='UNLINK ALL!'>";
        //echo "</form>";
        //echo "<br>";

        //echo "A new link will be requested when the ROM is next loaded in the emulator.<br><br>";

        //echo "Please note, no achievements will be deleted. However all entries that link to this game will be removed.<br>";
        //echo "To restore the achievements, simply load up the game in the emulator and select the entry from the drop-down list.<br><br>";
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
