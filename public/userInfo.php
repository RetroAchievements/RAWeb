<?php

use RA\Permissions;

require_once __DIR__ . '/../lib/bootstrap.php';

$userPage = strip_tags(seekGET('ID'));
if ($userPage == null || strlen($userPage) == 0) {
    header("Location: " . getenv('APP_URL'));
    exit;
}

if (ctype_alnum($userPage) == false) {
    //  NB. this is triggering for odd reasons? Why would a non-user hit this page?
    header("Location: " . getenv('APP_URL'));
    exit;
}

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$maxNumGamesToFetch = seekGET('g', 5);

//    Get general info
getUserPageInfo($userPage, $userMassData, $maxNumGamesToFetch, 100, $user);

$userMotto = $userMassData['Motto'];
$userPageID = $userMassData['ID'];
$userTruePoints = $userMassData['TotalTruePoints'];
$userRank = $userMassData['Rank'];
$userWallActive = $userMassData['UserWallActive'];
$userIsUntracked = $userMassData['Untracked'];

//    Get wall
$numArticleComments = getArticleComments(3, $userPageID, 0, 100, $commentData);

//    Get user's feed
//$numFeedItems = getFeed( $userPage, 20, 0, $feedData, 0, 'individual' );
//    Get user's site awards

$userAwards = getUsersSiteAwards($userPage);

//var_dump( $userAwards );
//    Find out which games are causing 'invalid' or out of date site awards for completed games
//var_dump( $userAwards );
//    Calc avg pcts:
$totalPctWon = 0.0;
$numGamesFound = 0;

$userCompletedGames = Array();

//    Get user's list of played games and pct completion
$userCompletedGamesList = getUsersCompletedGamesAndMax($userPage);
//var_dump( $userCompletedGamesList );
//
//    Merge all elements of $userCompletedGamesList into one unique list
for ($i = 0; $i < count($userCompletedGamesList); $i++) {
    $gameID = $userCompletedGamesList[$i]['GameID'];

    if ($userCompletedGamesList[$i]['HardcoreMode'] == 0) {
        $userCompletedGames[$gameID] = $userCompletedGamesList[$i];
    }

    $userCompletedGames[$gameID]['NumAwardedHC'] = 0; //    Update this later, but fill in for now
}

for ($i = 0; $i < count($userCompletedGamesList); $i++) {
    $gameID = $userCompletedGamesList[$i]['GameID'];
    if ($userCompletedGamesList[$i]['HardcoreMode'] == 1) {
        $userCompletedGames[$gameID]['NumAwardedHC'] = $userCompletedGamesList[$i]['NumAwarded'];
    }
}
//var_dump( $userCompletedGames );
//    Custom sort, then overwrite $userCompletedGamesList

function scorePctCompare($a, $b)
{
    if (empty($a['PctWon']) || empty($b['PctWon'])) {
        return 0;
    }
    return $a['PctWon'] < $b['PctWon'];
}

usort($userCompletedGames, "scorePctCompare");

$userCompletedGamesList = $userCompletedGames;

foreach ($userCompletedGamesList as $nextGame) {
    if ($nextGame['PctWon'] > 0) {
        $totalPctWon += $nextGame['PctWon'];
        $numGamesFound++;
    }
}

$avgPctWon = "0.0";
if ($numGamesFound > 0) {
    $avgPctWon = sprintf("%01.2f", ($totalPctWon / $numGamesFound) * 100.0);
}

//foreach( $userAwards as $nextKey => &$nextAward )
for ($i = 0; $i < count($userAwards); $i++) {
    $nextAward = $userAwards[$i];

    if ($nextAward['AwardType'] == 1) {
        $nextAward['Incomplete'] = 0;
        foreach ($userCompletedGamesList as $nextGame) {
            // if( $nextGame[ 'GameID' ] == $nextAward[ 'AwardData' ] )
            // {
            //    I have this game listed as a game I've got awards for, do I have the same number
            //     of completed awards as there are possible achievements?    //NB> FLAWED!!! DOESNT CATER FOR HARDCORE
            //if( $nextGame['NumAwarded'] < $nextGame['MaxPossible'] )
            //    $nextAward['Incomplete'] = 1;
            // }
        }
    }
}

settype($userMassData['Friendship'], 'integer');
settype($userMassData['FriendReciprocation'], 'integer');

$errorCode = seekGET('e');

getCookie($user, $cookie);

$pageTitle = "$userPage";

$userPagePoints = getScore($userPage);

$daysRecentProgressToShow = 14; //    fortnight

$userScoreData = getAwardedList($userPage, 0, 1000,
    date("Y-m-d H:i:s", time() - 60 * 60 * 24 * $daysRecentProgressToShow), date("Y-m-d H:i:s", time()));

//    Also add current.
$numScoreDataElements = count($userScoreData);
$userScoreData[$numScoreDataElements]['Year'] = date('Y');
$userScoreData[$numScoreDataElements]['Month'] = date('m');
$userScoreData[$numScoreDataElements]['Day'] = date('d');
$userScoreData[$numScoreDataElements]['Date'] = date("Y-m-d H:i:s");
$userScoreData[$numScoreDataElements]['Points'] = 0;
settype($userPagePoints, 'integer');
$userScoreData[$numScoreDataElements]['CumulScore'] = $userPagePoints;

$pointsReverseCumul = $userPagePoints;
for ($i = $numScoreDataElements; $i >= 0; $i--) {
    $pointsReverseCumul -= $userScoreData[$i]['Points'];
    $userScoreData[$i]['CumulScore'] = $pointsReverseCumul;
}

$numScoreDataElements++;

//var_dump( $userScoreData );

RenderDocType(true);
?>

<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">

        // Load the Visualization API and the piechart package.
        google.load('visualization', '1.0', {'packages': ['corechart']});

        // Set a callback to run when the Google Visualization API is loaded.
        google.setOnLoadCallback(drawCharts);

        // Callback that creates and populates a data table,
        // instantiates the pie chart, passes in the data and
        // draws it.
        function drawCharts() {
            var dataRecentProgress = new google.visualization.DataTable();

            // Declare columns
            dataRecentProgress.addColumn('date', 'Date');    //    NOT date! this is non-continuous data
            dataRecentProgress.addColumn('number', 'Score');

            dataRecentProgress.addRows([
                <?php
                $arrayToUse = $userScoreData;

                $count = 0;
                foreach ($arrayToUse as $dayInfo) {
                    if ($count++ > 0) {
                        echo ", ";
                    }

                    $nextDay = $dayInfo['Day'];
                    $nextMonth = $dayInfo['Month'];
                    $nextYear = $dayInfo['Year'];

                    $dateStr = "$nextDay/$nextMonth";
                    //if( $nextYear != date( 'Y' ) )
                    //    $dateStr = "$nextDay/$nextMonth/$nextYear";

                    $value = $dayInfo['CumulScore'];

                    //echo "[ {v:new Date($nextYear,$nextMonth,$nextDay), f:'$dateStr'}, $value ]";
                    echo "[ {v:new Date($nextYear,$nextMonth,$nextDay), f:'$dateStr'}, $value ]";
                }
                ?>
            ]);


            var optionsRecentProcess = {
                backgroundColor: 'transparent',
                title: 'Recent Progress',
                titleTextStyle: {color: '#186DEE'},
                hAxis: {textStyle: {color: '#186DEE'}, slantedTextAngle: 90},
                vAxis: {textStyle: {color: '#186DEE'}},
                legend: {position: 'none'},
                chartArea: {left: 42, width: 458, 'height': '100%'},
                showRowNumber: false,
                view: {columns: [0, 1]},
                //height: 460,
                colors: ['#cc9900']
            };

            function resize() {
                chartRecentProgress = new google.visualization.AreaChart(document.getElementById('chart_recentprogress'));
                chartRecentProgress.draw(dataRecentProgress, optionsRecentProcess);
            }

            window.onload = resize();
            window.onresize = resize;
        }
    </script>
    <?php RenderSharedHeader($user); ?>
    <?php RenderFBMetadata($userPage, "user", "/UserPic/$userPage" . ".png", "/User/$userPage",
        "User page for $userPage"); ?>
    <?php RenderTitleTag($pageTitle, $user); ?>
    <?php RenderGoogleTracking(); ?>
</head>

<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>

<div id="mainpage">
    <div id='leftcontainer'>
        <?php
        RenderErrorCodeWarning('left', $errorCode);

        echo "<div class='navpath'>";
        echo "<a href='/userList.php'>All Users</a>";
        echo " &raquo; <b>$userPage</b>";
        echo "</div>";

        echo "<div class='usersummary'>";
        echo "<h3 class='longheader' >$userPage's User Page</h3>";

        $totalPoints = $userMassData['TotalPoints'];
        $totalTruePoints = $userMassData['TotalTruePoints'];
        echo "<img src='/UserPic/$userPage.png' alt='$userPage' align='right' width='128' height='128'>";
        echo "<div class='username'>";
        echo "<span class='username'><a href='/User/$userPage'><strong>$userPage</strong></a>&nbsp;($totalPoints points)<span class='TrueRatio'> ($userTruePoints)</span></span>";
        echo "</div>"; //username

        if (isset($userMotto) && strlen($userMotto) > 1) {
            echo "<div class='mottocontainer'>";
            echo "<span class='usermotto'>$userMotto</span>";
            echo "</div>"; //mottocontainer
        }
        echo "<br/>";

        $niceDateJoined = $userMassData['MemberSince'] ? getNiceDate(strtotime($userMassData['MemberSince'])) : null;
        if ($niceDateJoined) {
            echo "Member Since: $niceDateJoined<br>";
        }
        // LastLogin is updated on any activity -> "LastActivity"
        $niceDateLogin = $userMassData['LastActivity'] ? getNiceDate(strtotime($userMassData['LastActivity'])) : null;
        if ($niceDateLogin) {
            echo "Last Activity: $niceDateLogin<br>";
        }
        echo "Account Type: <b>[" . PermissionsToString($userMassData['Permissions']) . "]</b><br/>";
        echo "<br/>";

        $retRatio = 0.0;
        if ($totalPoints > 0) {
            $retRatio = sprintf("%01.2f", $userTruePoints / $totalPoints);
        }
        echo "Retro Ratio: <span class='TrueRatio'><b>$retRatio</b></span><br/>";
        echo "Average Completion: <b>$avgPctWon%</b><br/>";

        echo "Site Rank: ";
        if ($userIsUntracked) {
            echo "<b>Untracked</b>";
        } elseif ($userTruePoints <= 0) {
            echo "<i>This user has not earned any points.</i>";
        } else {
            $countRankedUsers = countRankedUsers();
            $rankPct = sprintf("%1.0f", (($userRank / $countRankedUsers) * 100.0) + 1.0);
            echo "<a href='/userList.php?s=2'>$userRank</a> / $countRankedUsers ranked users (Top $rankPct%)";
        }
        echo "<br/><br/>";

        if (!empty($userMassData['RichPresenceMsg']) && $userMassData['RichPresenceMsg'] !== 'Unknown') {
            echo "<div class='mottocontainer'>Last seen ";
            if (!empty($userMassData['LastGameID'])) {
                $game = getGameData($userMassData['LastGameID']);
                echo ' in ' . GetGameAndTooltipDiv($game['ID'], $game['Title'], $game['ImageIcon'], null, false,
                        22) . '<br>';
            }
            echo "<code>" . $userMassData['RichPresenceMsg'] . "</code></div>";
        }

        $contribCount = $userMassData['ContribCount'];
        $contribYield = $userMassData['ContribYield'];
        if ($contribCount > 0) {
            echo "<strong>$userPage Developer Stats:</strong><br/>";
            echo "<a href='/gameList.php?d=$userPage'>View all achievements sets <b>$userPage</b> has worked on.</a><br/>";
            echo "<a href='/ticketmanager.php?u=$userPage'>Open Tickets: <b>" . countOpenTicketsByDev($userPage) . "</b></a><br>";
            echo "Achievements won by others: <b>$contribCount</b><br>";
            echo "Points awarded to others: <b>$contribYield</b><br>";
        }

        echo "</div>"; //usersummary

        if (isset($user) && ($user !== $userPage)) {
            echo "<div class='friendbox'>";
            echo "<div class='buttoncollection'>";
            //echo "<h4>Friend Actions:</h4>";

            if ($userMassData['Friendship'] == 1) {
                if ($userMassData['FriendReciprocation'] == 1) {
                    echo "<span class='clickablebutton'><a href='/requestchangefriend.php?u=$user&amp;c=$cookie&amp;f=$userPage&amp;a=0'>Remove friend</a></span>";
                } elseif ($userMassData['FriendReciprocation'] == 0) {
                    //    They haven't accepted yet
                    echo "<span class='clickablebutton'><a href='/requestchangefriend.php?u=$user&amp;c=$cookie&amp;f=$userPage&amp;a=0'>Cancel friend request</a></span>";
                } elseif ($userMassData['FriendReciprocation'] == -1) {
                    //    They blocked us
                    echo "<span class='clickablebutton'><a href='/requestchangefriend.php?u=$user&amp;c=$cookie&amp;f=$userPage&amp;a=0'>Remove friend</a></span>";
                }
            } elseif ($userMassData['Friendship'] == 0) {
                if ($userMassData['FriendReciprocation'] == 1) {
                    echo "<span class='clickablebutton'><a href='/requestchangefriend.php?u=$user&amp;c=$cookie&amp;f=$userPage&amp;a=1'>Confirm friend request</a></span>";
                } elseif ($userMassData['FriendReciprocation'] == 0) {
                    echo "<span class='clickablebutton'><a href='/requestchangefriend.php?u=$user&amp;c=$cookie&amp;f=$userPage&amp;a=1'>Add friend</a></span>";
                }
            }

            if ($userMassData['Friendship'] !== -1) {
                echo "<span class='clickablebutton'><a href='/requestchangefriend.php?u=$user&amp;c=$cookie&amp;f=$userPage&amp;a=-1'>Block user</a></span>";
            } else //if( $userMassData['Friendship'] == -1 )
            {
                echo "<span class='clickablebutton'><a href='/requestchangefriend.php?u=$user&amp;c=$cookie&amp;f=$userPage&amp;a=0'>Unblock user</a></span>";
            }

            echo "<span class='clickablebutton'><a href='/createmessage.php?t=$userPage'>Send Private Message</a></span>";

            echo "</div>"; //    buttoncollection
            echo "</div>"; //    friendbox
        }

        if (isset($user) && $permissions >= Permissions::Admin) {
            echo "<div class='devbox'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Admin (Click to show):</span><br/>";
            echo "<div id='devboxcontent'>";

            if ($permissions >= $userMassData['Permissions'] && ($user != $userPage)) {
                echo "<li>Update Account Type:</li>";
                echo "<form method='post' action='/requestupdateuser.php' enctype='multipart/form-data'>";
                echo "<input type='hidden' name='p' value='0' />";
                echo "<input type='hidden' name='t' value='$userPage' />";

                echo "<select name='v' >";
                $i = Permissions::Banned;
                //    NB. Only I can authorise changing to Admin
                //    Don't do this, looks weird when trying to change someone above you
                //while( $i <= $permissions && ( $i <= \RA\Permissions::Developer || $user == 'Scott' ) )
                while ($i <= $permissions) {
                    if ($userMassData['Permissions'] == $i) {
                        echo "<option value='$i' selected >($i): " . PermissionsToString($i) . " (current)</option>";
                    } else {
                        echo "<option value='$i'>($i): " . PermissionsToString($i) . "</option>";
                    }
                    $i++;
                }
                echo "</select>";

                echo "&nbsp;<input type='submit' style='float: right;' value='Do it!' /></br></br>";
                echo "<div style='clear:all;'></div>";
                echo "</form><br/>";
            }

            if (isset($user) && $permissions >= Permissions::Root) {
                //  Me only
                echo "<form method='post' action='/requestupdateuser.php' enctype='multipart/form-data'>";
                echo "<input type='hidden' name='p' value='2' />";
                echo "<input type='hidden' name='t' value='$userPage' />";
                echo "<input type='hidden' name='v' value='0' />";
                echo "&nbsp;<input type='submit' style='float: right;' value='Toggle Patreon Supporter' /></br></br>";
                echo "<div style='clear:all;'></div>";
                echo "</form>";
            }

            if (isset($user) && $permissions >= Permissions::Admin) {
                echo "<form method='post' action='/requestscorerecalculation.php' enctype='multipart/form-data'>";
                echo "<input TYPE='hidden' NAME='u' VALUE='$userPage' />";
                echo "&nbsp;<input type='submit' style='float: right;' value='Recalc Score Now' /></br></br>";
                echo "<div style='clear:all;'></div>";
                echo "</form>";

                //$userIsUntracked
                echo ($userIsUntracked == 1) ? "<b>Untracked User!</b>&nbsp;" : "Tracked User.&nbsp;";
                $newValue = $userIsUntracked ? 0 : 1;
                echo "<form method='post' action='/requestupdateuser.php' enctype='multipart/form-data'>";
                echo "<input TYPE='hidden' NAME='p' VALUE='3' />";
                echo "<input TYPE='hidden' NAME='t' VALUE='$userPage' />";
                echo "<input TYPE='hidden' NAME='v' VALUE='$newValue' />";
                echo "&nbsp;<input type='submit' style='float: right;' value='Toggle Tracked Status' /></br></br>";
                echo "<div style='clear:all;'></div>";
                echo "</form>";
            }

            echo "</div>"; //devboxcontent

            echo "</div>"; //devbox
        }

        echo "<div class='userpage recentlyplayed' >";

        $recentlyPlayedCount = $userMassData['RecentlyPlayedCount'];

        //var_dump( $userMassData[ 'RecentlyPlayed' ] );
        //error_log( print_r( $userMassData[ 'Awarded' ], true ) );      //a, empty

        echo "<h4>Last $recentlyPlayedCount games played:</h4>";
        for ($i = 0; $i < $recentlyPlayedCount; $i++) {
            $gameID = $userMassData['RecentlyPlayed'][$i]['GameID'];
            $consoleID = $userMassData['RecentlyPlayed'][$i]['ConsoleID'];
            $consoleName = $userMassData['RecentlyPlayed'][$i]['ConsoleName'];
            $gameTitle = $userMassData['RecentlyPlayed'][$i]['Title'];
            $gameLastPlayed = $userMassData['RecentlyPlayed'][$i]['LastPlayed'];

            $pctAwarded = 100.0;

            if (isset($userMassData['Awarded'][$gameID])) {
                $numPossibleAchievements = $userMassData['Awarded'][$gameID]['NumPossibleAchievements'];
                $maxPossibleScore = $userMassData['Awarded'][$gameID]['PossibleScore'];
                $numAchieved = $userMassData['Awarded'][$gameID]['NumAchieved'];
                $scoreEarned = $userMassData['Awarded'][$gameID]['ScoreAchieved'];
                $numAchievedHardcore = $userMassData['Awarded'][$gameID]['NumAchievedHardcore'];
                $scoreEarnedHardcore = $userMassData['Awarded'][$gameID]['ScoreAchievedHardcore'];

                settype($numPossibleAchievements, "integer");
                settype($maxPossibleScore, "integer");
                settype($numAchieved, "integer");
                settype($scoreEarned, "integer");
                settype($numAchievedHardcore, "integer");
                settype($scoreEarnedHardcore, "integer");

                echo "<div class='userpagegames'>";

                $pctAwardedCasual = "0";
                $pctAwardedHardcore = "0";
                $pctComplete = "0";

                if ($numPossibleAchievements > 0) {
                    $pctAwardedCasualVal = $numAchieved / $numPossibleAchievements;

                    $pctAwardedHardcoreProportion = 0;
                    if ($numAchieved > 0) {
                        $pctAwardedHardcoreProportion = $numAchievedHardcore / $numAchieved;
                    }

                    $pctAwardedCasual = sprintf("%01.0f", $pctAwardedCasualVal * 100.0);
                    $pctAwardedHardcore = sprintf("%01.0f", $pctAwardedHardcoreProportion * 100.0);
                    $pctComplete = sprintf("%01.0f",
                        (($numAchieved + $numAchievedHardcore) * 100.0 / $numPossibleAchievements));
                }

                echo "<div class='progressbar'>";
                echo "<div class='completion'             style='width:$pctAwardedCasual%'>";
                echo "<div class='completionhardcore'     style='width:$pctAwardedHardcore%'>";
                echo "&nbsp;";
                echo "</div>";
                echo "</div>";
                if ($pctComplete > 100.0) {
                    echo "<b>$pctComplete%</b> complete<br/>";
                } else {
                    echo "$pctComplete% complete<br/>";
                }
                echo "</div>";

                echo "<a href='/Game/$gameID'>$gameTitle ($consoleName)</a><br/>";
                echo "Last played $gameLastPlayed<br/>";
                echo "Earned $numAchieved of $numPossibleAchievements achievements, $scoreEarned/$maxPossibleScore points.<br/>";

                //var_dump( $userMassData[ 'RecentAchievements' ] );

                if (isset($userMassData['RecentAchievements'][$gameID])) {
                    foreach ($userMassData['RecentAchievements'][$gameID] as $achID => $achData) {
                        $badgeName = $achData['BadgeName'];
                        $achID = $achData['ID'];
                        $achPoints = $achData['Points'];
                        $achTitle = $achData['Title'];
                        $achDesc = $achData['Description'];
                        $achUnlockDate = getNiceDate(strtotime($achData['DateAwarded']));
                        $achHardcore = $achData['HardcoreAchieved'];
                        //var_dump( $achData );

                        $unlockedStr = "";
                        $class = 'badgeimglarge';

                        if (!$achData['IsAwarded']) {
                            $badgeName .= "_lock";
                        } else {
                            $unlockedStr = "<br clear=all>Unlocked: $achUnlockDate";
                            if ($achHardcore == 1) {
                                $unlockedStr .= "</br>-=HARDCORE=-";
                                $class = 'goldimage';
                            }
                        }

                        echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle,
                            $badgeName, true, true, $unlockedStr, 48, $class);
                        //echo "<a href='/Achievement/$achID'><img class='$class' src='" . getenv('APP_STATIC_URL') . "/Badge/$badgeName.png' title='$achTitle ($achPoints) - $achDesc$unlockedStr' width='48' height='48'></a>";
                    }
                }

                echo "</div>";
            }

            echo "<br/>";
        }

        if ($maxNumGamesToFetch == 5 && $recentlyPlayedCount == 5) {
            echo "<div class='rightalign'><a href='/User/$userPage&g=15'>more...</a></div><br/>";
        }

        echo "</div>"; //recentlyplayed

        echo "<div class='commentscomponent left'>";

        if ($userWallActive) {
            echo "<h4>User Wall</h4>";
            $forceAllowDeleteComments = $permissions >= Permissions::Admin;
            RenderCommentsComponent($user, $numArticleComments, $commentData, $userPageID, 3,
                $forceAllowDeleteComments);
        }

        echo "</div>";
        ?>
    </div>

    <div id='rightcontainer'>
        <?php
        RenderSiteAwards($userAwards);
        RenderCompletedGamesList($userPage, $userCompletedGamesList);

        echo "<div id='achdistribution' class='component' >";
        echo "<h3>Recent Progress</h3>";
        echo "<div id='chart_recentprogress'></div>";
        echo "<div class='rightalign'><a href='/history.php?u=$userPage'>more...</a></div>";
        echo "</div>";

        if ($user !== null) {
            RenderScoreLeaderboardComponent($user, $points, true);
        }
        ?>

    </div>

</div>

<?php RenderFooter(); ?>

</body>
</html>
