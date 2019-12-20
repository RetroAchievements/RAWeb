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
    $requestors = getSetRequestorsList($gameID);
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
    <?php RenderTitleTag( "Set Requestors", $user ); ?>
    <?php RenderGoogleTracking(); ?>
</head>
<body>
    <?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
    <?php RenderToolbar( $user, $permissions ); ?>
    <div id="mainpage">
        <div id='fullcontainer'>
            <h2>List of Set Requestors</h2>
            <?php
                echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName, FALSE, 96 );
                echo "</br></br>";
                echo "A set for this game has been requested by the following users:<br/><br/>";
                echo "<ul>";
                if (!empty($requestors))
                {
                    foreach( $requestors as $requestor)
                    {
                        echo "<code><li>" . GetUserAndTooltipDiv( $requestor['Requestor'], FALSE) . "</code></li>";
                    }
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