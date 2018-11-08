<?php
require_once __DIR__ . '/../lib/bootstrap.php';

if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Registered ) )
{
    //	Immediate redirect if we cannot validate user!	//TBD: pass args?
    header( "Location: " . getenv('APP_URL') );
    exit;
}

$gameID = seekGET( 'g' );
$errorCode = seekGET( 'e' );

$gameIDSpecified = ( isset( $gameID ) && $gameID != 0 );
if( $gameIDSpecified )
{
    getGameMetadata( $gameID, $user, $achievementData, $gameData );
    $consoleName = $gameData[ 'ConsoleName' ];
    $consoleID = $gameData[ 'ConsoleID' ];
    $gameTitle = $gameData[ 'Title' ];
    $gameIcon = $gameData[ 'ImageIcon' ];
    $hashes = getHashListByGameID( $gameID );
}
else
{
    //	Immediate redirect: this is pointless otherwise!
    header( "Location: " . getenv('APP_URL') );
}

RenderDocType();
?>

<head>
    <?php RenderSharedHeader( $user ); ?>
    <?php RenderTitleTag( "Linked Hashes", $user ); ?>
    <?php RenderGoogleTracking(); ?>
</head>
<body>

    <?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
    <?php RenderToolbar( $user, $permissions ); ?>

    <div id="mainpage">
        <div class='left'>

            <h2>List of Linked Hashes</h2>

            <?php
            echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName, FALSE, 96 );
            echo "</br></br>";

            echo "<p><b>Hashes are used to confirm if two copies of a file are identical. We use it to ensure the player is using the same ROM as the achievement developer, or a compatible one.</b></p>";
            echo "Currently this game has <b>". count( $hashes ) ."</b> unique ROM(s) registered for it with the following MD5s:<br/><br/>";

            echo "<ul>";
            foreach( $hashes as $hash)
            {
                echo "<li><code>" . $hash[ 'hash' ] . "</code></li>";
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
