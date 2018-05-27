<?php
require_once __DIR__ . '/../lib/bootstrap.php';

if( isset( $user ) )
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

            <h2>Show Linked Hashes</h2>

            <?php
            echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName, FALSE, 96 );
            echo "</br></br>";

            echo "<strong>Linked hashes to <a href='/Game/$gameID'>$gameTitle</a> ($consoleName).</strong><br/>";

            echo "Currently this game has <b>$numLinks</b> unique ROM(s) registered for it with the following MD5s:<br/><br/>";

            echo "<ul>";
            for( $i = 0; $i < $numLinks; $i++ )
            {
                echo "<li><code>" . $hashList[ $i ] . "</code></li>";
            }
            echo "</ul>";

            echo "<br/>";

            ?>
            <br/>
        </div>
    </div>

    <?php RenderFooter(); ?>

</body>
</html>
