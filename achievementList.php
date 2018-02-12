<?php
require_once('db.inc.php');

$consoleList = getConsoleList();
$consoleIDInput = seekGET( 'z', 0 );
$mobileBrowser = IsMobileBrowser();

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

$maxCount = 25;

$count = seekGET( 'c', $maxCount );
settype( $params, 'integer' );
$offset = seekGET( 'o', 0 );
settype( $offset, 'integer' );
$params = seekGET( 'p', 0 );
settype( $params, 'integer' );

if( $user == NULL )
    $params = 0;

$flags = NULL;
if( $params != 0 )
    $flags = 3; //	If we are interrogating 'me/my achievements', only include completed ones

$sortBy = seekGET( 's', 1 );
$achCount = getAchievementsList( $consoleIDInput, $user, $sortBy, $params, $count, $offset, $achData, $flags );

//var_dump( $achData );

$requestedConsole = "";
if( $consoleIDInput !== 0 )
    $requestedConsole = " " . $consoleList[ $consoleIDInput ];

$pageTitle = "Achievement List" . $requestedConsole;

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

    <div id='mainpage'>
        <div id='leftcontainer'>
            <div class='left'>
                <?php
                echo "<div class='navpath'>";
                if( $requestedConsole == "" )
                    echo "<b>Achievement List</b>"; //	NB. This will be a stub page
                echo "</div>";

                echo "<div class='detaillist'>";

                echo "<h3 class='longheader'>Achievement List</h3>";

                echo "Showing:</br>";

                echo "&nbsp;- ";
                if( $params !== 0 )
                    echo "<a href='/achievementList.php?s=$sortBy&p=0'>";
                else
                    echo "<b>";
                echo "All achievements";
                if( $params !== 0 )
                    echo "</a>";
                else
                    echo "</b>";
                echo "<br/>";


                if( $user !== NULL )
                {
                    echo "&nbsp;- ";
                    if( $params !== 1 )
                        echo "<a href='/achievementList.php?s=$sortBy&p=1'>";
                    else
                        echo "<b>";
                    echo "Only my earned achievements";
                    if( $params !== 1 )
                        echo "</a>";
                    else
                        echo "</b>";
                    echo "<br/>";

                    echo "&nbsp;- ";
                    if( $params !== 2 )
                        echo "<a href='/achievementList.php?s=$sortBy&p=2'>";
                    else
                        echo "<b>";
                    echo "Achievements I haven't yet won";
                    if( $params !== 2 )
                        echo "</a>";
                    else
                        echo "</b>";
                    echo "<br/>";
                }

                echo "<div class='rightfloat'>* = ordered by</div>";

                echo "<table><tbody>";

                $sort1 = ($sortBy == 1) ? 11 : 1;
                $sort2 = ($sortBy == 2) ? 12 : 2;
                $sort3 = ($sortBy == 3) ? 13 : 3;
                $sort4 = ($sortBy == 4) ? 14 : 4;
                //$sort5 = ($sortBy==5) ? 15 : 5;
                $sort6 = ($sortBy == 6) ? 16 : 6;
                $sort7 = ($sortBy == 7) ? 17 : 7;

                $mark1 = ($sortBy % 10 == 1) ? '&nbsp;*' : '';
                $mark2 = ($sortBy % 10 == 2) ? '&nbsp;*' : '';
                $mark3 = ($sortBy % 10 == 3) ? '&nbsp;*' : '';
                $mark4 = ($sortBy % 10 == 4) ? '&nbsp;*' : '';
                //$mark5 = ($sortBy%10==5) ? '&nbsp;*' : '';
                $mark6 = ($sortBy % 10 == 6) ? '&nbsp;*' : '';
                $mark7 = ($sortBy % 10 == 7) ? '&nbsp;*' : '';

                echo "<th><a href='/achievementList.php?s=$sort1&p=$params'>Title</a>$mark1</th>";
                //if(!$mobileBrowser)
                echo "<th><a href='/achievementList.php?s=$sort2&p=$params'>Desc.</a>$mark2</th>";
                echo "<th><a href='/achievementList.php?s=$sort3&p=$params'>Points</a>$mark3 ";
                echo "<span class='TrueRatio'>(<a href='/achievementList.php?s=$sort4&p=$params'>Retro Ratio</a>$mark4)</span></th>";
                //if(!$mobileBrowser)
                //echo "<th><a href='/achievementList.php?s=$sort5&p=$params'>Author</a>$mark5</th>";
                echo "<th><a href='/achievementList.php?s=$sort6&p=$params'>Game</a>$mark6</th>";
                echo "<th><a href='/achievementList.php?s=$sort7&p=$params'>Added</a>$mark7</th>";

                $achCount = 0;

                foreach( $achData as $achEntry )
                {
                    //$query = "SELECT ach.ID, ach.Title AS AchievementTitle, ach.Description, ach.Points, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.GameID, gd.Title AS GameTitle, gd.ConsoleID, c.Name AS ConsoleName ";

                    $achID = $achEntry[ 'ID' ];
                    $achTitle = $achEntry[ 'AchievementTitle' ];
                    $achDesc = $achEntry[ 'Description' ];
                    $achPoints = $achEntry[ 'Points' ];
                    $achTruePoints = $achEntry[ 'TrueRatio' ];
                    $achAuthor = $achEntry[ 'Author' ];
                    $achDateCreated = $achEntry[ 'DateCreated' ];
                    $achDateModified = $achEntry[ 'DateModified' ];
                    $achBadgeName = $achEntry[ 'BadgeName' ];
                    $gameID = $achEntry[ 'GameID' ];
                    $gameIcon = $achEntry[ 'GameIcon' ];
                    $gameTitle = $achEntry[ 'GameTitle' ];
                    $consoleID = $achEntry[ 'ConsoleID' ];
                    $consoleName = $achEntry[ 'ConsoleName' ];

                    if( $achCount++ % 2 == 0 )
                        echo "<tr>";
                    else
                        echo "<tr class='alt'>";

                    echo "<td style='min-width:25%'>";
                    echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, TRUE );
                    echo "</td>";

                    //if(!$mobileBrowser)
                    {
                        //echo "<td style='min-width:25%'>";
                        echo "<td>";
                        echo "$achDesc";
                        echo "</td>";
                    }

                    echo "<td>";
                    echo "$achPoints ";
                    echo "<span class='TrueRatio'>($achTruePoints)</span>";
                    echo "</td>";

                    //if(!$mobileBrowser)
                    //{
                    //	echo "<td>";
                    //	echo GetUserAndTooltipDiv( $achAuthor, NULL, NULL, NULL, NULL, TRUE );
                    //	echo "</td>";
                    //}

                    echo "<td>";
                    echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName, TRUE, 32 );
                    echo "</td>";

                    echo "<td>";
                    echo "<span class='smalldate'>" . getNiceDate( strtotime( $achDateCreated ) ) . "</span>";
                    echo "</td>";

                    echo "</tr>";
                }

                echo "</tbody></table>";
                echo "</div>";

                echo "<div class='rightalign row'>";
                if( $offset > 0 )
                {
                    $prevOffset = $offset - $maxCount;
                    echo "<a href='/achievementList.php?s=$sortBy&amp;o=$prevOffset&amp;p=$params'>&lt; Previous $maxCount</a> - ";
                }
                if( $achCount == $maxCount )
                {
                    //	Max number fetched, i.e. there are more. Can goto next 25.
                    $nextOffset = $offset + $maxCount;
                    echo "<a href='/achievementList.php?s=$sortBy&amp;o=$nextOffset&amp;p=$params'>Next $maxCount &gt;</a>";
                }
                echo "</div>";
                ?>
                <br/>
            </div>
        </div>

        <div id='rightcontainer'>
<?php RenderRecentlyUploadedComponent( 10 ); ?>
        </div>

    </div>

<?php RenderFooter(); ?>

</body>
</html>

