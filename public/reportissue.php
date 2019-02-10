<?php
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
$cookieRaw = RA_ReadCookie( 'RA_Cookie' );

$achievementID  = seekGET( 'i', 0 );
settype( $achievementID, 'integer' );

if( $achievementID == 0 ||
        getAchievementMetadata( $achievementID, $dataOut ) == false )
{
    header( "Location: " . getenv('APP_URL') . "?e=unknownachievement" );
    exit;
}

$achievementTitle = $dataOut[ 'AchievementTitle' ];
$desc = $dataOut[ 'Description' ];
$gameTitle = $dataOut[ 'GameTitle' ];
$achPoints = $dataOut[ 'Points' ];
$achBadgeName = $dataOut[ 'BadgeName' ];
$consoleID = $dataOut[ 'ConsoleID' ];
$consoleName = $dataOut[ 'ConsoleName' ];
$gameID = $dataOut[ 'GameID' ];
$gameBadge = $dataOut[ 'GameIcon' ];

$errorCode = seekGET( 'e' );

$pageTitle = "Report Broken Achievement";

RenderDocType( TRUE );
?>

<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader( $user ); ?>
    <?php RenderTitleTag( $pageTitle, $user ); ?>
    <?php RenderGoogleTracking(); ?>
</head>

<body>
<script type="text/javascript">
    function displayCore() {
        if (document.getElementById('emulator').value == 'RetroArch') {
            document.getElementById('core').style.display = '';
        } else {
            document.getElementById('core').style.display = 'none';
        }
    }
</script>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="leftcontainer">
        <div class="navpath">
            <a href="/gameList.php">All Games</a>
            &raquo; <a href="/gameList.php?c=<?php echo $consoleName ?>"><?php echo $consoleName ?></a>
            &raquo; <a href="/Game/<?php echo $gameTitle ?>"><?php echo $gameTitle ?></a>
            &raquo; <a href="/Achievement/<?php echo $achievementTitle ?>"><?php echo $achievementTitle ?></a>
            &raquo; <b>Issue Report</b>
        </div>

        <h3 class="longheader"><?php echo $pageTitle ?></h3>

        <form action="requestsubmitwebticket.php" method="post">
            <input type="hidden" value="<?php echo $user ?>" name="u">
            <input type="hidden" value="<?php echo $cookieRaw ?>" name="c">
            <input type="hidden" value="<?php echo $achievementID ?>" name="i">
            <table class="smalltable">
                <tbody>
                <tr class="alt">
                    <td>Game:</td>
                    <td style="width:80%">
                        <?php echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameBadge, $consoleName, false) ?>
                    </td>
                </tr>
                <tr>
                    <td>Achievement:</td>
                    <td>
                        <?php echo GetAchievementAndTooltipDiv($achievementID, $achievementTitle, $desc, $achPoints,
                            $gameTitle, $achBadgeName, true) ?>
                    </td>
                </tr>
                <tr class="alt">
                    <td>Issue:</td>
                    <td>
                        <select name="p" required>
                            <option value="" disabled selected hidden>Select your issue...</option>
                            <option value="1">Triggered at wrong time</option>
                            <option value="2">Doesn't Trigger</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Emulator:</td>
                    <td>
                        <select name="note[emulator]" id="emulator" onclick="displayCore()" required>
                            <option value="" disabled selected hidden>Select your emulator...</option>
                            <option>RAGens</option>
                            <option>RANes</option>
                            <option>RASnes9x</option>
                            <option>RAVBA</option>
                            <option>RAPCEngine</option>
                            <option>RAMeka</option>
                            <option>RAProject64</option>
                            <option>RAQUASI88</option>
                            <option>RALibRetro</option>
                            <option>RetroArch</option>
                        </select>
                        <br><input type="text" name="note[core]" id="core" placeholder="Please input the Core used."
                                   style="display: none">
                    </td>
                </tr>
                <tr class="alt">
                    <td>Checksum:</td>
                    <td>
                        <select name="note[checksum]" id="checksum" required>
                            <option value="Unknown">I don't know.</option>
                            <?php
                            foreach( getHashListByGameID( $gameID ) as $listKey => $hashArray )
                                foreach( $hashArray as $hashKey => $hash )
                                    echo "<option value='$hash'>$hash</option>";
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Description:</td>
                    <td colspan="2">
                        <textarea class="requiredinput fullwidth forum" name="note[description]" id="description"
                                  style="height:160px" rows="5" cols="61" placeholder="Describe your issue here..."
                                  required></textarea>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="2" class="fullwidth">
                        <input style="float:right" type="submit" value="Submit Issue Report" size="37">
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
    <div id="rightcontainer">
        <?php RenderScoreLeaderboardComponent($user, $points, true); ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
</html>
