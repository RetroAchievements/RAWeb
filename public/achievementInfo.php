<?php

use RA\Permissions;

require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$achievementID = seekGET('ID', 0);
settype($achievementID, 'integer');

if ($achievementID == 0 || getAchievementMetadata($achievementID, $dataOut) == false) {
    header("Location: " . getenv('APP_URL') . "?e=unknownachievement");
    exit;
}

$achievementTitle = $dataOut['AchievementTitle'];
$desc = $dataOut['Description'];
$achFlags = $dataOut['Flags'];
$achPoints = $dataOut['Points'];
$achTruePoints = $dataOut['TrueRatio'];
$gameTitle = $dataOut['GameTitle'];
$badgeName = $dataOut['BadgeName'];
$consoleID = $dataOut['ConsoleID'];
$consoleName = $dataOut['ConsoleName'];
$gameID = $dataOut['GameID'];
$embedVidURL = $dataOut['AssocVideo'];
$author = $dataOut['Author'];
$dateCreated = $dataOut['DateCreated'];
$dateModified = $dataOut['DateModified'];
$achMem = $dataOut['MemAddr'];

$numLeaderboards = getLeaderboardsForGame($gameID, $lbData, $user);

$numWinners = 0;
$numPossibleWinners = 0;
$numRecentWinners = 0;

getAchievementWonData($achievementID, $numWinners, $numPossibleWinners, $numRecentWinners, $winnerInfo, $user);

$dateWonLocal = "";
foreach ($winnerInfo as $userWon => $userObject) {
    if ($userWon == $user) {
        $dateWonLocal = $userObject['DateAwarded'];
        break;
    }
}

$achievedLocal = ($dateWonLocal !== "");

$numArticleComments = getArticleComments(2, $achievementID, 0, 20, $commentData);

getCodeNotes($gameID, $codeNotes);

$errorCode = seekGET('e');

$pageTitle = $achievementTitle;

RenderDocType(true);
?>

<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader($user); ?>
    <?php RenderFBMetadata("$achievementTitle in $gameTitle ($consoleName)", "achievement",
        "/Badge/$badgeName" . ".png", "/Achievement/$achievementID", "$gameTitle ($consoleName) - $desc"); ?>
    <?php RenderTitleTag($pageTitle, $user); ?>
    <?php RenderGoogleTracking(); ?>
</head>

<body>

<?php
if ($permissions >= 2) {
    ?>
    <script>
        function PostEmbedUpdate() {
            var url = $("body").find("#embedurlinput").val();
            url = replaceAll("http", "_http_", url);

            var posting = $.post("/requestupdateachievement.php", {
                u: '<?php echo $user; ?>',
                a: <?php echo $achievementID; ?>,
                f: 2,
                v: url
            });
            posting.done(onUpdateEmbedComplete);
            $("body").find("#warning").html("Status: Updating...");
        }

        function onUpdateEmbedComplete(data) {
            if (data !== "OK") {
                $("body").find("#warning").html("Status: Errors...");
            } else {
                $("body").find("#warning").html("Status: Loading...");
                window.location.reload();
            }
        }
    </script>

    <?php
}
?>

<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>

<div id="mainpage">

    <div id='leftcontainer'>

        <?php
        RenderErrorCodeWarning('left', $errorCode);

        echo "<div id='achievement' class='left' >";

        echo "<div class='navpath'>";
        echo "<a href='/gameList.php'>All Games</a>";
        echo " &raquo; <a href='/gameList.php?c=$consoleID'>$consoleName</a>";
        echo " &raquo; <a href='/Game/$gameID'>$gameTitle</a>";
        echo " &raquo; <b>$achievementTitle</b>";
        echo "</div>"; //navpath

        echo "<h3 class='longheader'>$gameTitle ($consoleName)</h3>";

        $fileSuffix = ($user == "" || ($achievedLocal == false)) ? "_lock.png" : ".png";
        $badgeFullPath = getenv('APP_STATIC_URL') . "/Badge/" . $badgeName . $fileSuffix;

        echo "<table class='nicebox'><tbody>";

        echo "<tr>";
        echo "<td style='width:70px'>";
        echo "<div id='achievemententryicon'>";
        echo "<a href='/Achievement/$achievementID'><img src='$badgeFullPath' title='$gameTitle ($achPoints)\n$desc' alt='$desc' align='left' width='64' height='64' /></a>";
        echo "</div>"; //achievemententryicon
        echo "</td>";

        //echo "<td style='float: left;'>";    //Horrible dont do this
        echo "<td>";
        echo "<div id='achievemententry'>";

        if ($achievedLocal) {
            $niceDateWon = date("d M, Y H:i", strtotime($dateWonLocal));
            echo "<small style='float: right; text-align: right;' class='smalldate'>unlocked on<br/>$niceDateWon</small>";
        }
        echo "<a href='/Achievement/$achievementID'><strong>$achievementTitle</strong></a> ($achPoints)<span class='TrueRatio'> ($achTruePoints)</span><br/>";
        echo "$desc<br/>";

        echo "</div>"; //achievemententry
        echo "</td>";

        echo "</tr>";
        echo "</tbody></table>";

        if ($numPossibleWinners > 0) {
            $recentWinnersPct = sprintf("%01.0f", ($numWinners / $numPossibleWinners) * 100);
        } else {
            $recentWinnersPct = sprintf("%01.0f", 0);
        }


        $niceDateCreated = date("d M, Y H:i", strtotime($dateCreated));
        $niceDateModified = date("d M, Y H:i", strtotime($dateModified));

        echo "<p class='smalldata'>";
        echo "<small>";
        if ($achFlags == 5) {
            echo "<b>Unofficial Achievement</b><br>";
        }
        echo "Created by <a href='/User/$author'>$author</a> on: $niceDateCreated<br/>Last modified: $niceDateModified<br/>";
        echo "</small>";
        echo "</p>";

        echo "Won by <b>$numWinners</b> of <b>$numPossibleWinners</b> possible players ($recentWinnersPct%)";

        if (isset($user) && $permissions >= 1) {
            echo "<br>";
            $countTickets = countOpenTicketsByAchievement($achievementID);
            if ($countTickets > 0) {
                echo "<small><a href='/ticketmanager.php?a=$achievementID'>This achievement has $countTickets open tickets</a></small><br>";
            }
            echo "<small><a href='/reportissue.php?i=$achievementID'>Report an issue for this achievement.</a></small>";
        }
        echo "<br/><br/><br/><br/>";

        if (isset($user) && $permissions >= 2) {
            echo "<div class='devbox'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(500); return false;\">Dev (Click to show):</span><br/>";
            echo "<div id='devboxcontent'>";

            echo "<li>Set embedded video URL:</li>";
            echo "<table><tbody>";
            echo "<input type='hidden' name='a' value='$achievementID' />";
            echo "<input type='hidden' name='f' value='2' />";
            echo "<input type='hidden' name='u' value='$user' />";
            echo "<tr><td>Embed:</td><td style='width:100%'><input id='embedurlinput' type='text' name='v' value='$embedVidURL' style='width:100%;'/></td></tr>";
            echo "</tbody></table>";
            echo "&nbsp;<input type='submit' style='float: right;' value='Submit' onclick=\"PostEmbedUpdate()\" /></br></br>";
            echo "<div style='clear:both;'></div>";
            ?>
            Examples for accepted formats:<br>
            <p style="margin-bottom: 20px; float: left; clear: both;">
                <small style="width:50%; word-break: break-word; float: left">
                    https://www.youtube.com/v/ID<br>
                    https://www.youtube.com/watch?v=ID<br>
                    https://youtu.be/ID<br>
                    https://www.youtube.com/embed/ID<br>
                    https://www.youtube.com/watch?v=ID<br>
                    www.youtube.com/watch?v=ID<br>
                    https://www.twitch.tv/videos/ID<br>
                    https://www.twitch.tv/collections/ID<br>
                    https://www.twitch.tv/ID/v/ID<br>
                    https://clips.twitch.tv/ID<br>
                </small>
                <small style="width:50%; word-break: break-word; float: left">
                    https://imgur.com/gallery/ID -> turns out as link without extension<br>
                    https://imgur.com/a/ID.gif -> will use .gifv instead<br>
                    https://imgur.com/gallery/ID.gifv<br>
                    https://imgur.com/a/ID.gifv<br>
                    https://i.imgur.com/ID.gifv<br>
                    https://i.imgur.com/ID.webm<br>
                    https://i.imgur.com/ID.mp4<br>
                </small>
            </p>
            <?php
            echo "<div style='clear:both;'></div>";

            if ($achFlags == 3) {
                echo "<li>State: Official&nbsp;<a href='/requestupdateachievement.php?a=$achievementID&amp;f=3&amp;u=$user&amp;v=5'>Demote To Unofficial</a></li>";
            } elseif ($achFlags == 5) {
                echo "<li>State: Unofficial</li>";
            }

            echo "<li> Achievement ID: " . $achievementID . "</li>";

            echo "<div>";
            echo "<li>Mem:</li>";
            echo "<code>" . htmlspecialchars($achMem) . "</code>";
            echo "<li>Mem explained:</li>";
            echo "<code>" . getAchievementPatchReadableHTML($achMem, $codeNotes) . "</code>";
            echo "</div>";

            echo "</div>"; //    devboxcontent
            echo "</div>"; //    devbox
        }

        if ($embedVidURL !== "") {
            echo parseTopicCommentPHPBB($embedVidURL, true);
        }

        //    Comments:
        $forceAllowDeleteComments = $permissions >= Permissions::Admin;
        RenderCommentsComponent($user, $numArticleComments, $commentData, $achievementID, 2, $forceAllowDeleteComments);

        echo "</div>"; //achievement

        echo "<div id='recentwinners' class='left'>";
        echo "<h3>Winners</h3>";

        if (count($winnerInfo) == 0) {
            echo "Nobody yet! Will you be the first?!<br/>";
        } else {
            echo "<table class='smalltable'><tbody>";
            echo "<tr><th colspan='2'>User</th><th>Hardcore?</th><th>Earned On</th></tr>";
            $iter = 0;
            foreach ($winnerInfo as $userWinner => $userObject) {
                if ($userWinner == null || $userObject['DateAwarded'] == null) {
                    continue;
                }

                $niceDateWon = date("d M, Y H:i", strtotime($userObject['DateAwarded']));

                echo "<tr>";

                echo "<td style='width:34px'>";
                echo GetUserAndTooltipDiv($userWinner, $userObject['RAPoints'], null, null, null, true);
                echo "</td>";
                echo "<td>";
                echo GetUserAndTooltipDiv($userWinner, $userObject['RAPoints'], null, null, null, false);
                echo "</td>";
                echo "<td>";
                if ($userObject['HardcoreMode']) {
                    echo "<span class='hardcore'>Hardcore!</span>";
                } else {
                    echo "";
                }
                echo "</td>";

                //echo "<a href='/User/$userWinner'><img alt='Won by $userWinner' title='$userWinner' src='/UserPic/$userWinner.png' width='32' height='32'/></a>";
                //var_dump( $userObject );
                //echo GetUserAndTooltipDiv( $userObject['User'], $userObject['RAPoints'], NULL, NULL, NULL, FALSE );
                //echo " (" . $userObject['RAPoints'] . ")";
                //echo "</td>";

                echo "<td>";
                echo "<small>$niceDateWon</small>";
                echo "</td>";

                echo "</tr>";
            }

            echo "</tbody></table>";
        }

        echo "</div>"; //RecentWinners;
        ?>
    </div>    <!-- leftcontainer -->

    <div id='rightcontainer'>
        <?php
        if ($user !== null) {
            RenderScoreLeaderboardComponent($user, $points, true);
        }

        RenderGameLeaderboardsComponent($gameID, $lbData);
        ?>
    </div>
</div>

<?php RenderFooter(); ?>

</body>
</html>
