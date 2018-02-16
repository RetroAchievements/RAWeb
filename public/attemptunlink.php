<?php
require_once __DIR__ . '/../lib/bootstrap.php';

if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Developer ) )
{
    //	Immediate redirect if we cannot validate user!	//TBD: pass args?
    header( "Location: http://" . AT_HOST );
    exit;
}

$gameID = seekGET( 'g' );
$errorCode = seekGET( 'e' );

$achievementList = array();
$gamesList = array();

$gameIDSpecified = ( isset( $gameID ) && $gameID != 0 );
if( $gameIDSpecified )
{
    getGameMetadata( $gameID, $user, $achievementData, $gameData );
}
else
{
    //	Immediate redirect: this is pointless otherwise!
    header( "Location: http://" . AT_HOST );
}

$query = "SELECT MD5 FROM GameHashLibrary WHERE GameID=$gameID";
$dbResult = s_mysql_query( $query );

$hashList = array();
while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
{
    $hashList[] = $db_entry[ 'MD5' ];
}

$numLinks = count( $hashList );

$consoleName = $gameData[ 'ConsoleName' ];
$consoleID = $gameData[ 'ConsoleID' ];
$gameTitle = $gameData[ 'Title' ];
$gameIcon = $gameData[ 'ImageIcon' ];

$pageTitle = "Rename Game Entry ($consoleName)";

//$numGames = getGamesListWithNumAchievements( $consoleID, $gamesList, 0 );
//var_dump( $gamesList );
RenderDocType();
?>

<head>
    <?php RenderSharedHeader( $user ); ?>
    <?php RenderTitleTag( $pageTitle, $user ); ?>
    <?php RenderGoogleTracking(); ?>
</head>
<body>

    <?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
    <?php RenderToolbar( $user, $permissions ); ?>

    <div id="mainpage">
        <div class='left'>

            <h2>Unlink Game Entry</h2>

            <?php
            echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName, FALSE, 96 );
            echo "</br></br>";

            echo "Unlinking game entry <a href='/Game/$gameID'>$gameTitle</a> for $consoleName.<br/>";
            echo "Use this tool when an incorrect link has been made to a game, i.e. when you load a Super Mario Kart ROM, and the achievements for Super Mario World get loaded.<br/>";
            echo "By clicking unlink, all links to this game ($gameTitle) will be removed. A new link will be requested when the ROM is next loaded in the emulator.<br/><br/>";

            echo "Currently this game has <b>$numLinks</b> unique ROM(s) registered for it with the following MD5s:<br/><br/>";

            echo "<ul>";
            for( $i = 0; $i < $numLinks; $i++ )
            {
                echo "<li><code>" . $hashList[ $i ] . "</code></li>";
            }
            echo "</ul>";

            echo "<br/>";

            echo "Please note, no achievements will be deleted. However all entries that link to this game will be removed.<br/>";
            echo "To restore the achievements, simply load up the game in the emulator and select the entry from the drop-down list.<br/><br/>";

            echo "<FORM method=post action='requestmodifygame.php'>";
            echo "<INPUT TYPE='hidden' NAME='u' VALUE='$user' />";
            echo "<INPUT TYPE='hidden' NAME='g' VALUE='$gameID' />";
            echo "<INPUT TYPE='hidden' NAME='f' VALUE='2' />";
            echo "<INPUT TYPE='hidden' NAME='v' VALUE='1' />";
            echo "Perform Unlink:&nbsp;<INPUT TYPE='submit' VALUE='Submit' />";
            echo "</FORM>";

            echo "<br/><div id='warning'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=Scott&s=Attempt%20to%20Unlink%20a%20title'>leave me a message</a> and I'll help sort it.</div>";
            ?>
            <br/>
        </div>
    </div>

    <?php RenderFooter(); ?>

</body>
</html>
