<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Admin)) {
    //	Immediate redirect if we cannot validate user!	//TBD: pass args?
    header("Location: " . getenv('APP_URL'));
    exit;
}

$errorCode = seekGET('e');

$newsArticleID = seekGET('n');
$newsCount = getLatestNewsHeaders(0, 999, $newsData);
$activeNewsArticle = null;

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
        fseek($f, -mb_strlen($chunk), SEEK_CUR);

        // Decrease our line counter
        $lines -= mb_substr_count($chunk, "\n");
    }

    while ($lines++ < 0) {

        // Find first newline and remove all text before that
        $output = mb_substr($output, mb_strpos($output, "\n") + 1);
    }

    // Close file and return
    fclose($f);
    return trim($output);
}

$action = seekPOSTorGET('action');
$message = null;
switch ($action) {
    // case 'regenapi':
    //     $query = "SELECT User FROM UserAccounts";
    //     $dbResult = s_mysql_query($query);
    //
    //     $userList = '';
    //     while ($db_entry = mysqli_fetch_assoc($dbResult)) {
    //         $userList[] = $db_entry['User'];
    //     }
    //
    //     $numRegens = 0;
    //     foreach ($userList as $nextTempUser) {
    //         $newKey = generateAPIKey($nextTempUser);
    //         if ($newKey !== "") {
    //             $numRegens++;
    //         }
    //     }
    //
    //     echo "REGENERATED $numRegens APIKEYS!<br>";
    //     break;
    // case 'regenapione':
    //     $targetUser = seekGET('t');
    //     $newKey = generateAPIKey($targetUser);
    //     echo "New API Key for $targetUser: $newKey<br>";
    //     break;
    // case 'recalcdev':
    //     $query = "SELECT User FROM UserAccounts";
    //     $dbResult = s_mysql_query($query);
    //
    //     $userList = '';
    //     while ($db_entry = mysqli_fetch_assoc($dbResult)) {
    //         $userList[] = $db_entry['User'];
    //     }
    //
    //     $numRegens = 0;
    //     foreach ($userList as $nextTempUser) {
    //         $valid = recalculateDevelopmentContributions($nextTempUser);
    //         echo "$nextTempUser<br>";
    //         if ($valid) {
    //             $numRegens++;
    //         }
    //     }
    //
    //     echo "REGENERATED $numRegens developer contribution totals!<br>";
    //     break;
    // case 'reconstructsiteawards':
    //     $tgtPlayer = seekGET('t', null);
    //
    //     $query = "DELETE FROM SiteAwards WHERE AwardType = 1";
    //     if ($tgtPlayer !== null) {
    //         $query .= " AND User = '$tgtPlayer'";
    //     }
    //
    //     $dbResult = s_mysql_query($query);
    //
    //     $query = "SELECT User FROM UserAccounts";
    //     if ($tgtPlayer !== null) {
    //         $query .= " WHERE User = '$tgtPlayer'";
    //     }
    //
    //     $dbResult = s_mysql_query($query);
    //
    //     $userList = [];
    //     if ($dbResult !== false) {
    //         while ($db_entry = mysqli_fetch_assoc($dbResult)) {
    //             $userList[] = $db_entry['User'];
    //         }
    //     } else {
    //         echo "Error accessing UserAccounts";
    //         exit;
    //     }
    //
    //     $numAccounts = count($userList);
    //     for ($i = 0; $i < $numAccounts; $i++) {
    //         $user = $userList[$i];
    //
    //         echo "Updating $user...<br>";
    //
    //         $query = "	SELECT gd.ID AS GameID, c.Name AS ConsoleName, gd.ImageIcon, gd.Title, COUNT(ach.GameID) AS NumAwarded, inner1.MaxPossible, (COUNT(ach.GameID)/inner1.MaxPossible) AS PctWon , aw.HardcoreMode
    //             FROM Awarded AS aw
    //             LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
    //             LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
    //             LEFT JOIN
    //                 ( SELECT COUNT(*) AS MaxPossible, ach1.GameID FROM Achievements AS ach1 WHERE Flags=3 GROUP BY GameID )
    //                 AS inner1 ON inner1.GameID = ach.GameID AND inner1.MaxPossible > 5
    //             LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
    //             WHERE aw.User='$user' AND ach.Flags = 3
    //             GROUP BY ach.GameID, aw.HardcoreMode, gd.Title
    //             ORDER BY PctWon DESC, inner1.MaxPossible DESC, gd.Title";
    //
    //         $dbResult = s_mysql_query($query);
    //
    //         if ($dbResult !== false) {
    //             $listOfAwards = [];
    //
    //             while ($db_entry = mysqli_fetch_assoc($dbResult)) {
    //                 $listOfAwards[] = $db_entry;
    //                 //$nextElem = $db_entry;
    //                 //$nextGameID = $nextElem['GameID'];
    //                 // if( $nextElem['PctWon'] == 2.0 )
    //                 // {
    //                 // $gameTitle = $nextElem['Title'];
    //                 // echo "Mastered $gameTitle<br>";
    //                 // //	Add award:
    //                 // AddSiteAward( $user, 1, $nextElem['GameID'], 1 );
    //                 // }
    //                 // if( $nextElem['PctWon'] >= 1.0 )	//noooo!!!!
    //                 // {
    //                 // $gameTitle = $nextElem['Title'];
    //                 // echo "Completed $gameTitle<br>";
    //                 // //	Add award:
    //                 // AddSiteAward( $user, 1, $nextElem['GameID'], 0 );
    //                 // }
    //             }
    //
    //             $awardAddedHC = [];
    //
    //             foreach ($listOfAwards as $nextAward) {
    //                 if ($nextAward['HardcoreMode'] == 1) {
    //                     if ($nextAward['PctWon'] == 1.0) {
    //                         $gameTitle = $nextAward['Title'];
    //                         $gameID = $nextAward['GameID'];
    //                         echo "MASTERED $gameTitle<br>";
    //                         //	Add award:
    //                         AddSiteAward($user, 1, $gameID, 1);
    //
    //                         $awardAddedHC[] = $gameID;
    //                     }
    //                 }
    //             }
    //
    //             foreach ($listOfAwards as $nextAward) {
    //                 if ($nextAward['HardcoreMode'] == 0) {
    //                     //	Check it hasnt already been added as a non-HC award
    //                     if ($nextAward['PctWon'] == 1.0) {
    //                         $gameTitle = $nextAward['Title'];
    //                         $gameID = $nextAward['GameID'];
    //
    //                         if (!in_array($gameID, $awardAddedHC)) {
    //                             echo "Completed $gameTitle<br>";
    //                             //	Add award:
    //                             AddSiteAward($user, 1, $gameID, 0);
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //     }
    //     break;
    // case 'recalcsiteawards':
    //     $tgtPlayer = seekGET('t', null);
    //     {
    //         $query = "DELETE FROM SiteAwards WHERE ( AwardType = 2 || AwardType = 3 || AwardType = 5 )";
    //         if ($tgtPlayer !== null) {
    //             $query .= " AND User = '$tgtPlayer'";
    //         }
    //
    //         global $db;
    //         $unusedDBResult = mysqli_query($db, $query);
    //     }
    //     {
    //         $query = "SELECT User, ContribCount, ContribYield, fbUser FROM UserAccounts";
    //         if ($tgtPlayer !== null) {
    //             $query .= " WHERE User = '$tgtPlayer'";
    //         }
    //
    //         $dbResult = mysqli_query($db, $query);
    //
    //         $userList = '';
    //         while ($db_entry = mysqli_fetch_assoc($dbResult)) {
    //             $userList[] = [
    //                 $db_entry['User'],
    //                 $db_entry['ContribCount'],
    //                 $db_entry['ContribYield'],
    //                 $db_entry['fbUser'],
    //             ];
    //         }
    //
    //         $numRecalced = 0;
    //         foreach ($userList as $nextTempUser) {
    //             $nextUser = $nextTempUser[0];
    //             $nextCount = $nextTempUser[1];
    //             $nextYield = $nextTempUser[2];
    //             $nextFBUser = $nextTempUser[3];
    //
    //             for ($i = 0; $i < count(RA\AwardThreshold::DEVELOPER_COUNT_BOUNDARIES); $i++) {
    //                 if ($nextCount >= RA\AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$i]) {
    //                     //echo "$nextUser has $nextCount, greater than RA\AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[  $i ], addaward!<br>";
    //                     //This developer has arrived at this point boundary!
    //                     AddSiteAward($nextUser, 2, $i);
    //                     $numRecalced++;
    //                 }
    //             }
    //             for ($i = 0; $i < count(RA\AwardThreshold::DEVELOPER_POINT_BOUNDARIES); $i++) {
    //                 if ($nextYield >= RA\AwardThreshold::DEVELOPER_POINT_BOUNDARIES[$i]) {
    //                     //echo "$nextUser has yield of $nextYield, greater than RA\AwardThreshold::DEVELOPER_POINT_BOUNDARIES[$i], addaward!<br>";
    //                     //This developer is newly above this point boundary!
    //                     AddSiteAward($nextUser, 3, $i);
    //                     $numRecalced++;
    //                 }
    //             }
    //
    //             if (isset($nextFBUser) && mb_strlen($nextFBUser) > 2) {
    //                 echo "$nextUser has signed up for FB, add FB award!<br>";
    //                 AddSiteAward($nextUser, 5, 0);
    //             }
    //         }
    //
    //         echo "RECALCULATED $numRecalced site awards!<br>";
    //     }
    //     break;
    case 'getachids':
        $gameIDs = explode(',', seekPOST('g'));
        foreach ($gameIDs as $nextGameID) {
            $ids = getAchievementIDs($nextGameID);
            $message = implode(', ', $ids["AchievementIDs"] ?? []);
        }
        break;
    case 'giveaward':
        $awardAchievementID = seekPOST('a');
        $awardAchievementUser = seekPOST('u');
        $awardAchHardcore = seekPOST('h', 0);

        if (isset($awardAchievementID) && isset($awardAchievementUser)) {
            $usersToAward = preg_split('/\W+/', $awardAchievementUser);
            foreach ($usersToAward as $nextUser) {
                $validUser = validateUsername($nextUser);
                if (!$validUser) {
                    $message .= "<strong>$nextUser</strong>: user not found!<br>";
                    continue;
                }
                $message .= "<strong>$validUser</strong>:<br>";
                $ids = explode(',', $awardAchievementID);
                foreach ($ids as $nextID) {
                    $message .= "- $nextID: ";
                    $awardResponse = addEarnedAchievementJSON($validUser, $nextID, $awardAchHardcore);
                    if (empty($awardResponse) || !$awardResponse['Success']) {
                        $message .= array_key_exists('Error', $awardResponse)
                            ? $awardResponse['Error']
                            : "Failed to award achievement!";
                    } else {
                        $message .= "Awarded achievement";
                    }
                    $message .= "<br>";
                }
                recalcScore($validUser);
                $message .= "- Recalculated Score: " . GetScore($validUser) . "<br>";
            }
        }
        break;
    // case 'recalctrueratio':
    //     set_time_limit(3000);
    //
    //     $query = "SELECT MAX(ID) FROM GameData";
    //     $dbResult = s_mysql_query($query);
    //     $data = mysqli_fetch_assoc($dbResult);
    //     $numGames = $data['MAX(ID)'];
    //     for ($i = 1; $i <= $numGames; $i++) {
    //         // error_log("Recalculating TA for Game ID: $i<br>");
    //         echo "Recalculating TA for Game ID: $i<br>";
    //         recalculateTrueRatio($i);
    //
    //         ob_flush();
    //         flush();
    //
    //         //if( $i % 10 == 0 )
    //         //	sleep( 1 );
    //     }
    //
    //     // error_log("Recalc'd TA for $numGames games!");
    //     echo "Recalc'd TA for $numGames games!";
    //     exit;
    //     break;
    // case 'recalcplayerscores':
    //     set_time_limit(3000);
    //
    //     getUserList(1, 0, 99999, $userData, "");
    //
    //     echo "Recalc players scores: " . count($userData) . " to process...";
    //
    //     foreach ($userData as $nextUser) {
    //         echo "Player: " . $nextUser['User'] . " recalc (was TA: " . $nextUser['TrueRAPoints'] . ")<br>";
    //         recalcScore($nextUser['User']);
    //
    //         ob_flush();
    //         flush();
    //     }
    //
    //     // error_log("Recalc'd TA for " . count($userData) . " players!");
    //     echo "Recalc'd TA for " . count($userData) . " players!";
    //     exit;
    //     break;
    case 'updatestaticdata':
        $aotwAchID = seekPOSTorGET('a', 0, 'integer');
        $aotwForumID = seekPOSTorGET('f', 0, 'integer');
        $aotwStartAt = seekPOSTorGET('s', null, 'string');

        $query = "UPDATE StaticData SET
            Event_AOTW_AchievementID='$aotwAchID',
            Event_AOTW_ForumID='$aotwForumID',
            Event_AOTW_StartAt='$aotwStartAt'";

        $result = s_mysql_query($query);

        if ($result) {
            $message = "Successfully updated static data!";
        } else {
            $message = mysqli_error($db);
        }

        break;
    // case 'access_log':
    //     $accessLog = file_get_contents( "../../log/httpd/access_log" );
    //     echo $accessLog;
    //     exit;
    // case 'error_log':
    //     $log = file_get_contents( "../../log/httpd/error_log" );
    //     echo $log;
    //     exit;
    //     break;
    // case 'errorlog':
    //     $errorlogpath = "/var/log/httpd/error_log";
    //
    //     //$result = exec( "tail -n10 $errorlogpath");
    //     //var_dump( $result );
    //
    //     echo "<a href='/admin.php?action=errorlog&c=50'>Last 50</a> - ";
    //     echo "<a href='/admin.php?action=errorlog&c=100'>Last 100</a> - ";
    //     echo "<a href='/admin.php?action=errorlog&c=500'>Last 500</a><br><br>";
    //
    //     $count = seekPOSTorGET('c', 20, 'integer');
    //     echo nl2br(tailCustom($errorlogpath, $count));
    //     exit;
    //     break;
}

$staticData = getStaticData();

RenderHtmlStart();
RenderHtmlHead('Admin Tools');
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<script src="/vendor/jquery.datetimepicker.full.min.js"></script>
<link rel="stylesheet" href="/vendor/jquery.datetimepicker.min.css">
<div id="mainpage">

    <?php if ($message): ?>
        <div id="fullcontainer">
            <?= $message ?>
        </div>
    <?php endif ?>

    <?php
    // if ($permissions >= \RA\Permissions::Root) :
    //     echo "<h1>API Key</h1>";
    //     echo "<a href='/admin.php?action=regenapi'>Regenerate ALL API Keys! (WARNING)</a><br>";
    //     echo "<a href='/admin.php?action=regenapione&amp;t=TestUser'>Regenerate API Key for TestUser</a><br>";
    //
    //     echo "<a href='/admin.php?action=errorlog'>ERROR LOG</a><br>";

    //     echo "<h1>Achievement Inspection/Interaction</h1>";
    //     echo "<a href='/admin.php?action=recalcdev'>Recalculate developer contribution totals! (1) (WARNING)</a><br>";
    //     echo "<a href='/admin.php?action=recalcsiteawards'>Recalculate site awards (developer contrib + FB)! (2) (WARNING)</a><br>";
    //     echo "<a href='/admin.php?action=reconstructsiteawards'>Reconstruct site awards (completed games)! (3) (WARNING)</a><br>";
    // endif
    ?>
    <?php if ($permissions >= \RA\Permissions::Admin) : ?>
        <div id="fullcontainer">
            <h4>Get Game Achievement IDs</h4>
            <form method='post' action='admin.php'>
                <table class="mb-1">
                    <colgroup>
                        <col>
                        <col class="fullwidth">
                    </colgroup>
                    <tbody>
                    <tr>
                        <td class="text-nowrap">
                            <label for="achievements_game_id">Game ID</label>
                        </td>
                        <td>
                            <input id='achievements_game_id' name='g'>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <input type='hidden' name='action' value='getachids'>
                <input type='submit' value='Submit'>
            </form>
        </div>

        <div id='fullcontainer'>
            <h4>Award Achievement</h4>
            <form method='post' action='admin.php'>
                <table class="mb-1">
                    <colgroup>
                        <col>
                        <col class="fullwidth">
                    </colgroup>
                    <tbody>
                    <tr>
                        <td class="text-nowrap">
                            <label for="award_achievement_user">User to receive achievement</label>
                        </td>
                        <td>
                            <input id='award_achievement_user' name='u'>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap">
                            <label for="award_achievement_id">Achievement ID</label>
                        </td>
                        <td>
                            <input id='award_achievement_id' name='a'>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap">
                            <label for="award_achievement_hardcore">Include hardcore?</label>
                        </td>
                        <td>
                            <input id='award_achievement_hardcore' type='checkbox' name='h' value='1'>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <input type='hidden' name='action' value='giveaward'>
                <input type='submit' value='Submit'>
            </form>
        </div>

        <div id='fullcontainer'>
            <?php
            $eventAotwAchievementID = $staticData['Event_AOTW_AchievementID'] ?? null;
            $eventAotwStartAt = $staticData['Event_AOTW_StartAt'] ?? null;
            $eventAotwForumTopicID = $staticData['Event_AOTW_ForumID'] ?? null;
            ?>
            <h4>Achievement of the Week</h4>
            <form method='post' action='admin.php'>
                <table class="mb-1">
                    <colgroup>
                        <col>
                        <col>
                        <col class="fullwidth">
                    </colgroup>
                    <tbody>
                    <tr>
                        <td class="text-nowrap">
                            <label for='event_aotw_achievement_id'>Achievement ID</label>
                        </td>
                        <td>
                            <input id='event_aotw_achievement_id' name='a' value='<?= $eventAotwAchievementID ?>'>
                        </td>
                        <td>
                            <a href='/Achievement/<?= $eventAotwAchievementID ?>'>Link</a>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap">
                            <label for='event_aotw_start_at'>Start At (UTC time)</label>
                        </td>
                        <td>
                            <input id='event_aotw_start_at' name='s' value='<?= $eventAotwStartAt ?>'>
                        </td>
                        <td>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap">
                            <label for='event_aotw_forum_topic_id'>Forum Topic ID</label>
                        </td>
                        <td>
                            <input id='event_aotw_forum_topic_id' name='f' value='<?= $eventAotwForumTopicID ?>'>
                        </td>
                        <td>
                            <a href='/viewtopic.php?t=<?= $eventAotwForumTopicID ?>'>Link</a>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <input type='hidden' name='action' value='updatestaticdata'>
                <input type='submit' value='Submit'>
            </form>

            <div id="aotw_entries"></div>

            <script>
                jQuery('#event_aotw_start_at').datetimepicker({
                    format: 'Y-m-d H:i:s',
                    mask: true, // '9999/19/39 29:59' - digit is the maximum possible for a cell
                });
            </script>
        </div>
    <?php endif ?>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
