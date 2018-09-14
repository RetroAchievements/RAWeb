<?php
require_once __DIR__ . '/../lib/bootstrap.php';

$latestRAGensVer = file_get_contents( "./LatestRAGensVersion.html" );
$latestRAMekaVer = file_get_contents( "./LatestRAMekaVersion.html" );
$latestRANesVer = file_get_contents( "./LatestRANESVersion.html" );
$latestRAP64Ver = file_get_contents( "./LatestRAP64Version.html" );
$latestRAPCEVer = file_get_contents( "./LatestRAPCEVersion.html" );
$latestRAPSXVer = file_get_contents( "./LatestRAPSXVersion.html" );
$latestRASnesVer = file_get_contents( "./LatestRASnesVersion.html" );
$latestRAVBAVer = file_get_contents( "./LatestRAVBAVersion.html" );

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

            <h2 class='longheader' id='ragens'>Mega Drive/Genesis Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="/bin/RAGens.zip">Download RAGens <?php echo $latestRAGensVer; ?> for Windows</a>
                </span>
            </div>

            <br/>

            <h2 class='longheader' id='rasnes9x'>SNES Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="/bin/RASnes9x.zip">Download RASnes9x v<?php echo $latestRASnesVer; ?> for Windows</a>
                </span>
            </div>

            <br/>
            <h2 class='longheader' id='ravba'>Gameboy/GBA Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="/bin/RAVBA.zip">Download RAVBA v<?php echo $latestRAVBAVer; ?> for Windows</a>
                </span>
            </div>

            <br/>
            <h2 class='longheader' id='ranes'>NES Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="/bin/RANes.zip">Download RANes v<?php echo $latestRANesVer; ?> for Windows</a>
                </span>
            </div>

            <br/>
            <h2 class='longheader' id='rameka'>Master System/Game Gear Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="/bin/RAMeka.zip">Download RAMeka v<?php echo $latestRAMekaVer; ?> for Windows</a>
                </span>
            </div>

            <br/>
            <h2 class='longheader' id='rap64'>N64 Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="/bin/RAP64.zip">Download RAP64 v<?php echo $latestRAP64Ver; ?> for Windows</a>
                </span>
            </div>

            <br/>
            <h2 class='longheader' id='ralibretro'>RALibretro Multi Emulator for Development</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="https://docs.retroachievements.org/RALibretro/" target="_blank">RALibretro Docs</a>
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
