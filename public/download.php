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

            <br/>
            <h2 class='longheader' id='rameka'>RAMeka: Master System/Game Gear Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'><a href="/bin/RAMeka.zip">Download RAMeka</a></span><!-- (<?php echo $latestRAMekaVer; ?> for Windows) -->
            </div>

            <br/>
            <h2 class='longheader' id='rap64'>RAProject64: N64 Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'><a href="/bin/RAP64.zip">Download RAP64</a></span><!-- (<?php echo $latestRAP64Ver; ?> for Windows) -->
            </div>

            <br/>
            <h2 class='longheader' id='ralibretro'>RALibretro: multi-emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="https://docs.retroachievements.org/RALibretro/" target="_blank">RALibretro</a>
                </span>
            </div>

            <br/>
            <h3>Source Code</h3>
            <p>The vast majority of the code for these emulators is GPL protected, and as such all source code for these emulators is GPL alike, and publically available at the following URL: <a href='https://github.com/RetroAchievements/RAEmus'>https://github.com/RetroAchievements/RAEmus</a></p>

            <br/>
            <h3>Legal</h3>
            <p><small>RetroAchievements.org does not condone or supply any copyright-protected ROMs to be used in conjunction with the emulator supplied within.
                    There are no copyright-protected ROMs available for download on RetroAchievements.org.<br/></small></p>

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
