<?php
require_once __DIR__ . '/../lib/bootstrap.php';

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Admin)) {
    //	Immediate redirect if we cannot validate user!	//TBD: pass args?
    header("Location: " . getenv('APP_URL'));
    exit;
}

$newsArticleID = seekGET('n');
$newsCount = getLatestNewsHeaders(0, 999, $newsData);
$activeNewsArticle = null;

$testUser = "Bob";
$rawPass = "qwe";
$saltPass = md5($rawPass . getenv('RA_PASSWORD_SALT'));
$appToken = "INbOEl5bviMEmU4b";

$reqAchievementID = 1;
$reqAchievementValidation = sprintf("%d,%d-%s.%s-%d132%s2A%slLIA", $reqAchievementID, (strlen($testUser) * 3) + 1,
    $testUser, $appToken, $reqAchievementID, $testUser, "WOAHi2");

$awardAchievementID = seekPOST('a');
$awardAchievementUser = seekPOST('u');
$awardAchHardcore = seekPOST('h', 0);

function tailCustom($filepath, $lines = 1, $adaptive = true)
{

    // Open file
    $f = @fopen($filepath, "rb");
    if ($f === false) {
        return false;
    }

    // Sets buffer size
    if (!$adaptive) {
        $buffer = 4096;
    } else {
        $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
    }

    // Jump to last character
    fseek($f, -1, SEEK_END);

    if (fread($f, 1) != "\n") {
        $lines -= 1;
    }

    // Start reading
    $output = '';
    $chunk = '';

    // While we would like more
    while (ftell($f) > 0 && $lines >= 0) {

        // Figure out how far back we should jump
        $seek = min(ftell($f), $buffer);

        // Do the jump (backwards, relative to where we are)
        fseek($f, -$seek, SEEK_CUR);

        // Read a chunk and prepend it to our output
        $output = ($chunk = fread($f, $seek)) . $output;

        // Jump back to where we started reading
        fseek($f, -strlen($chunk), SEEK_CUR);

        // Decrease our line counter
        $lines -= substr_count($chunk, "\n");
    }

    while ($lines++ < 0) {

        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
    }

    // Close file and return
    fclose($f);
    return trim($output);
}

if (seekGET('action') == 'regenapi') {
    $query = "SELECT User FROM UserAccounts";
    $dbResult = s_mysql_query($query);

    $userList = '';
    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        $userList[] = $db_entry['User'];
    }

    $numRegens = 0;
    foreach ($userList as $nextTempUser) {
        $newKey = generateAPIKey($nextTempUser);
        if ($newKey !== "") {
            $numRegens++;
        }
    }

    echo "REGENERATED $numRegens APIKEYS!<br>";
} else {
    if (seekGET('action') == 'regenapione') {
        $targetUser = seekGET('t');
        $newKey = generateAPIKey($targetUser);
        echo "New API Key for $targetUser: $newKey<br>";
    } else {
        if (seekGET('action') == 'recalcdev') {
            $query = "SELECT User FROM UserAccounts";
            $dbResult = s_mysql_query($query);

            $userList = '';
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $userList[] = $db_entry['User'];
            }

            $numRegens = 0;
            foreach ($userList as $nextTempUser) {
                $valid = recalculateDevelopmentContributions($nextTempUser);
                echo "$nextTempUser<br>";
                if ($valid) {
                    $numRegens++;
                }
            }

            echo "REGENERATED $numRegens developer contribution totals!<br>";
        } else {
            if (seekGET('action') == 'reconstructsiteawards') {
                $tgtPlayer = seekGET('t', null);

                $query = "DELETE FROM SiteAwards WHERE AwardType = 1";
                if ($tgtPlayer !== null) {
                    $query .= " AND User = '$tgtPlayer'";
                }

                $dbResult = s_mysql_query($query);

                $query = "SELECT User FROM UserAccounts";
                if ($tgtPlayer !== null) {
                    $query .= " WHERE User = '$tgtPlayer'";
                }

                $dbResult = s_mysql_query($query);

                $userList = [];
                if ($dbResult !== false) {
                    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                        $userList[] = $db_entry['User'];
                    }
                } else {
                    echo "Error accessing UserAccounts";
                    exit;
                }

                $numAccounts = count($userList);
                for ($i = 0; $i < $numAccounts; $i++) {
                    $user = $userList[$i];

                    echo "Updating $user...<br>";

                    $query = "	SELECT gd.ID AS GameID, c.Name AS ConsoleName, gd.ImageIcon, gd.Title, COUNT(ach.GameID) AS NumAwarded, inner1.MaxPossible, (COUNT(ach.GameID)/inner1.MaxPossible) AS PctWon , aw.HardcoreMode
						FROM Awarded AS aw
						LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
						LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
						LEFT JOIN
							( SELECT COUNT(*) AS MaxPossible, ach1.GameID FROM Achievements AS ach1 WHERE Flags=3 GROUP BY GameID )
							AS inner1 ON inner1.GameID = ach.GameID AND inner1.MaxPossible > 5
						LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
						WHERE aw.User='$user' AND ach.Flags = 3
						GROUP BY ach.GameID, aw.HardcoreMode
						ORDER BY PctWon DESC, inner1.MaxPossible DESC, gd.Title";

                    $dbResult = s_mysql_query($query);

                    if ($dbResult !== false) {
                        $listOfAwards = [];

                        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                            $listOfAwards[] = $db_entry;
                            //$nextElem = $db_entry;
                            //$nextGameID = $nextElem['GameID'];
                            // if( $nextElem['PctWon'] == 2.0 )
                            // {
                            // $gameTitle = $nextElem['Title'];
                            // echo "Mastered $gameTitle<br>";
                            // //	Add award:
                            // AddSiteAward( $user, 1, $nextElem['GameID'], 1 );
                            // }
                            // if( $nextElem['PctWon'] >= 1.0 )	//noooo!!!!
                            // {
                            // $gameTitle = $nextElem['Title'];
                            // echo "Completed $gameTitle<br>";
                            // //	Add award:
                            // AddSiteAward( $user, 1, $nextElem['GameID'], 0 );
                            // }
                        }

                        $awardAddedHC = [];

                        foreach ($listOfAwards as $nextAward) {
                            if ($nextAward['HardcoreMode'] == 1) {
                                if ($nextAward['PctWon'] == 1.0) {
                                    $gameTitle = $nextAward['Title'];
                                    $gameID = $nextAward['GameID'];
                                    echo "MASTERED $gameTitle<br>";
                                    //	Add award:
                                    AddSiteAward($user, 1, $gameID, 1);

                                    $awardAddedHC[] = $gameID;
                                }
                            }
                        }

                        foreach ($listOfAwards as $nextAward) {
                            if ($nextAward['HardcoreMode'] == 0) {
                                //	Check it hasnt already been added as a non-HC award
                                if ($nextAward['PctWon'] == 1.0) {
                                    $gameTitle = $nextAward['Title'];
                                    $gameID = $nextAward['GameID'];

                                    if (!in_array($gameID, $awardAddedHC)) {
                                        echo "Completed $gameTitle<br>";
                                        //	Add award:
                                        AddSiteAward($user, 1, $gameID, 0);
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                if (seekGET('action') == 'recalcsiteawards') {
                    $tgtPlayer = seekGET('t', null);
                    {
                        $query = "DELETE FROM SiteAwards WHERE ( AwardType = 2 || AwardType = 3 || AwardType = 5 )";
                        if ($tgtPlayer !== null) {
                            $query .= " AND User = '$tgtPlayer'";
                        }

                        global $db;
                        $unusedDBResult = mysqli_query($db, $query);
                    }
                    {
                        $query = "SELECT User, ContribCount, ContribYield, fbUser FROM UserAccounts";
                        if ($tgtPlayer !== null) {
                            $query .= " WHERE User = '$tgtPlayer'";
                        }

                        $dbResult = mysqli_query($db, $query);

                        $userList = '';
                        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                            $userList[] = [
                                $db_entry['User'],
                                $db_entry['ContribCount'],
                                $db_entry['ContribYield'],
                                $db_entry['fbUser'],
                            ];
                        }

                        $numRecalced = 0;
                        foreach ($userList as $nextTempUser) {
                            global $developerCountBoundaries;
                            global $developerPointBoundaries;

                            $nextUser = $nextTempUser[0];
                            $nextCount = $nextTempUser[1];
                            $nextYield = $nextTempUser[2];
                            $nextFBUser = $nextTempUser[3];

                            for ($i = 0; $i < count($developerCountBoundaries); $i++) {
                                if ($nextCount >= $developerCountBoundaries[$i]) {
                                    //echo "$nextUser has $nextCount, greater than $developerCountBoundaries[  $i ], addaward!<br>";
                                    //This developer has arrived at this point boundary!
                                    AddSiteAward($nextUser, 2, $i);
                                    $numRecalced++;
                                }
                            }
                            for ($i = 0; $i < count($developerPointBoundaries); $i++) {
                                if ($nextYield >= $developerPointBoundaries[$i]) {
                                    //echo "$nextUser has yield of $nextYield, greater than $developerPointBoundaries[     $i ], addaward!<br>";
                                    //This developer is newly above this point boundary!
                                    AddSiteAward($nextUser, 3, $i);
                                    $numRecalced++;
                                }
                            }

                            if (isset($nextFBUser) && strlen($nextFBUser) > 2) {
                                echo "$nextUser has signed up for FB, add FB award!<br>";
                                AddSiteAward($nextUser, 5, 0);
                            }
                        }

                        echo "RECALCULATED $numRecalced site awards!<br>";
                    }
                } else {
                    if (seekPOST('action') == 'getachids') {
                        $gameIDs = explode(',', seekPOST('g'));

                        foreach ($gameIDs as $nextGameID) {
                            $ids = getAchievementIDs($nextGameID);

                            foreach ($ids["AchievementIDs"] as $id) {
                                echo "$id,";
                            }
                        }
                    } else {
                        if (seekPOST('action') == 'giveaward') {
                            //	Debug award achievement:
                            //$awardAchievementID 	= seekPOST( 'a' );
                            //$awardAchievementUser = seekPOST( 'u' );
                            //$awardAchHardcore 	= seekPOST( 'h', 0 );

                            if (isset($awardAchievementID) && isset($awardAchievementUser)) {
                                $ids = explode(',', $awardAchievementID);
                                foreach ($ids as $nextID) {
                                    if (addEarnedAchievement($awardAchievementUser, '', $nextID, 0, $newPointTotal,
                                        $awardAchHardcore, false)) {
                                        echo " - Updated $awardAchievementUser's score to $newPointTotal!<br>";
                                    }
                                }
                            }
                        } else {
                            if (seekGET('action') == 'recalctrueratio') {
                                set_time_limit(3000);

                                $query = "SELECT MAX(ID) FROM GameData";
                                $dbResult = s_mysql_query($query);
                                $data = mysqli_fetch_assoc($dbResult);
                                $numGames = $data['MAX(ID)'];
                                for ($i = 1; $i <= $numGames; $i++) {
                                    error_log("Recalculating TA for Game ID: $i<br>");
                                    echo "Recalculating TA for Game ID: $i<br>";
                                    recalculateTrueRatio($i);

                                    ob_flush();
                                    flush();

                                    //if( $i % 10 == 0 )
                                    //	sleep( 1 );
                                }

                                error_log("Recalc'd TA for $numGames games!");
                                echo "Recalc'd TA for $numGames games!";
                                exit;
                            } else {
                                if (seekGET('action') == 'recalcplayerscores') {
                                    set_time_limit(3000);

                                    getUserList(1, 0, 99999, $userData, "");

                                    echo "Recalc players scores: " . count($userData) . " to process...";

                                    foreach ($userData as $nextUser) {
                                        echo "Player: " . $nextUser['User'] . " recalc (was TA: " . $nextUser['TrueRAPoints'] . ")<br>";
                                        recalcScore($nextUser['User']);

                                        ob_flush();
                                        flush();
                                    }

                                    error_log("Recalc'd TA for " . count($userData) . " players!");
                                    echo "Recalc'd TA for " . count($userData) . " players!";
                                    exit;
                                } else {
                                    if (seekPOSTorGET('action') == 'updatestaticdata') {
                                        $achID = seekPOSTorGET('a', 0, 'integer');
                                        $forumID = seekPOSTorGET('f', 0, 'integer');

                                        //echo $achID;
                                        //echo $forumID;

                                        $query = "UPDATE StaticData SET
			Event_AOTW_AchievementID='$achID',
			Event_AOTW_ForumID='$forumID'";

                                        s_mysql_query($query);
                                        echo "Successfully updated static data!";
                                    }
                                    // else if( seekGET( 'action' ) == 'access_log' )
                                    // {
                                    // $accessLog = file_get_contents( "../../log/httpd/access_log" );
                                    // echo $accessLog;
                                    // exit;
                                    // }
                                    // else if( seekGET( 'action' ) == 'error_log' )
                                    // {
                                    // $log = file_get_contents( "../../log/httpd/error_log" );
                                    // echo $log;
                                    // exit;
                                    // }
                                    else {
                                        if (seekPOSTorGET('action') == 'errorlog') {
                                            $errorlogpath = "/var/log/httpd/error_log";

                                            //$result = exec( "tail -n10 $errorlogpath");
                                            //var_dump( $result );

                                            echo "<a href='/admin.php?action=errorlog&c=50'>Last 50</a> - ";
                                            echo "<a href='/admin.php?action=errorlog&c=100'>Last 100</a> - ";
                                            echo "<a href='/admin.php?action=errorlog&c=500'>Last 500</a><br><br>";

                                            $count = seekPOSTorGET('c', 20, 'integer');
                                            echo nl2br(tailCustom($errorlogpath, $count));
                                            exit;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

$staticData = getStaticData();

RenderHtmlStart();
RenderHtmlHead('Global Tests');
?>
<body>
<div id="fullcontainer">
    <h1>Admin Tools</h1>
    <?php
    echo "Account: <b>" . $user . "</b><br>";
    echo "Account Type: <b>" . PermissionsToString($permissions) . "</b><br>";

    if ($permissions >= \RA\Permissions::Root) {
        echo "<h1>API Key</h1>";
        echo "<a href='/admin.php?action=regenapi'>Regenerate ALL API Keys! (WARNING)</a><br>";
        echo "<a href='/admin.php?action=regenapione&amp;t=TestUser'>Regenerate API Key for TestUser</a><br>";

        echo "<a href='/admin.php?action=errorlog'>ERROR LOG</a><br>";
    }

    if ($permissions >= \RA\Permissions::Root) {
        echo "<h1>Achievement Inspection/Interaction</h1>";
        echo "<a href='/admin.php?action=recalcdev'>Recalculate developer contribution totals! (1) (WARNING)</a><br>";
        echo "<a href='/admin.php?action=recalcsiteawards'>Recalculate site awards (developer contrib + FB)! (2) (WARNING)</a><br>";
        echo "<a href='/admin.php?action=reconstructsiteawards'>Reconstruct site awards (completed games)! (3) (WARNING)</a><br>";
    }

    if ($permissions >= \RA\Permissions::Admin) {
        echo "<h2>Get Game Achievement IDs</h2>";
        echo "<form method='post' action='admin.php'>";
        echo "Game ID<input type='text' name='g' value=''><br>";
        echo "<input type='hidden' name='action' value='getachids' />";
        echo "<input type='submit' value='Submit'/>";
        echo "</form>";
    }

    if ($permissions >= \RA\Permissions::Admin) {
        echo "<h2>Award Achievement</h2>";
        echo "<form method='post' action='admin.php'>";
        echo "User To Receive Achievement	<input type='text' name='u' value='$awardAchievementUser'><br>";
        echo "Achievement ID				<input type='text' name='a' value='$awardAchievementID'><br>";
        $checked = ($awardAchHardcore == 1) ? 'checked' : '';
        echo "Include hardcore?				<input type='checkbox' name='h' $checked value='1'><br>";
        echo "<input type='hidden' name='action' value='giveaward' />";
        echo "<input type='submit' value='Submit'/>";
        echo "</form>";
    }

    if ($permissions >= \RA\Permissions::Admin) {
        $eventAchievementID = $staticData['Event_AOTW_AchievementID'];
        $eventForumTopicID = $staticData['Event_AOTW_ForumID'];

        echo "<h2>Update Event</h2>";
        echo "<h3>Achievement of the Week</h3>";
        echo "<form method='post' action='admin.php'>";
        echo "Achievement ID<input type='text' name='a' value='$eventAchievementID'> <a href='/Achievement/$eventAchievementID'>Link</a><br>";
        echo "Forum Topic ID<input type='text' name='f' value='$eventForumTopicID'> <a href='/viewtopic.php?t=$eventForumTopicID'>Link</a><br>";
        echo "<input type='hidden' name='action' value='updatestaticdata' />";
        echo "<input type='submit' value='Submit'/>";
        echo "</form>";
    }
    ?>
</div>
</body>
<?php RenderHtmlEnd(); ?>
