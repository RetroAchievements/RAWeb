<?php
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
$cookieRaw = RA_ReadCookie( 'RA_Cookie' );

$achievementID  = seekGET( 'i', 0 );
settype( $achievementID, 'integer' );

if( $achievementID == 0 ||
        getAchievementMetadata( $achievementID, $dataOut ) == false )
{
    header( "Location: http://" . AT_HOST . "?e=unknownachievement" );
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
    <script type='text/javascript'>
      function diplayCore() {
        if (document.getElementById('emulator').value == 'RetroArch') {
            document.getElementById('core').style.display = '';
        } else {
            document.getElementById('core').style.display = 'none';
        }
      }
    </script>
    <?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
    <?php RenderToolbar( $user, $permissions ); ?>

    <div id="mainpage">
        <div id='leftcontainer'>
            <?php
            echo "<div class='navpath'>";
            echo "<a href='/gameList.php'>All Games</a>";
            echo " &raquo; <a href='/gameList.php?c=$consoleID'>$consoleName</a>";
            echo " &raquo; <a href='/Game/$gameID'>$gameTitle</a>";
            echo " &raquo; <a href='/Achievement/$achievementID'>$achievementTitle</a>";
            echo " &raquo; <b>Issue Report</b>";
            echo "</div>";

            echo "<h3 class='longheader'>$pageTitle</h3>";

            echo "<table class='smalltable'>";
        		echo "<tbody>";

            echo "<form action='requestsubmitwebticket.php' method='post'>";
            echo "<input type='hidden' value='$user' name='u'></input>";
        		echo "<input type='hidden' value='$cookieRaw' name='c'></input>";
            echo "<input type='hidden' value='$achievementID' name='i'></input>";

            echo "<tr class='alt'>";
        		echo "<td>Game:</td>";
            echo "<td style='width:80%'>";
            echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameBadge, $consoleName, FALSE );
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>Achievement:</td>";
            echo "<td>";
            echo GetAchievementAndTooltipDiv( $achievementID, $achievementTitle, $desc, $achPoints, $gameTitle, $achBadgeName, TRUE );
            echo "</td>";
            echo "</tr>";

            echo "<tr class='alt'>";
            echo "<td>Issue:</td>";
            echo "<td>";
            echo "<select name='p' required>";
              echo "<option value='' disabled selected hidden>Select your issue...</option>";
              echo "<option value='1'>Triggered at wrong time</option>";
              echo "<option value='2'>Doesn't Trigger</option>";
            echo "</select>";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>Emulator:</td>";
            echo "<td>";
            echo "<select name='note[emulator]' id='emulator' onclick='diplayCore()' required>";
              echo "<option value='' disabled selected hidden>Select your emulator...</option>";
              echo "<option>RAGens</option>";
              echo "<option>RANes</option>";
              echo "<option>RASnes9x</option>";
              echo "<option>RAVBA</option>";
              echo "<option>RAPCEngine</option>";
              echo "<option>RAProject64</option>";
              echo "<option>RALibRetro</option>";
              echo "<option>RetroArch</option>";
            echo "</select>";
            echo "<br><input type='text' name='note[core]' id='core' placeholder='Please input the Core used.' style='display: none'>";
            echo "</td>";

            echo "<tr class='alt'>";
            echo "<td>Checksum:";
            echo "<br><small>(Optional)</small></td>";
            echo "<td>";
            echo "<input type='text' name='note[checksum]' id='checksum' size='30'>";
            echo "</td>";

            echo "<tr>";
            echo "<td>Description:</td>";
            echo "<td colspan='2'>";
            echo "<textarea class='requiredinput fullwidth forum' ";
            echo "style='height:160px' rows='5' cols='61' name='note[description]' id='description' placeholder='Describe your issue here...' required></textarea>";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td></td><td colspan='2' class='fullwidth'><input style='float:right' type='submit' value='Submit Issue Report' size='37'/></td></tr>";

            echo "</form>";

            echo "</tbody>";
        		echo "</table>";
            ?>
        </div>
        <div id='rightcontainer'>
            <?php
            RenderScoreLeaderboardComponent( $user, $points, TRUE );
            ?>
        </div> <!-- rightcontainer -->
    </div>

    <?php RenderFooter(); ?>

</body>
</html>
