<?php
require_once __DIR__ . '/../lib/bootstrap.php';

$latestRAGensVer = file_get_contents( "./LatestRAGensVersion.html" );
$latestRAMekaVer = file_get_contents( "./LatestRAMekaVersion.html" );
$latestRANesVer = file_get_contents( "./LatestRANESVersion.html" );
$latestRAP64Ver = file_get_contents( "./LatestRAP64Version.html" );
$latestRAPCEVer = file_get_contents( "./LatestRAPCEVersion.html" );
$latestRASnesVer = file_get_contents( "./LatestRASnesVersion.html" );
$latestRAVBAVer = file_get_contents( "./LatestRAVBAVersion.html" );
$latestRALibretroVer = file_get_contents( "./LatestRALibretroVersion.html" );
$latestRAQUASI88Ver = file_get_contents( "./LatestRAQUASI88Version.html" );

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

    <?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
    <?php RenderToolbar( $user, $permissions ); ?>

    <div id="mainpage">
        <div id='leftcontainer' >

            <h2 class='longheader'>Note About Upgrading</h2>
            <p>
                If you were brought to this page because the emulator told you
                that a new version is available, download the new version and
                extract it over the existing folder. This way you won't lose
                any save files that you may have.
            </p>

            <h2 class='longheader' id='ragens'>Mega Drive/Genesis Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="/bin/RAGens.zip">Download RAGens v<?php echo $latestRAGensVer; ?> for Windows</a>
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
            <h2 class='longheader' id='rameka'>Master System/Game Gear/ColecoVision Emulator</h2>

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
            <h2 class='longheader' id='ralibretro'>RALibretro Multi Emulator - achievement creation</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="/bin/RALibretro.zip">Download RALibretro v<?php echo $latestRALibretroVer; ?> for Windows</a>
                </span>
            </div>

            <br/>
            <h2 class='longheader' id='raquasi88'>NEC PC-8000/8800 Emulator</h2>

            <div class='largeicon'>
                <span class='clickablebutton'>
                    <a href="/bin/RAQUASI88.zip">Download RAQUASI88 v<?php echo $latestRAQUASI88Ver; ?> for Windows</a>
                </span>
            </div>

            <br/>
            <h2 class='longheader' id='retroarch'>RetroArch - only for playing</h2>
            <p>
                The official RetroAchievements.org emulators are all Windows-based. For other operating systems - such as Linux (which includes <a href="https://retropie.org.uk/">RetroPie</a>, <a href="https://www.recalbox.com">Recalbox</a> and <a href="https://www.lakka.tv">lakka</a>), Mac, Android, PlayStation Vita, Wii U, and, of course, even Windows - you can use RetroArch:
                <br>
                <a href="https://retroarch.com">https://retroarch.com</a>
                <br>
                <br>
                You can also find <a href="https://docs.retroachievements.org/FAQ/#retroarch-emulators">more info about RetroArch on our FAQ</a>.
                <br>
                <br>
                <strong>Note: although we have some members contributing in the RetroArch front, keep in mind that RetroArch is maintained by <a href="https://github.com/libretro/">another team</a>.</strong>
            </p>

            <br/>
            <h3>Source Code</h3>
            <p>All RetroAchievements emulators are released as free software. Licensing terms for usage and distribution of derivative works may vary depending on the emulator. Please consult the license information of each one for more details.
            <br>
            Standalone Emulators repository: <a href='https://github.com/RetroAchievements/RAEmus'>https://github.com/RetroAchievements/RAEmus</a>
            <br>
            RALibretro repository: <a href='https://github.com/RetroAchievements/RALibretro'>https://github.com/RetroAchievements/RALibretro</a>
            </p>
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
