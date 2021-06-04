<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

use RA\Permissions;

/*
  DONT FORGET! All URLS within Game, User or Achievement MUST have an extra forward slash
  as they are all in a pseudo-directory of /game etc.
 */

$gameID = requestInputSanitized('ID', null, 'integer');
if ($gameID == null || $gameID == 0) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

$friendScores = [];
if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, null, $userID)) {
    getAllFriendsProgress($user, $gameID, $friendScores);
}

$errorCode = requestInputSanitized('e');

$officialFlag = 3; // flag = 3: Core (official) achievements
$unofficialFlag = 5; // flag = 5: unofficial
$flags = requestInputSanitized('f', $officialFlag, 'integer');

$defaultSort = 1;
if (isset($user)) {
    $defaultSort = 13;
}
$sortBy = requestInputSanitized('s', $defaultSort, 'integer');

if (!isset($user) && ($sortBy == 3 || $sortBy == 13)) {
    $sortBy = 1;
}

$numAchievements = getGameMetadataByFlags($gameID, $user, $achievementData, $gameData, $sortBy, null, $flags);

if (!isset($gameData)) {
    echo "Invalid game ID!";
    exit;
}

$gameTitle = $gameData['Title'];
$consoleName = $gameData['ConsoleName'];
$consoleID = $gameData['ConsoleID'];
$forumTopicID = $gameData['ForumTopicID'];
$richPresenceData = $gameData['RichPresencePatch'];

// Entries that aren't actual game only have alternatives exposed, e.g. hubs.
$isFullyFeaturedGame = !in_array($consoleName, ['Hubs']);

$pageTitle = "$gameTitle ($consoleName)";

$gameAlts = getGameAlternatives($gameID);

$achDist = null;
$authorInfo = null;
$commentData = null;
$cookie = null;
$gameLatestMasters = null;
$gameTopAchievers = null;
$lbData = null;
$numArticleComments = null;
$numDistinctPlayersCasual = null;
$numEarnedCasual = null;
$numEarnedHardcore = null;
$numLeaderboards = null;
$screenshotMaxHeight = null;
$screenshotWidth = null;
$totalEarnedCasual = null;
$totalEarnedHardcore = null;
$totalEarnedTrueRatio = null;
$totalPossible = null;
$totalPossibleTrueRatio = null;

if ($isFullyFeaturedGame) {
    $numDistinctPlayersCasual = $gameData['NumDistinctPlayersCasual'];
    $numDistinctPlayersHardcore = $gameData['NumDistinctPlayersHardcore'];
    if ($numDistinctPlayersCasual == 0) {
        $numDistinctPlayersCasual = 1;
    }
    if ($numDistinctPlayersHardcore == 0) {
        $numDistinctPlayersHardcore = 1; //??
    }

    $totalUniquePlayers = getTotalUniquePlayers($gameID, $user);
    if ($numDistinctPlayersCasual < $totalUniquePlayers) {
        $numDistinctPlayersCasual = $totalUniquePlayers;
    }
    if ($numDistinctPlayersHardcore < $totalUniquePlayers) {
        $numDistinctPlayersHardcore = $totalUniquePlayers;
    }

    $achDist = getAchievementDistribution($gameID, 0, $user, $flags); // for now, only retrieve casual!
    for ($i = 1; $i <= $numAchievements; $i++) {
        if (!array_key_exists($i, $achDist)) {
            $achDist[$i] = 0;
        }
    }

    ksort($achDist);

    $numArticleComments = getArticleComments(1, $gameID, 0, 20, $commentData);

    getCookie($user, $cookie);

    $numLeaderboards = getLeaderboardsForGame($gameID, $lbData, $user);

    $screenshotWidth = 200;
    $screenshotMaxHeight = 240; // corresponds to the DS screen aspect ratio

    // Quickly calculate earned/potential
    $totalEarnedCasual = 0;
    $totalEarnedHardcore = 0;
    $numEarnedCasual = 0;
    $numEarnedHardcore = 0;
    $totalPossible = 0;

    $totalEarnedTrueRatio = 0;
    $totalPossibleTrueRatio = 0;

    $authorName = [];
    $authorCount = [];
    if (isset($achievementData)) {
        foreach ($achievementData as &$nextAch) {
            // Add author to array if it's not already there and initialize achievement count for that author.
            if (!in_array($nextAch['Author'], $authorName)) {
                $authorName[mb_strtolower($nextAch['Author'])] = $nextAch['Author'];
                $authorCount[mb_strtolower($nextAch['Author'])] = 1;
            } // If author is already in array then increment the achievement count for that author.
            else {
                $authorCount[mb_strtolower($nextAch['Author'])]++;
            }

            $totalPossible += $nextAch['Points'];
            $totalPossibleTrueRatio += $nextAch['TrueRatio'];

            if (isset($nextAch['DateEarned'])) {
                $numEarnedCasual++;
                $totalEarnedCasual += $nextAch['Points'];
                $totalEarnedTrueRatio += $nextAch['TrueRatio'];
            }
            if (isset($nextAch['DateEarnedHardcore'])) {
                $numEarnedHardcore++;
                $totalEarnedHardcore += $nextAch['Points'];
            }
        }
        // Combine arrays and sort by achievement count.
        $authorInfo = array_combine($authorName, $authorCount);
        array_multisort($authorCount, SORT_DESC, $authorInfo);
    }

    //Get the top ten players at this game:
    $gameTopAchievers = getGameTopAchievers($gameID, 0, 10, $user, 0);
    $gameLatestMasters = getGameTopAchievers($gameID, 0, 10, $user, 1);
}

sanitize_outputs(
    $gameTitle,
    $consoleName,
    $richPresenceData,
    $pageTitle,
    $user,
);

RenderHtmlStart(true);
?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php if ($isFullyFeaturedGame): ?>
        <?php RenderOpenGraphMetadata($pageTitle, "game", $gameData['ImageIcon'], "/game/$gameID", "Game Info for $gameTitle ($consoleName)"); ?>
    <?php endif ?>
    <?php RenderTitleTag($pageTitle); ?>
    <?php RenderGoogleTracking(); ?>
</head>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<?php if ($isFullyFeaturedGame): ?>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.load('visualization', '1.0', {'packages': ['corechart']});
      google.setOnLoadCallback(drawCharts);

      function drawCharts() {
        var dataTotalScore = new google.visualization.DataTable();

        // Declare columns
        dataTotalScore.addColumn('number', 'Total Achievements Won');
        dataTotalScore.addColumn('number', 'Num Users');

        dataTotalScore.addRows([
            <?php
            $largestWonByCount = 0;
            $count = 0;
            for ($i = 1; $i <= $numAchievements; $i++) {
                if ($count++ > 0) {
                    echo ", ";
                }
                $wonByUserCount = $achDist[$i];

                if ($wonByUserCount > $largestWonByCount) {
                    $largestWonByCount = $wonByUserCount;
                }

                echo "[ {v:$i, f:\"Earned $i achievement(s)\"}, $wonByUserCount ] ";
            }

            if ($largestWonByCount > 30) {
                $largestWonByCount = -2;
            }
            ?>
        ]);
          <?php $numGridlines = $numAchievements; ?>
        var optionsTotalScore = {
          backgroundColor: 'transparent',
          //title: 'Achievement Distribution',
          titleTextStyle: {color: '#186DEE'}, //cc9900
          hAxis: {textStyle: {color: '#186DEE'}, gridlines: {count:<?php echo $numGridlines; ?>, color: '#334433'}, minorGridlines: {count: 0}, format: '#', slantedTextAngle: 90, maxAlternation: 0},
          vAxis: {textStyle: {color: '#186DEE'}, gridlines: {count:<?php echo $largestWonByCount + 1; ?>}, viewWindow: {min: 0}, format: '#'},
          legend: {position: 'none'},
          chartArea: {'width': '85%', 'height': '78%'},
          height: 260,
          colors: ['#cc9900'],
          pointSize: 4,
        };

        function resize() {
          chartScoreProgress = new google.visualization.AreaChart(document.getElementById('chart_distribution'));
          chartScoreProgress.draw(dataTotalScore, optionsTotalScore);
          //google.visualization.events.addListener(chartScoreProgress, 'select', selectHandlerScoreProgress );
        }

        window.onload = resize();
        window.onresize = resize;
      }
    </script>
    <script>
      var lastKnownAchRating = 0;
      var lastKnownGameRating = 0;

      function SetLitStars(container, numStars) {
        $(container + ' a').removeClass('starlit');
        $(container + ' a').removeClass('starhalf');

        if (numStars >= 0.5)
          $(container + ' a:first-child').addClass('starhalf');
        if (numStars >= 1.5)
          $(container + ' a:first-child + a').addClass('starhalf');
        if (numStars >= 2.5)
          $(container + ' a:first-child + a + a').addClass('starhalf');
        if (numStars >= 3.5)
          $(container + ' a:first-child + a + a + a').addClass('starhalf');
        if (numStars >= 4.5)
          $(container + ' a:first-child + a + a + a + a').addClass('starhalf');

        if (numStars >= 1) {
          $(container + ' a:first-child').removeClass('starhalf');
          $(container + ' a:first-child').addClass('starlit');
        }
        if (numStars >= 2) {
          $(container + ' a:first-child + a').removeClass('starhalf');
          $(container + ' a:first-child + a').addClass('starlit');
        }

        if (numStars >= 3) {
          $(container + ' a:first-child + a + a').removeClass('starhalf');
          $(container + ' a:first-child + a + a').addClass('starlit');
        }

        if (numStars >= 4) {
          $(container + ' a:first-child + a + a + a').removeClass('starhalf');
          $(container + ' a:first-child + a + a + a').addClass('starlit');
        }

        if (numStars >= 5) {
          $(container + ' a:first-child + a + a + a + a').removeClass('starhalf');
          $(container + ' a:first-child + a + a + a + a').addClass('starlit');
        }
      }

      function GetRating(gameID) {

        $('#ratinggame a').removeClass('starlit');
        $('#ratingach a').removeClass('starlit');

        $('.ratinggamelabel').html('Rating: ...');
        $('.ratingachlabel').html('Rating: ...');

        $.ajax({
          url: '/request/game/rating.php?i=' + gameID,
          dataType: 'json',
          success: function (results) {
            results.GameID;
            lastKnownGameRating = parseFloat(results.Ratings['Game']);
            lastKnownAchRating = parseFloat(results.Ratings['Achievements']);
            var gameRatingNumVotes = results.Ratings['GameNumVotes'];
            var achRatingNumVotes = results.Ratings['AchievementsNumVotes'];

            SetLitStars('#ratinggame', lastKnownGameRating);
            SetLitStars('#ratingach', lastKnownAchRating);

            $('.ratinggamelabel').html('Rating: ' + lastKnownGameRating.toFixed(2) + ' (' + gameRatingNumVotes + ' votes)');
            $('.ratingachlabel').html('Rating: ' + lastKnownAchRating.toFixed(2) + ' (' + achRatingNumVotes + ' votes)');

          },
          error: function (temp, temp1, temp2) {
            alert('Error ' + temp + temp1 + temp2);
          },
        });
      }

      function SubmitRating(gameID, ratingObjectType, value) {
        $.ajax({
          url: '/request/game/update-rating.php?i=' + gameID + '&t=' + ratingObjectType + '&v=' + value,
          dataType: 'json',
          success: function (results) {
            GetRating(<?php echo $gameID; ?>);
          },
          error: function (temp, temp1, temp2) {
            alert('Error ' + temp + temp1 + temp2);
          },
        });
      }

      // Onload:
      $(function () {

        // Add these handlers onload, they don't exist yet
        $('.starimg').hover(
          function () {
            //	On hover

            if ($(this).parent().is($('#ratingach'))) {
              //	Ach:
              var numStars = 0;
              if ($(this).hasClass('1star'))
                numStars = 1;
              else if ($(this).hasClass('2star'))
                numStars = 2;
              else if ($(this).hasClass('3star'))
                numStars = 3;
              else if ($(this).hasClass('4star'))
                numStars = 4;
              else if ($(this).hasClass('5star'))
                numStars = 5;

              SetLitStars('#ratingach', numStars);
            } else {
              //	Game:
              var numStars = 0;
              if ($(this).hasClass('1star'))
                numStars = 1;
              else if ($(this).hasClass('2star'))
                numStars = 2;
              else if ($(this).hasClass('3star'))
                numStars = 3;
              else if ($(this).hasClass('4star'))
                numStars = 4;
              else if ($(this).hasClass('5star'))
                numStars = 5;

              SetLitStars('#ratinggame', numStars);
            }
          },
          function () {
            // On leave
            //GetRating( <?php echo $gameID; ?> );
          });

        $('.rating').hover(
          function () {
            // On hover
          },
          function () {
            // On leave
            //GetRating( <?php echo $gameID; ?> );
            if ($(this).is($('#ratingach')))
              SetLitStars('#ratingach', lastKnownAchRating);
            else
              SetLitStars('#ratinggame', lastKnownGameRating);
          });

        $('.starimg').click(function () {

          var numStars = 0;
          if ($(this).hasClass('1star'))
            numStars = 1;
          else if ($(this).hasClass('2star'))
            numStars = 2;
          else if ($(this).hasClass('3star'))
            numStars = 3;
          else if ($(this).hasClass('4star'))
            numStars = 4;
          else if ($(this).hasClass('5star'))
            numStars = 5;

          var ratingType = 1;
          if ($(this).parent().is($('#ratingach')))
            ratingType = 3;

          SubmitRating(<?php echo $gameID; ?>, ratingType, numStars);
        });

        if ($('.rating').length) {
          GetRating(<?php echo $gameID; ?>);
        }

      });

      /**
       * Displays set request information
       */
      function getSetRequestInformation(user, gameID) {
        $.ajax(
          {
            url: '/request/set-request/list.php?i=' + gameID + '&u=' + user,
            dataType: 'json',
            success: function (results) {
              var remaining = parseInt(results.remaining);
              var gameTotal = parseInt(results.gameRequests);
              var thisGame = results.requestedThisGame;

              $('.gameRequestsLabel').html('Set Requests: <a href=\'/setRequestors.php?g=' + gameID + '\'>' + gameTotal + '</a>');
              $('.userRequestsLabel').html('User Requests Remaining: <a href=\'/setRequestList.php?u=' + user + '\'>' + remaining + '</a>');

              //If the user has not requested a set for this game
              if (thisGame == 0) {
                if (remaining <= 0) {
                  $('.setRequestLabel').html('<h4>No Requests Remaining</h4>');

                  //Remove clickable text
                  $('.setRequestLabel').each(function () {
                    $('<h4>' + $(this).html() + '</h4>').replaceAll(this);
                  });
                } else {
                  $('.setRequestLabel').html('<h4>Request Set</h4>');
                }
              } else {
                $('.setRequestLabel').html('<h4>Withdraw Request</h4>');
              }

            },
            error: function (temp, temp1, temp2) {
              alert('Error ' + temp + temp1 + temp2);
            },
          });
      }

      /**
       * Submits a set requets
       */
      function submitSetRequest(user, gameID) {
        $.ajax(
          {
            url: '/request/set-request/update.php?i=' + gameID,
            dataType: 'json',
            success: function (results) {
              getSetRequestInformation('<?php echo $user; ?>', <?php echo $gameID; ?>);
            },
            error: function (temp, temp1, temp2) {
              alert('Error ' + temp + temp1 + temp2);
            },
          });
      }

      $(function () {
        $('.setRequestLabel').click(function () {
          submitSetRequest('<?php echo $user; ?>', <?php echo $gameID; ?>);
        });

        if ($('.setRequestLabel').length) {
          getSetRequestInformation('<?php echo $user; ?>', <?php echo $gameID; ?>);
        }

      });
    </script>
<?php endif ?>
<div id="mainpage">
    <div id="<?= $isFullyFeaturedGame ? 'leftcontainer' : 'fullcontainer' ?>">
        <?php RenderErrorCodeWarning($errorCode); ?>
        <div id="achievement">
            <?php
            sanitize_outputs(
                $developer,
                $publisher,
                $genre,
                $released,
            );

            if ($isFullyFeaturedGame) {
                echo "<div class='navpath'>";
                echo "<a href='/gameList.php'>All Games</a>";
                echo " &raquo; <a href='/gameList.php?c=$consoleID'>$consoleName</a>";
                if ($flags == $unofficialFlag) {
                    echo " &raquo; <a href='/game/$gameID'>$gameTitle</a>";
                    echo " &raquo; <b>Unofficial Achievements</b>";
                } else {
                    echo " &raquo; <b>$gameTitle</b>";
                }
                echo "</div>";
            }

            $developer = $gameData['Developer'] ?? null;
            $publisher = $gameData['Publisher'] ?? null;
            $genre = $gameData['Genre'] ?? null;
            $released = $gameData['Released'] ?? null;
            $imageIcon = $gameData['ImageIcon'];
            $imageTitle = $gameData['ImageTitle'];
            $imageIngame = $gameData['ImageIngame'];

            echo "<h3 class='longheader'>$pageTitle</h3>";
            echo "<table><tbody>";
            echo "<tr>";
            echo "<td style='width:110px; padding: 7px' ><img src='$imageIcon' title='$pageTitle' width='96' height='96'></td>";
            echo "<td>";
            echo "<table class='gameinfo'><tbody>";
            if ($developer) {
                echo "<tr>";
                echo "<td>Developer:</td>";
                echo "<td><b>$developer</b></td>";
                echo "</tr>";
            }
            if ($publisher) {
                echo "<tr>";
                echo "<td>Publisher:</td>";
                echo "<td><b>$publisher</b></td>";
                echo "</tr>";
            }
            if ($genre) {
                echo "<tr>";
                echo "<td>Genre:</td>";
                echo "<td><b>$genre</b></td>";
                echo "</tr>";
            }
            if ($released) {
                echo "<tr>";
                echo "<td>First released:</td>";
                echo "<td><b>$released</b></td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "</tr>";
            echo "</tbody></table>";

            if ($isFullyFeaturedGame) {
                echo "<div class='gamescreenshots'>";
                echo "<table><tbody>";
                echo "<tr>";
                echo "<td>";
                echo "<img src='$imageTitle' style='max-width:${screenshotWidth}px;max-height:${screenshotMaxHeight}px;' alt='Title Screenhot'>";
                echo "</td>";
                echo "<td>";
                echo "<img src='$imageIngame' style='max-width:${screenshotWidth}px;max-height:${screenshotMaxHeight}px;' alt='In-game Screenshot'>";
                echo "</td>";
                echo "</tr>";
                echo "</tbody></table>";
                echo "</div>";
            }

            echo "<br>";

            if (isset($user) && $permissions >= Permissions::Developer) {
                echo "<div class='devbox'>";
                echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Dev (Click to show):</span><br>";
                echo "<div id='devboxcontent'>";

                if ($isFullyFeaturedGame) {
                    if ($flags == $unofficialFlag) {
                        echo "<div><a href='/game/$gameID'>View Core Achievements</a></div>";
                        echo "<div><a href='/achievementinspector.php?g=$gameID&f=5'>Manage Unofficial Achievements</a></div>";
                    } else {
                        echo "<div><a href='/gameInfo.php?ID=$gameID&f=5'>View Unofficial Achievements</a></div>";
                        echo "<div><a href='/achievementinspector.php?g=$gameID'>Manage Core Achievements</a></div>";
                    }
                    echo "<div><a href='/leaderboardList.php?g=$gameID'>Manage Leaderboards</a></div>";
                }

                echo "<div><a href='/attemptrename.php?g=$gameID'>Rename Game</a></div>";

                if ($isFullyFeaturedGame) {
                    echo "<div><a href='/attemptunlink.php?g=$gameID'>Unlink Game</a></div>";

                    if ($numLeaderboards == 0) {
                        echo "<div><a href='/request/leaderboard/create.php?u=$user&amp;c=$cookie&amp;g=$gameID'>Create First Leaderboard</a></div>";
                    }
                    echo "<div><a href='/request/game/recalculate-points-ratio.php?g=$gameID'>Recalculate True Ratios</a></div>";
                    echo "<div><a href='/ticketmanager.php?g=$gameID&ampt=1'>View open tickets for this game</a></div>";

                    echo "<div><a href='/codenotes.php?g=$gameID'>Code Notes</a></div>";

                    echo "<div>";
                    $isSubscribedToTickets = isUserSubscribedTo(\RA\SubscriptionSubjectType::GameTickets, $gameID, $userID);
                    RenderUpdateSubscriptionForm(
                        "updateticketssub",
                        \RA\SubscriptionSubjectType::GameTickets,
                        $gameID,
                        $isSubscribedToTickets
                    );
                    echo "<a href='#' onclick='document.getElementById(\"updateticketssub\").submit(); return false;'>";
                    echo($isSubscribedToTickets ? "Unsubscribe from" : "Subscribe to") . " Tickets";
                    echo "</a>";
                    echo "</div>";

                    echo "<div>";
                    $isSubscribedToAchievements = isUserSubscribedTo(\RA\SubscriptionSubjectType::GameAchievements, $gameID, $userID);
                    RenderUpdateSubscriptionForm(
                        "updateachievementssub",
                        \RA\SubscriptionSubjectType::GameAchievements,
                        $gameID,
                        $isSubscribedToAchievements
                    );
                    echo "<a href='#' onclick='document.getElementById(\"updateachievementssub\").submit(); return false;'>";
                    echo($isSubscribedToAchievements ? "Unsubscribe from" : "Subscribe to") . " Achievement Comments";
                    echo "</a>";
                    echo "</div>";

                    echo "<br>";

                    echo "<form class='mb-2' method='post' action='/request/uploadpic.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' name='i' value='$gameID'>";
                    echo "<input type='hidden' name='t' value='GAME_TITLE'>";
                    echo "<label for='game_title'>Update title screenshot</label><br>";
                    echo "<input type='file' name='file' id='game_title'>";
                    echo "<input type='submit' name='submit' style='float: right;' value='Submit'>";
                    echo "</form>";

                    echo "<form class='mb-2' method='post' action='/request/uploadpic.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' name='i' value='$gameID'>";
                    echo "<input type='hidden' name='t' value='GAME_INGAME'>";
                    echo "<label for='game_ingame'>Update ingame screenshot</label><br>";
                    echo "<input type='file' name='file' id='game_ingame'>";
                    echo "<input type='submit' name='submit' style='float: right;' value='Submit'>";
                    echo "</form>";
                }

                echo "<form class='mb-2' method='post' action='/request/uploadpic.php' enctype='multipart/form-data'>";
                echo "<input type='hidden' name='i' value='$gameID'>";
                echo "<label for='game_icon'>Update game icon</label><br>";
                echo "<input type='hidden' name='t' value='GAME_ICON'>";
                echo "<input type='file' name='file' id='game_icon'>";
                echo "<input type='submit' name='submit' style='float: right;' value='Submit'>";
                echo "</form>";

                if ($isFullyFeaturedGame) {
                    echo "<form class='mb-2' method='post' action='/request/uploadpic.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' name='i' value='$gameID'>";
                    echo "<label for='game_boxart'>Update game boxart</label><br>";
                    echo "<input type='hidden' name='t' value='GAME_BOXART'>";
                    echo "<input type='file' name='file' id='game_boxart'>";
                    echo "<input type='submit' name='submit' style='float: right;' value='Submit'>";
                    echo "</form>";
                }

                echo "<form class='mb-2' method='post' action='/request/game/update.php' enctype='multipart/form-data'>";
                echo "<div>Update game details:</div>";
                echo "<table><tbody>";
                echo "<input type='hidden' name='i' value='$gameID'>";
                echo "<tr><td>Developer:</td><td style='width:100%'><input type='text' name='d' value='$developer' style='width:100%;'></td></tr>";
                echo "<tr><td>Publisher:</td><td style='width:100%'><input type='text' name='p' value='$publisher' style='width:100%;'></td></tr>";
                echo "<tr><td>Genre:</td><td style='width:100%'><input type='text' name='g' value='$genre' style='width:100%;'></td></tr>";
                echo "<tr><td>First Released:</td><td style='width:100%'><input type='text' name='r' value='$released' style='width:100%;'></td></tr>";
                echo "</tbody></table>";
                echo "<div class='text-right'><input type='submit' value='Submit'></div>";
                echo "</form>";

                if ($permissions >= Permissions::Admin) {
                    echo "<tr><td>";
                    echo "<form method='post' action='/request/game/update.php' enctype='multipart/form-data'>";
                    echo "New Forum Topic ID:";
                    echo "<input type='hidden' name='i' value='$gameID'>";
                    echo "<input type='text' name='f' size='20'>";
                    echo "<input type='submit' style='float: right;' value='Submit' size='37'>";
                    echo "</form>";
                    echo "</td></tr>";
                }

                echo "<div>Similar Games</div>";
                echo "<table><tbody>";
                if (count($gameAlts) > 0) {
                    echo "<tr><td>";
                    echo "<form method='post' action='/request/game/update.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' name='i' value='$gameID'>";

                    echo "To remove:";
                    echo "<select name='m'>";
                    echo "<option value='0' selected>-</option>";

                    foreach ($gameAlts as $gameAlt) {
                        $gameAltID = $gameAlt['gameIDAlt'];
                        $gameAltTitle = $gameAlt['Title'];
                        $gameAltConsole = $gameAlt['ConsoleName'];

                        sanitize_outputs(
                            $gameAltTitle,
                            $gameAltConsole,
                        );

                        echo "<option value='$gameAltID'>$gameAltTitle ($gameAltConsole)</option>";
                    }
                    echo "</select>";
                    echo "<input type='submit' style='float: right;' value='Remove' size='37'>";
                    echo "</form>";
                    echo "</td></tr>";
                }

                echo "<tr><td>";
                echo "<form method='post' action='/request/game/update.php' enctype='multipart/form-data'>";
                echo "To add (game ID):";
                echo "<input type='hidden' name='i' value='$gameID'>";
                echo "<input type='text' name='n' class='searchboxgame' size='20'>";
                echo "<input type='submit' style='float: right;' value='Add' size='37'>";
                echo "</form>";
                echo "</td></tr>";
                echo "</tbody></table>";

                if ($isFullyFeaturedGame) {
                    echo "<div>Update <a href='https://docs.retroachievements.org/Rich-Presence/'>Rich Presence</a> script:</div>";
                    echo "<form method='post' action='/request/game/update.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' value='$gameID' name='i'>";
                    echo "<textarea style='height:320px;' class='code fullwidth' name='x'>$richPresenceData</textarea><br>";
                    echo "<input type='submit' style='float: right;' value='Submit' size='37'>";
                    echo "</form>";
                }

                echo "</div>"; // devboxcontent
                echo "</div>"; // devbox
            }

            if ($isFullyFeaturedGame) {
                if ($flags == $unofficialFlag) {
                    echo "<h4><b>Unofficial</b> Achievements</h4>";
                    echo "<a href='/game/$gameID'><b>Click here to view the Core Achievements</b></a><br>";
                    echo "There are <b>$numAchievements Unofficial</b> achievements worth <b>$totalPossible</b> <span class='TrueRatio'>($totalPossibleTrueRatio)</span> points.<br>";
                } else {
                    echo "<h4>Achievements</h4>";
                    echo "There are <b>$numAchievements</b> achievements worth <b>$totalPossible</b> <span class='TrueRatio'>($totalPossibleTrueRatio)</span> points.<br>";
                }

                if ($numAchievements > 0) {
                    echo "<b>Authors:</b> ";
                    $numItems = count($authorInfo);
                    $i = 0;
                    foreach ($authorInfo as $author => $achievementCount) {
                        echo GetUserAndTooltipDiv($author, false);
                        echo " (" . $achievementCount . ")";
                        if (++$i === $numItems) {
                            echo '.';
                        } else {
                            echo ', ';
                        }
                    }
                    echo "<br>";
                    echo "<br>";
                }

                if (isset($user)) {
                    $pctAwardedCasual = 0;
                    $pctAwardedHardcore = 0;
                    $pctComplete = 0;

                    if ($numAchievements) {
                        $pctAwardedCasual = $numEarnedCasual / $numAchievements;
                        $pctAwardedHardcore = $numEarnedHardcore / $numAchievements;
                        $pctAwardedHardcoreProportion = 0;
                        if ($numEarnedHardcore > 0) {
                            $pctAwardedHardcoreProportion = $numEarnedHardcore / $numEarnedCasual;
                        }

                        $pctAwardedCasual = sprintf("%01.0f", $pctAwardedCasual * 100.0);
                        $pctAwardedHardcore = sprintf("%01.0f", $pctAwardedHardcoreProportion * 100.0);

                        $pctComplete = sprintf(
                            "%01.0f",
                            (($numEarnedCasual + $numEarnedHardcore) * 100.0 / $numAchievements)
                        );
                    }

                    echo "<div class='progressbar'>";
                    echo "<div class='completion' style='width:$pctAwardedCasual%'>";
                    echo "<div class='completionhardcore' style='width:$pctAwardedHardcore%'>&nbsp;</div>";
                    echo "</div>";
                    if ($pctComplete > 100.0) {
                        echo "<b>$pctComplete%</b> complete<br>";
                    } else {
                        echo "$pctComplete% complete<br>";
                    }
                    echo "</div>";
                }

                if ($user !== null && $numAchievements > 0) {
                    echo "<a href='/user/$user'>$user</a> has won <b>$numEarnedCasual</b> achievements";
                    if ($totalEarnedCasual > 0) {
                        echo ", worth <b>$totalEarnedCasual</b> <span class='TrueRatio'>($totalEarnedTrueRatio)</span> points";
                    }
                    echo ".<br>";
                    if ($numEarnedHardcore > 0) {
                        echo "<a href='/user/$user'>$user</a> has won <b>$numEarnedHardcore</b> HARDCORE achievements";
                        if ($totalEarnedHardcore > 0) {
                            echo ", worth a further <b>$totalEarnedHardcore</b> points";
                        }
                        echo ".<br>";
                    }
                }

                if ($user !== null && $numAchievements > 0) {
                    if ($numEarnedCasual > 0 || $numEarnedHardcore > 0) {
                        echo "<div class='devbox'>";
                        echo "<span onclick=\"$('#resetboxcontent').toggle(); return false;\">Reset Progress</span><br>";
                        echo "<div id='resetboxcontent'>";
                        echo "<form id='resetform' action='/request/user/reset-achievements.php' method='post'>";
                        echo "<input type='hidden' name='u' value='$user'>";
                        echo "<input type='hidden' name='g' value='$gameID'>";
                        echo "<input type='submit' value='Reset your progress for this game'>";
                        echo "</form>";
                        echo "</div></div>";
                    }

                    echo "<div style='float: right; clear: both;'>";

                    echo "<h4>Game Rating</h4>";

                    echo "<div class='rating' id='ratinggame'>";
                    echo "<a class='starimg starlit 1star'>1</a>";
                    echo "<a class='starimg starlit 2star'>2</a>";
                    echo "<a class='starimg starlit 3star'>3</a>";
                    echo "<a class='starimg starlit 4star'>4</a>";
                    echo "<a class='starimg starlit 5star'>5</a>";
                    echo "</div>";
                    echo "<span class='ratinggamelabel'>?</span>";

                    echo "</div>";
                    echo "<br>";
                }

                // Only show set request option for logged in users, games without achievements, and core achievement page
                if ($user !== null && $numAchievements == 0 && $flags != 5) {
                    echo "<br>";
                    echo "<div style='float: right; clear: both;'>";
                    echo "<div>";
                    echo "<a class='setRequestLabel'>Request Set</a>";
                    echo "</div>";
                    echo "<span class='gameRequestsLabel'>?</span>";
                    echo "<br>";
                    echo "<span class='userRequestsLabel'>?</span>";
                    echo "</div>";
                }

                /* if( $user !== NULL && $numAchievements > 0 )
                  {
                  echo "<div style='float: right; clear: both;'>";

                  echo "<h4>Achievements Rating</h4>";

                  echo "<div class='rating' id='ratingach'>";
                  echo "<a class='starimg starlit 1star'>1</a>";
                  echo "<a class='starimg starlit 2star'>2</a>";
                  echo "<a class='starimg starlit 3star'>3</a>";
                  echo "<a class='starimg starlit 4star'>4</a>";
                  echo "<a class='starimg starlit 5star'>5</a>";
                  echo "</div>";
                  echo "<span class='ratingachlabel'>?</span>";

                  echo "</div>";
                  echo "<br>";
                  } */

                echo "<div style='clear: both;'>";
                echo "&nbsp;";
                echo "</div>";

                if ($numAchievements > 1) {
                    echo "<div class='sortbyselector'><span>";
                    echo "Sort: ";

                    $flagParam = ($flags != $officialFlag) ? "f=$flags" : '';

                    $sortType = ($sortBy < 10) ? "^" : "<sup>v</sup>";

                    $sort1 = ($sortBy == 1) ? 11 : 1;
                    $sort2 = ($sortBy == 2) ? 12 : 2;
                    $sort3 = ($sortBy == 3) ? 13 : 3;
                    $sort4 = ($sortBy == 4) ? 14 : 4;
                    $sort5 = ($sortBy == 5) ? 15 : 5;

                    $mark1 = ($sortBy % 10 == 1) ? "&nbsp;$sortType" : "";
                    $mark2 = ($sortBy % 10 == 2) ? "&nbsp;$sortType" : "";
                    $mark3 = ($sortBy % 10 == 3) ? "&nbsp;$sortType" : "";
                    $mark4 = ($sortBy % 10 == 4) ? "&nbsp;$sortType" : "";
                    $mark5 = ($sortBy % 10 == 5) ? "&nbsp;$sortType" : "";

                    echo "<a href='/game/$gameID?$flagParam&s=$sort1'>Normal$mark1</a> - ";
                    echo "<a href='/game/$gameID?$flagParam&s=$sort2'>Won By$mark2</a> - ";
                    // TODO sorting by "date won" isn't implemented yet.
                    //if(isset($user)) {
                    //    echo "<a href='/game/$gameID?$flagParam&s=$sort3'>Date Won$mark3</a> - ";
                    //}
                    echo "<a href='/game/$gameID?$flagParam&s=$sort4'>Points$mark4</a> - ";
                    echo "<a href='/game/$gameID?$flagParam&s=$sort5'>Title$mark5</a>";

                    echo "<sup>&nbsp;</sup></span></div>";
                }

                echo "<table class='achievementlist'><tbody>";

                if (isset($achievementData)) {
                    for ($i = 0; $i < 2; $i++) {
                        if ($i == 0 && $numEarnedCasual == 0) {
                            continue;
                        }

                        foreach ($achievementData as &$nextAch) {
                            $achieved = (isset($nextAch['DateEarned']));

                            if ($i == 0 && $achieved == false) {
                                continue;
                            }
                            if ($i == 1 && $achieved == true) {
                                continue;
                            }

                            $achID = $nextAch['ID'];
                            $achTitle = $nextAch['Title'];
                            $achDesc = $nextAch['Description'];
                            $achPoints = $nextAch['Points'];
                            $achTrueRatio = $nextAch['TrueRatio'];
                            $dateAch = "";
                            if ($achieved) {
                                $dateAch = $nextAch['DateEarned'];
                            }
                            $achBadgeName = $nextAch['BadgeName'];

                            sanitize_outputs(
                                $achTitle,
                                $achDesc,
                            );

                            $earnedOnHardcore = isset($nextAch['DateEarnedHardcore']);

                            $achDesc = str_replace('"', '\'', $achDesc);

                            $imgClass = $earnedOnHardcore ? 'goldimagebig' : 'badgeimg';
                            $tooltipText = $earnedOnHardcore ? '<br clear=all>Unlocked: ' . getNiceDate(strtotime($nextAch['DateEarnedHardcore'])) . '<br>-=HARDCORE=-' : '';

                            $wonBy = $nextAch['NumAwarded'];
                            $completionPctCasual = sprintf("%01.2f", ($wonBy / $numDistinctPlayersCasual) * 100);
                            $wonByHardcore = $nextAch['NumAwardedHardcore'];
                            $completionPctHardcore = sprintf("%01.2f", ($wonByHardcore / $numDistinctPlayersCasual) * 100);

                            if ($user == "" || !$achieved) {
                                $achBadgeName .= "_lock";
                            }

                            echo "<tr>";
                            echo "<td>";
                            echo "<div class='achievemententry'>";

                            echo "<div class='achievemententryicon'>";
                            echo GetAchievementAndTooltipDiv(
                                $achID,
                                $achTitle,
                                $achDesc,
                                $achPoints,
                                $gameTitle,
                                $achBadgeName,
                                true,
                                true,
                                $tooltipText,
                                64,
                                $imgClass
                            );
                            echo "</div>";

                            $pctAwardedCasual = 0;
                            $pctAwardedHardcore = 0;
                            $pctComplete = 0;

                            if ($numDistinctPlayersCasual) {
                                $pctAwardedCasual = $wonBy / $numDistinctPlayersCasual;
                                $pctAwardedHardcore = $wonByHardcore / $numDistinctPlayersCasual;
                                $pctAwardedHardcoreProportion = 0;
                                if ($wonByHardcore > 0 && $wonBy > 0) {
                                    $pctAwardedHardcoreProportion = $wonByHardcore / $wonBy;
                                }

                                $pctAwardedCasual = sprintf("%01.2f", $pctAwardedCasual * 100.0);
                                $pctAwardedHardcore = sprintf("%01.2f", $pctAwardedHardcoreProportion * 100.0);

                                $pctComplete = sprintf(
                                    "%01.2f",
                                    (($wonBy + $wonByHardcore) * 100.0 / $numDistinctPlayersCasual)
                                );
                            }

                            echo "<div class='progressbar allusers'>";
                            echo "<div class='completion allusers'             style='width:$pctAwardedCasual%'>";
                            echo "<div class='completionhardcore allusers'     style='width:$pctAwardedHardcore%'>";
                            echo "&nbsp;";
                            echo "</div>";
                            echo "</div>";
                            if ($wonByHardcore > 0) {
                                echo "won by $wonBy <strong alt='HARDCORE'>($wonByHardcore)</strong> of $numDistinctPlayersCasual ($pctAwardedCasual%)<br>";
                            } else {
                                echo "won by $wonBy of $numDistinctPlayersCasual ($pctAwardedCasual%)<br>";
                            }
                            echo "</div>"; //progressbar

                            echo "<div class='achievementdata'>";
                            echo GetAchievementAndTooltipDiv(
                                $achID,
                                $achTitle,
                                $achDesc,
                                $achPoints,
                                $gameTitle,
                                $achBadgeName,
                                false,
                                false,
                                "",
                                64,
                                $imgClass
                            );
                            echo " <span class='TrueRatio'>($achTrueRatio)</span>";
                            echo "<br>";
                            echo "$achDesc<br>";
                            echo "</div>";

                            if ($achieved) {
                                echo "<div class='date smalltext'>unlocked on<br>$dateAch<br></div>";
                            }
                            echo "</div>"; //    achievemententry
                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                }
                echo "</tbody></table>";
            }

            if (!$isFullyFeaturedGame) {
                if (count($gameAlts) > 0) {
                    RenderGameAlts($gameAlts, false);
                }
            }

            RenderLinkToGameForum($gameTitle, $gameID, $forumTopicID, $permissions);
            echo "<br><br>";

            if ($isFullyFeaturedGame) {
                RenderCommentsComponent($user, $numArticleComments, $commentData, $gameID, \RA\ArticleType::Game, $permissions >= Permissions::Admin);
            }
            ?>
        </div>
    </div>
    <?php if ($isFullyFeaturedGame): ?>
        <div id="rightcontainer">
            <?php
            RenderBoxArt($gameData['ImageBoxArt']);

            if (isset($user)) {
                echo "<h3>More Info</h3>";
                echo "<ul>";
                echo "<li>";
                RenderLinkToGameForum($gameTitle, $gameID, $forumTopicID, $permissions);
                echo "</li>";
                echo "<li><a class='info-button' href='/linkedhashes.php?g=$gameID'><span>🔗</span>Hashes linked to this game</a></li>";
                $numOpenTickets = countOpenTickets(
                    requestInputSanitized('f') == $unofficialFlag,
                    requestInputSanitized('t', 2041),
                    null,
                    $gameID
                );
                if ($permissions >= Permissions::Registered) {
                    if ($flags == $unofficialFlag) {
                        echo "<li><a class='info-button' href='/ticketmanager.php?g=$gameID&f=$flags'><span>🎫</span>($numOpenTickets) Open Unofficial Tickets for this game</a></li>";
                    } else {
                        echo "<li><a class='info-button' href='/ticketmanager.php?g=$gameID'><span>🎫</span>($numOpenTickets) Open Tickets for this game</a></li>";
                    }
                }
                if ($numAchievements == 0) {
                    echo "<li><a class='info-button' href='/setRequestors.php?g=$gameID'><span>📜</span>Set Requestors for this game</a></li>";
                }
                //if( $flags == $unofficialFlag )
                //echo "<li><a class='info-button' href='/game/$gameID'><span>🏆</span>View Core Achievements</a></li>";
                //else
                //echo "<li><a class='info-button' href='/gameInfo.php?ID=$gameID&f=5'><span>🏆</span>View Unofficial Achievements</a></li>";
                echo "</ul><br>";
            }

            if (count($gameAlts) > 0) {
                RenderGameAlts($gameAlts);
            }

            if ($user == null) {
                RenderTutorialComponent();
            }

            RenderGameCompare($user, $gameID, $friendScores, $totalPossible);

            echo "<div id='achdistribution' class='component' >";
            echo "<h3>Achievement Distribution</h3>";
            echo "<div id='chart_distribution'></div>";
            echo "</div>";

            RenderTopAchieversComponent($user, $gameTopAchievers, $gameLatestMasters);
            RenderGameLeaderboardsComponent($gameID, $lbData);
            ?>
        </div>
    <?php endif ?>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
