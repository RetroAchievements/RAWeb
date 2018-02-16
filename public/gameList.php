<?php
require_once __DIR__ . '/../lib/bootstrap.php';

$consoleList = getConsoleList();
$consoleIDInput = seekGET( 'c', 0 );
settype( $consoleIDInput, 'integer' );
$showCompleteGames = seekGET( 'f', 0 ); //	0 = no filter, 1 = only complete, 2 = only incomplete
settype( $showCompleteGames, 'integer' );

$sortBy = seekGET( 's', 0 );
$gamesCount = getGamesListWithNumAchievements( $consoleIDInput, $gamesList, $sortBy );

//echo $gamesCount;

$requestedConsole = "";
if( $consoleIDInput !== 0 )
{
    $requestedConsole = " " . $consoleList[ $consoleIDInput ];
}

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

$pageTitle = "Supported Games" . $requestedConsole;

$errorCode = seekGET( 'e' );
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
        <div id="leftcontainer">

            <?php
            echo "<div class=\"navpath\">";
            if( $requestedConsole == "" )
            {
                echo "<b>All Games</b>";
            }
            else //if( $requestedConsole != "" )
            {
                echo "<a href=\"/gameList.php\">All Games</a>";
                echo " &raquo; <b>$requestedConsole games</b></a>";
            }
            echo "</div>";

            echo "<div class='largelist'>";

            //	Output all console lists fetched
            foreach( $consoleList as $consoleID => $consoleName )
            {
                if( $consoleIDInput == 0 || $consoleIDInput == $consoleID )
                {
                    //	Cut out empty consoles:
                    $dataExists = FALSE;
                    foreach( $gamesList as $gameEntry )
                    {
                        if( $gameEntry[ 'ConsoleID' ] == $consoleID )
                        {
                            $dataExists = TRUE;
                            break;
                        }
                    }

                    if( $dataExists == FALSE )
                        continue;
                    //

                    echo "<h3 class='longheader'>$consoleName games with achievements:</h3>";
                    if( $showCompleteGames == 0 )
                        echo "<h4>All games</h4>";
                    else if( $showCompleteGames == 1 )
                        echo "<h4>Complete games</h4>";
                    else if( $showCompleteGames == 2 )
                        echo "<h4>Incomplete games</h4>";


                    if( $showCompleteGames != 0 )
                        echo "<a href='/gameList.php?c=$consoleIDInput&amp;f=0&amp;s=$sortBy'>Show All</a> | ";
                    else
                        echo "Show All | ";

                    if( $showCompleteGames != 1 )
                        echo "<a href='/gameList.php?c=$consoleIDInput&amp;f=1&amp;s=$sortBy'>Show Complete Only</a> | ";
                    else
                        echo "Show Complete Only | ";

                    if( $showCompleteGames != 2 )
                        echo "<a href='/gameList.php?c=$consoleIDInput&amp;f=2&amp;s=$sortBy'>Show Incomplete Only</a>";
                    else
                        echo "Show Incomplete Only";

                    echo "<table class='smalltable'><tbody>";

                    $sort1 = ($sortBy == 1) ? 11 : 1;
                    $sort2 = ($sortBy == 2) ? 12 : 2;
                    $sort3 = ($sortBy == 3) ? 13 : 3;
                    $sort4 = ($sortBy == 4) ? 14 : 4;

                    echo "<tr>";
                    echo "<th><a href='/gameList.php?s=$sort1&amp;c=$consoleIDInput'>Title</a></th>";
                    echo "<th class='smallthtitle'><a href='/gameList.php?s=$sort2&amp;c=$consoleIDInput'>Num Achieve-ments</a></th>";
                    echo "<th class='smallthtitle'><a href='/gameList.php?s=$sort3&amp;c=$consoleIDInput'>Points Available</a></th>";
                    echo "<th class='smallthtitle'><a href='/gameList.php?s=$sort4&amp;c=$consoleIDInput'>Leader-boards Available</a></th>";
                    echo "</tr>";
                    $gameCount = 0;

                    $pointsTally = 0;
                    $achievementsTally = 0;
                    $truePointsTally = 0;

                    $MaxGamePoints = 400;

                    foreach( $gamesList as $gameEntry )
                    {
                        if( $gameEntry[ 'ConsoleID' ] == $consoleID )
                        {
                            $title = $gameEntry[ 'Title' ];
                            $gameID = $gameEntry[ 'ID' ];
                            $maxPoints = $gameEntry[ 'MaxPointsAvailable' ];
                            $totalTrueRatio = $gameEntry[ 'TotalTruePoints' ];
                            $numAchievements = $gameEntry[ 'NumAchievements' ];
                            $numLBs = $gameEntry[ 'NumLBs' ];
                            $gameIcon = $gameEntry[ 'GameIcon' ];

                            if( $showCompleteGames == 1 && $maxPoints < $MaxGamePoints )
                                continue;

                            if( $showCompleteGames == 2 && $maxPoints >= $MaxGamePoints )
                                continue;

                            echo "<tr>";

                            echo "<td>";
                            echo GetGameAndTooltipDiv( $gameID, $title, $gameIcon, NULL );
                            echo "</td>";

                            echo "<td>$numAchievements</td>";
                            echo "<td>$maxPoints <span class='TrueRatio'>($totalTrueRatio)</span></td>";
                            if( $numLBs > 0 )
                                echo "<td><a href=\"game/$gameID\">$numLBs</a></td>";
                            else
                                echo "<td>-</td>";

                            echo "</tr>";

                            $pointsTally += $maxPoints;
                            $achievementsTally += $numAchievements;
                            $truePointsTally += $totalTrueRatio;
                        }
                    }

                    //	Totals:
                    echo "<tr>";

                    echo "<td><b>Totals:</b></td>";
                    echo "<td><b>$achievementsTally</b></td>";
                    echo "<td><b>$pointsTally</b><span class='TrueRatio'> ($truePointsTally)</span></td>";
                    echo "<td></td>";

                    echo "</tr>";

                    echo "</tbody></table>";
                }
            }

            /* if( $consoleIDInput !== 0 )
              {
              echo "<br/>";
              echo "<a href=\"/gameList.php\">View all consoles...</a><br/>";
              } */

            echo "</div>";
            ?>

            <br/>
        </div>

        <div id='rightcontainer'>
            <?php
            if( $user == NULL )
            {
                RenderTutorialComponent();
            }

            RenderScoreLeaderboardComponent( $user, $points, FALSE );

            if( $user !== NULL )
            {
                RenderScoreLeaderboardComponent( $user, $points, TRUE );
            }

            RenderRecentlyUploadedComponent( 10 );
            ?>
        </div>

    </div>

    <?php RenderFooter(); ?>

</body>
</html>