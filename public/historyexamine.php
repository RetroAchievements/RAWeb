<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$userPage = requestInputSanitized('u', $user);
if (!isset($userPage)) {
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

getUserPageInfo($userPage, $userMassData, 0, 0, $user);
if (!$userMassData) {
    http_response_code(404);
    echo "User not found";
    exit;
}

$dateInput = requestInputSanitized('d', 0);

$userPagePoints = getScore($userPage);

$achEarnedOnDay = getAchievementsEarnedOnDay($dateInput, $userPage);

$dateStr = strftime("%d %b %Y", $dateInput);

$errorCode = requestInputSanitized('e');

RenderHtmlStart(true);
RenderHtmlHead("$userPage's Legacy - $dateStr");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id='fullcontainer'>
        <?php
        echo "<div class='navpath'>";
        echo "<a href='/userList.php'>All Users</a>";
        echo " &raquo; <a href='/user/$userPage'>$userPage</a>";
        echo " &raquo; <a href='history.php?u=$userPage'>History</a>";
        echo " &raquo; <b>$dateStr</b>";
        echo "</div>";

        echo "<h3 class='longheader'>History - $dateStr</h3>";

        echo "<div class='userlegacy'>";
        echo "<img src='/UserPic/$userPage.png' alt='$userPage' align='right' width='64' height='64'>";
        echo "<b><a href='/user/$userPage'><strong>$userPage</strong></a> ($userPagePoints points)</b><br><br>";

        //echo "<a href='history.php?u=$userPage'>Back to $userPage's Legacy</a>";

        echo "<br>";

        echo "</div>";

        echo "<table class='smalltable xsmall'><tbody>";

        //$sort1 = ($sortBy==1) ? 11 : 1;
        //$sort2 = ($sortBy==2) ? 12 : 2;
        //$sort3 = ($sortBy==3) ? 13 : 3;

        echo "<tr>";
        echo "<th>At</th>";
        echo "<th>Title</th>";
        echo "<th>Description</th>";
        echo "<th>Points</th>";
        echo "<th>Author</th>";
        echo "<th>Game Title</th>";
        echo "</tr>";

        //var_dump( $achEarnedOnDay );

        //	Merge if poss and count
        $achCount = count($achEarnedOnDay);
        $pointsCount = 0;
        //foreach( $achEarnedOnDay as $achEarned )

        //	Tally all
        for ($i = 0; $i < $achCount; $i++) {
            $achID = $achEarnedOnDay[$i]['AchievementID'];
            $achPoints = $achEarnedOnDay[$i]['Points'];
            $pointsCount += $achPoints;
        }

        $achEarnedLib = [];

        //	Store all NORMAL into $achEarnedLib
        for ($i = 0; $i < $achCount; $i++) {
            $achID = $achEarnedOnDay[$i]['AchievementID'];
            //var_dump( $achEarnedOnDay[$i] );
            if ($achEarnedOnDay[$i]['HardcoreMode'] == 0) {
                $achEarnedLib[$achID] = $achEarnedOnDay[$i];
            }
        }

        //	Potentially overwrite HARDCORE into $achEarnedLib
        for ($i = 0; $i < $achCount; $i++) {
            $achID = $achEarnedOnDay[$i]['AchievementID'];
            if ($achEarnedOnDay[$i]['HardcoreMode'] == 1) {
                //if( isset( $achEarnedLib[$achID] ) && $achEarnedLib[$achID]['HardcoreMode'] == 1 )
                //	Ordinary ach also exists: notify in points col
                $achEarnedLib[$achID] = $achEarnedOnDay[$i];
                $achPoints = $achEarnedLib[$achID]['Points'];
                $achEarnedLib[$achID]['PointsNote'] = "<span class='hardcore'>(+$achPoints)</span>";
            }
        }

        function dateCompare($a, $b)
        {
            return $a['Date'] > $b['Date'];
        }

        usort($achEarnedLib, "dateCompare");

        foreach ($achEarnedLib as $achEarned) {
            $achAwardedAt = $achEarned['Date'];
            $achID = $achEarned['AchievementID'];
            $achTitle = $achEarned['Title'];
            $achDesc = $achEarned['Description'];
            $achPoints = $achEarned['Points'];
            $achPointsNote = isset($achEarned['PointsNote']) ? $achEarned['PointsNote'] : '';
            $achAuthor = $achEarned['Author'];
            $achGameID = $achEarned['GameID'];
            $achGameTitle = $achEarned['GameTitle'];
            $achGameIcon = $achEarned['GameIcon'];
            $achConsoleName = $achEarned['ConsoleName'];
            $achBadgeName = $achEarned['BadgeName'];
            $hardcoreMode = $achEarned['HardcoreMode'];

            //$pointsCount 	+= $achPoints;

            //$dateUnix 		= strtotime( "$nextDay-$nextMonth-$nextYear" );
            //$dateStr 		= getNiceDate( $dateUnix, TRUE );

            echo "<tr>";

            echo "<td>";
            echo getNiceTime(strtotime($achAwardedAt));
            echo "</td>";

            echo "<td style='min-width:25%'>";
            echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $achGameTitle, $achBadgeName, true);
            echo "</td>";

            echo "<td style='min-width:25%'>";
            echo "$achDesc";
            if ($hardcoreMode) {
                echo " <span class='hardcore'>(Hardcore!)</span>";
            }
            echo "</td>";

            echo "<td>";
            echo "$achPoints $achPointsNote";
            echo "</td>";

            echo "<td>";
            echo GetUserAndTooltipDiv($achAuthor, true);
            echo "</td>";

            echo "<td>";
            echo GetGameAndTooltipDiv($achGameID, $achGameTitle, $achGameIcon, $achConsoleName, true, 32);
            echo "</td>";

            echo "</tr>";
        }

        echo "</tbody></table>";

        echo "<h3>Summary</h3>";
        echo "<div class='historyexaminesummary'>";
        echo "Total earned on $dateStr: <strong>$pointsCount</strong> points, <strong>$achCount</strong> achievements.<br><br>";
        echo "<a href='/history.php?u=$userPage'>&laquo; Back to $userPage's Legacy</a><br><br>";
        echo "</div>";
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
