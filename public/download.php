<?php
require_once __DIR__ . '/../lib/bootstrap.php';

$latestRAGensVer = file_get_contents( "./LatestRAGensVersion.html" );
$latestRASnesVer = file_get_contents( "./LatestRASnesVersion.html" );
$latestRAVBAVer = file_get_contents( "./LatestRAVBAVersion.html" );
$latestRANesVer = file_get_contents( "./LatestRANESVersion.html" );
$latestRAPCEVer = file_get_contents( "./LatestRAPCEVersion.html" );

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

$errorCode = seekGET( 'e' );
$pageTitle = "Download a client";
$staticData = getStaticData();
  
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
        <div id='leftcontainer' >

            <h2 class='longheader' id='ragens'>RAGens: Mega Drive/Genesis Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'><a href="/bin/RAGens.zip">Download RAGens</a></span> (<?php echo $latestRAGensVer; ?> for Windows)
            </div>

            <br/>

            <h2 class='longheader' id='rasnes9x'>RASnes9x: SNES Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'><a href="/bin/RASnes9x.zip">Download RASnes9x</a></span> (<?php echo $latestRASnesVer; ?> for Windows)
            </div>

            <br/>
            <h2 class='longheader' id='ravba'>RAVBA: Gameboy/GBA Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'><a href="/bin/RAVBA.zip">Download RAVBA</a></span> (<?php echo $latestRAVBAVer; ?> for Windows)
            </div>

            <br/>
            <h2 class='longheader' id='ranes'>RANes: NES Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'><a href="/bin/RANes.zip">Download RANes</a></span> (<?php echo $latestRANesVer; ?> for Windows)
            </div>

            <!--<h2 class='longheader' id='rapce'>RAPCEngine: PC Engine Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'><a href="/bin/RAPCE.zip">Download RAPCEngine</a></span> (<?php echo $latestRAPCEVer; ?> for Windows)
            </div>-->

            <!--<h2 class='longheader' id='rapce'>RAMeka: Master System Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'><a href="https://drive.google.com/drive/folders/0B8GcPMKEziSHYVBVSUhwM2VnZnc">Download RAMeka</a></span>
            </div>-->

            <h2 class='longheader' id='rapce'>RAProject64: N64 Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'><a href="http://retroachievements.org/viewtopic.php?t=4686">Download RAProject64</a></span>
            </div>

            <h2 class='longheader' id='ralibretro'>RALibRetro</h2>

            <div class='largeicon'>
                <span class='clickablebutton'><a href="https://retroachievements.github.io/docs/RALibretro/">RALibRetro</a></span>
            </div>-->


            <div class="infobox">
                <h3>Free Genesis ROMs</h3>
                RAGens is designed for use with all valid Mega Drive ROMs. This includes public domain ('free') ROMs. Here are a few that can be freely used with RAGens, which also have achievements associated to demonstrate the functionality:<br/><br/>
                <table><tbody>
                        <tr>
                            <td><a href="/Game/4"><img src="/Images/ZeroTolPic1.png" height='90' width='128'></a></td>
                            <td>
                                <?php echo GetGameAndTooltipDiv( 4, "Zero Tolerance", "/Images/001229.png", NULL, FALSE, 32, TRUE ); ?>
                                <!--<a href="/Game/4">Zero Tolerance</a>-->
                                <div class='largeicon'><span class='clickablebutton'><a href="/bin/ZEROTOL.zip">Download</a></span></div> A very well written Doom clone by Technopop that has fortunately been released to the public!
                        </tr>
                        <tr class='alt'>
                            <td><a href="/Game/5"><img src="/Images/MightyPic1.png" height='90' width='128'></a></td>
                            <td>
                                <?php echo GetGameAndTooltipDiv( 5, "Mighty Mighty Missile", "/Images/001154.png", NULL, FALSE, 32, TRUE ); ?>
                                <!--<a href="/Game/5">Mighty Mighty Missile</a>-->
                                <div class='largeicon'><span class='clickablebutton'><a href="/bin/MIGHTY2K.zip">Download</a></span></div> A public domain clone of Chu Chu Rocket for the Mega Drive.
                        </tr>
                    </tbody></table>
            </div>

            <h3>Source Code</h3>
            <p>The vast majority of the code for these emulators is GPL protected, and as such all source code for these emulators is GPL alike, and publically available at the following URL: <a href='https://github.com/RetroAchievements/RASuite'>github.com/RetroAchievements/RASuite</a></p>

            <h3>Legal</h3>
            <p><small>RetroAchievements.org does not condone or supply any copyright-protected ROMs to be used in conjunction with the emulator supplied within.
                    There are no copyright-protected ROMs available for download on RetroAchievements.org.
                    It is illegal to possess any ROMs that you did not yourself make the backup copy of.
                    RAGens is only for use with backup Mega Drive/Genesis ROMs that you legally own.
                    RetroAchievements recommends the use of a <a href='http://www.Retrode.com'>Retrode</a> to legitimately obtain backup ROM files of games you own.
                    For more information, please see <a href="http://www.gamefaqs.com/features/help/entry.html?cat=24">GameFAQs explanation</a> or search the web to learn more.<br/></small></p>

            <br/>

        </div>
        <div id='rightcontainer'>
            <?php
            RenderScoreLeaderboardComponent( $user, $points, FALSE );
            if( $user !== NULL )
            {
                RenderScoreLeaderboardComponent( $user, $points, TRUE );
            }
            RenderStaticDataComponent( $staticData );
            ?>
        </div>
    </div>

    <?php RenderFooter(); ?>

</body>
</html>
