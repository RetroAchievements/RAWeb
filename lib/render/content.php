<?php
function RenderTwitterFeed()
{
    echo "<div class='component stream'>";
    echo "<h3>Twitter Feed</h3>";

    echo "<a class='twitter-timeline'  href='https://twitter.com/RetroCheevos'  data-widget-id='365153450822103040'>Tweets by @RetroCheevos</a>";
    echo "<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+\"://platform.twitter.com/widgets.js\";fjs.parentNode.insertBefore(js,fjs);}}(document,\"script\",\"twitter-wjs\");</script>";

    echo "</div>";
}

function RenderTutorialComponent()
{
    echo "<div class='component tutorial' >";
    echo "<h3>How Do I Play?</h3>";
    echo "<p>";
    echo "<a href='/'>RetroAchievements</a> provides emulators for your PC where you can earn achievements while you play games!<br><br>";
    echo "<i>\"...like Xbox Live&trade; for emulation!\"</i><br><br>";
    echo "<a href='/download.php'>Download an emulator</a> for your chosen console, <a href='//www.retrode.com/'>find</a> some <a href='//www.lmgtfy.com/?q=download+mega+drive+roms'>ROMs</a> and join the fun!";
    echo "</p>";
    echo "</div>";
}

function RenderWelcomeComponent()
{
    if (isset($user)) {
        return;
    }

    echo "
    <div class='component welcome'>
        <h2>Welcome!</h2>
        <div id='Welcome'>
            <p>
            Were you the greatest in your day at Mega Drive or SNES games? Wanna prove it? Use our modified emulators and you will be awarded achievements as you play! Your progress will be tracked so you can compete with your friends to complete all your favourite classics to 100%: we provide the emulators for your Windows-based PC, all you need are the roms!<br>
            <a href='/Game/1'>Click here for an example:</a> which of these do you think you can get?
            </p>
        <br>
            <p style='clear:both; text-align:center'>
            <a href='/download.php'><b>&gt;&gt;Download an emulator here!&lt;&lt;</b></a><br>
            </p>
        </div>
    </div>";
}

function RenderRecentlyUploadedComponent($numToFetch)
{
    echo "<div class='component'>";
    echo "<h3>New Achievements</h3>";

    $numFetched = getLatestNewAchievements($numToFetch, $dataOut);
    if ($numFetched > 0) {
        echo "<table class='sidebar'><tbody>";
        echo "<tr><th>Added</th><th>Achievement</th><th>Game</th></tr>";

        $lastDate = '';
        $iter = 0;

        for ($i = 0; $i < $numToFetch; $i++) {
            $nextData = $dataOut[$i];

            $timestamp = strtotime($nextData['DateCreated']);
            $dateAwarded = date("d M", $timestamp);

            if (date("d", $timestamp) == date("d")) {
                $dateAwarded = "Today";
            } elseif (date("d", $timestamp) == (date("d") - 1)) {
                $dateAwarded = "Y'day";
            }

            if ($lastDate !== $dateAwarded) {
                $lastDate = $dateAwarded;
            }
            //    Alternating colours for table :)
            if ($iter++ % 2 == 0) {
                echo "<tr>";
            } else {
                echo "<tr>";
            }

            $uploadedAt = date("H:i", $timestamp);
            $achID = $nextData['ID'];
            $achTitle = $nextData['Title'];
            $achDesc = $nextData['Description'];
            $achPoints = $nextData['Points'];
            $gameID = $nextData['GameID'];
            $gameTitle = $nextData['GameTitle'];
            $gameIcon = $nextData['GameIcon'];
            $achBadgeName = $nextData['BadgeName'];
            $consoleName = $nextData['ConsoleName'];
            //$badgeFullPath = getenv('APP_STATIC_URL')."/Badge/" . $achBadgeName . ".png";

            echo "<td>$dateAwarded $uploadedAt</td>";
            echo "<td style='width:50%'><div class='fixheightcell'>";
            //echo "<img title='$achTitle' alt='$achTitle' src='$badgeFullPath' width='32' height='32' />";
            echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, true);
            echo "</div></td>";
            echo "<td><div class='fixheightcell'>";
            echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, true);
            echo "</div></td>";

            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "<br>";

        echo "<div class='morebutton'><a href='/achievementList.php?s=17'>more...</a></div>";

        echo "</div>";
    }
}

function RenderDeveloperStats($user, $type)
{
    echo "<div class='component'>";
    echo "<h3>Developer Stats</h3>";

    $devData = GetDeveloperStats(99, $type);
    if (count($devData) > 0) {
        $tableType = ($type == 2) ? "Num Achievements Won By Others" : (($type == 1) ? "Num Points Allocated" : "Num Achievements Developed");

        echo "<table><tbody>";
        echo "<tr><th>Rank</th><th>Developer</th><th>$tableType</th></tr>";

        for ($i = 0; $i < count($devData); $i++) {
            $nextData = $devData[$i];

            $rank = $i + 1;
            $developer = $nextData['Author'];
            $numAchievements = $nextData['NumCreated'];

            echo "<tr>";
            echo "<td>$rank</td>";

            echo "<td><div class='fixheightcell'>";
            echo GetUserAndTooltipDiv($developer, true);
            echo GetUserAndTooltipDiv($developer, false);
            echo "</div></td>";

            echo "<td>$numAchievements</td>";

            echo "</tr>";
        }
        echo "</tbody></table>";

        echo "</div>";
    }
}

function RenderDocsComponent()
{
    echo "
      <div class='component' style='text-align: center'>
        <!--h3>Documentation</h3-->
        <div id='docsbox' class='infobox'>
          <div>
            Read the <a href='https://docs.retroachievements.org/' target='_blank' rel='noopener'>Documentation</a> & <a href='https://docs.retroachievements.org/FAQ/' target='_blank' rel='noopener'>FAQ</a>.
          </div>
        </div>
      </div>";
}

function RenderCurrentlyOnlineComponent($user)
{
    if (isset($user)) {
        //    not impl
    } else {
        //    global

        echo "<div class='component'>";
        echo "<h3>Currently Online</h3>";
        echo "<div id='playersonlinebox' class='infobox'>";

        $playersArray = getCurrentlyOnlinePlayers();

        $numPlayers = count($playersArray);
        echo "<div>There are currently <strong>$numPlayers</strong> players online.</div>";

        //$numOutput = 0;
        //foreach( $playersArray as $nextPlayer )
        //{
        //    if( $numOutput > 0 && $numOutput == $numPlayers - 1 )
        //    {
        //        echo " and ";
        //    }
        //    elseif( $numOutput > 0 )
        //    {
        //        echo ", ";
        //    }
        //    echo GetUserAndTooltipDiv( $nextPlayer[ 'User' ], FALSE );
        //    $numOutput++;
        //}

        echo "</div>";

        echo "<div class='rightfloat lastupdatedtext'><small><span id='playersonline-update'></span></small></div>";
        echo "</div>";
    }
}

function RenderActivePlayersComponent()
{
    echo "<div class='component activeplayerscomponent' >";
    echo "<h3>Active Players</h3>";

    echo "<div id='playersactivebox' style='margin-bottom: 7px'></div>";

    echo "<div id='activeplayersbox' style='min-height: 54px'>";
    //    fetch via ajaphp
    // $playersArray = getCurrentlyOnlinePlayers();
    // $numPlayers = count($playersArray);
    // echo "There are currently <strong>$numPlayers</strong> players online.<br>";
    //$numOutput = 0;
    //foreach( $playersArray as $nextPlayer )
    //{
    //    if( $numOutput > 0 && $numOutput == $numPlayers - 1 )
    //    {
    //        echo " and ";
    //    }
    //    elseif( $numOutput > 0 )
    //    {
    //        echo ", ";
    //    }
    //
    //    echo GetUserAndTooltipDiv( $nextPlayer[ 'User' ], FALSE );
    //    $numOutput++;
    //}
    echo "</div>";

    echo "<div class='rightfloat lastupdatedtext'><small><span id='activeplayers-update'></span></small></div>";
    echo "</div>";
}

function RenderAOTWComponent($achID, $forumTopicID)
{
    if (!getAchievementMetadata($achID, $achData)) {
        return;
    }

    echo "<div class='component aotwcomponent' >";
    echo "<h3>Achievement of the Week</h3>";
    echo "<div id='aotwbox' style='text-align:center;'>";

    $gameID = $achData['GameID'];
    $gameTitle = $achData['GameTitle'];
    $gameIcon = $achData['GameIcon'];
    $consoleName = $achData['ConsoleName'];

    $achID = $achData['AchievementID'];
    $achTitle = $achData['AchievementTitle'];
    $achDesc = $achData['Description'];
    $achBadgeName = $achData['BadgeName'];
    $achPoints = $achData['Points'];
    $achTruePoints = $achData['TrueRatio'];

    echo "Achievement: ";
    echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, true);
    echo "<br>";

    echo "on Game: ";
    echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 32);
    echo "<br>";

    echo "<span class='clickablebutton'><a href='/viewtopic.php?t=$forumTopicID'>Join this tournament!</a></span>";

    echo "</div>";

    echo "</div>";
}

function RenderDemoVideosComponent()
{
    $width = '392'; //600px
    $height = $width * (3.0 / 4.0); //'100%'; //400px

    echo "<div id='demo' >";

    echo "<h2>Demos</h2>";

    echo "<h4>Using RAGens</h4>";

    echo "<div class='videocontainer' >";
    echo "<iframe style='border:0;' width='$width' height='$height' src='//www.youtube.com/embed/rKY2mZjurJw' allowfullscreen></iframe>";
    //echo "<iframe src='https://www.youtube-nocookie.com/v/rKY2mZjurJw?hl=en&amp;fs=1' frameborder='0' allowfullscreen></iframe>";
    //echo "<object data='https://www.youtube-nocookie.com/v/rKY2mZjurJw?hl=en&amp;fs=1' style='width:300px;'></object>";
    echo "</div>";

    echo "<h4>Finding Memory Addresses</h4>";

    echo "<div class='videocontainer' >";
    echo "<object type='application/x-shockwave-flash' width='$width' height='$height' data='//www.twitch.tv/widgets/archive_embed_player.swf' id='clip_embed_player_flash' >
        <param name='movie' value='//www.twitch.tv/widgets/archive_embed_player.swf' />
        <param name='allowScriptAccess' value='always' />
        <param name='allowNetworking' value='all' />
        <param name='flashvars' value='auto_play=false&amp;channel=" . getenv('TWITCH_CHANNEL') . "&amp;title=Finding%2BMemory%2BAddresses&amp;chapter_id=2674100&amp;start_volume=25' />
        </object>";
    echo "</div>";

    echo "</div>";
}
