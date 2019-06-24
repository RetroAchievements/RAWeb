<?php

use RA\ActivityType;
use RA\Permissions;

require_once(__DIR__ . '/../bootstrap.php');
//////////////////////////////////////////////////////////////////////////////////////////
//    Achievement Accessors
//////////////////////////////////////////////////////////////////////////////////////////
//    00:54 21/03/2013
function getAchievementFeedData(
    $id,
    &$titleOut,
    &$descOut,
    &$pointsOut,
    &$badgeFilenameOut,
    &$gameTitleOut,
    &$gameIDOut,
    &$consoleNameOut
) {
    settype($id, "integer");

    //    Updated: embed gametitle, console
    $query = "SELECT a.Title, a.Description, a.Points, a.BadgeName, g.Title AS GameTitle, g.ID AS GameID, c.Name AS ConsoleName FROM Achievements AS a ";
    $query .= "LEFT JOIN GameData AS g ON g.ID = a.GameID ";
    $query .= "LEFT JOIN Console AS c ON c.ID = g.ConsoleID ";
    $query .= "WHERE a.ID='$id'";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        error_log($query);
        error_log(__FUNCTION__ . " failed! $id");
        return false;
    }

    $data = mysqli_fetch_assoc($dbResult);
    if ($data == false) {
        error_log($query);
        error_log(__FUNCTION__ . " failed! $id");
        return false;
    }

    $titleOut = $data['Title'];
    $descOut = $data['Description'];
    $pointsOut = $data['Points'];
    $badgeFilenameOut = $data['BadgeName'];
    $gameTitleOut = $data['GameTitle'];
    $gameIDOut = $data['GameID'];
    $consoleNameOut = $data['ConsoleName'];

    return true;
}

//    00:52 23/02/2013
function getAchievementTitle($id, &$gameTitleOut, &$gameIDOut)
{
    settype($id, "integer");

    //    Updated: embed gametitle
    $query = "SELECT a.Title, g.Title AS GameTitle, g.ID as GameID FROM Achievements AS a ";
    $query .= "LEFT JOIN GameData AS g ON g.ID = a.GameID ";
    $query .= "WHERE a.ID = '$id'";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        error_log($query);
        error_log(__FUNCTION__ . " fail on query (id:$id)");
        return "";
    }

    $data = mysqli_fetch_assoc($dbResult);
    if ($data == false) {
        error_log($query);
        error_log(__FUNCTION__ . " no results (id:$id)");
        return "";
    }

    $gameTitleOut = $data['GameTitle'];
    $gameIDOut = $data['GameID'];

    return $data['Title'];
}

//    08:22 04/11/2014
function GetAchievementData($id)
{
    settype($id, "integer");
    $query = "SELECT * FROM Achievements WHERE ID=$id";
    $dbResult = s_mysql_query($query);

    if ($dbResult == false || mysqli_num_rows($dbResult) != 1) {
        error_log($query);
        error_log(__FUNCTION__ . " failed: Achievement $id doesn't exist!");

        return null;
    } else {
        return mysqli_fetch_assoc($dbResult);
    }
}

function getAchievementsList($consoleIDInput, $user, $sortBy, $params, $count, $offset, &$dataOut, $achFlags = 3)
{
    return getAchievementsListByDev(null, $consoleIDInput, $user, $sortBy, $params, $count, $offset, $dataOut,
        $achFlags);
}


function getAchievementsListByDev(
    $dev = null,
    $consoleIDInput,
    $user,
    $sortBy,
    $params,
    $count,
    $offset,
    &$dataOut,
    $achFlags = 3
) {
    settype($sortBy, 'integer');

    $achCount = 0;

    $innerJoin = "";
    if ($params > 0 && $user !== null) {
        $innerJoin = "LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID AND aw.User = '$user'";
    }

    $query = "SELECT ach.ID, ach.Title AS AchievementTitle, ach.Description, ach.Points, ach.TrueRatio, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, gd.ConsoleID, c.Name AS ConsoleName
                FROM Achievements AS ach
                $innerJoin
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                LEFT JOIN Console AS c ON c.ID = gd.ConsoleID ";

    if (isset($achFlags)) {
        settype($achFlags, 'integer');
        $query .= "WHERE ach.Flags=$achFlags AND ach.TrueRatio > 0 ";

        if ($params == 1) {
            $query .= "AND ( !ISNULL( aw.User ) ) AND aw.HardcoreMode = 0 ";
        } elseif ($params == 2) {
            $query .= "AND ( ISNULL( aw.User ) )  ";
        } else {
            //    Ignore
        }

        if (isset($dev)) {
            $query .= "AND ach.Author = '$dev' ";
        }
    } elseif (isset($dev)) {
        $query .= "WHERE ach.Author = '$dev' ";
    }

    switch ($sortBy) {
        case 0:
        case 1:
            $query .= "ORDER BY ach.Title ";
            break;
        case 2:
            $query .= "ORDER BY ach.Description ";
            break;
        case 3:
            $query .= "ORDER BY ach.Points, GameTitle ";
            break;
        case 4:
            $query .= "ORDER BY ach.TrueRatio, GameTitle ";
            break;
        case 5:
            $query .= "ORDER BY ach.Author ";
            break;
        case 6:
            $query .= "ORDER BY GameTitle ";
            break;
        case 7:
            $query .= "ORDER BY ach.DateCreated ";
            break;
        case 8:
            $query .= "ORDER BY ach.DateModified ";
            break;
        case 11:
            $query .= "ORDER BY ach.Title DESC ";
            break;
        case 12:
            $query .= "ORDER BY ach.Description DESC ";
            break;
        case 13:
            $query .= "ORDER BY ach.Points DESC, GameTitle ";
            break;
        case 14:
            $query .= "ORDER BY ach.TrueRatio DESC, GameTitle ";
            break;
        case 15:
            $query .= "ORDER BY ach.Author DESC ";
            break;
        case 16:
            $query .= "ORDER BY GameTitle DESC ";
            break;
        case 17:
            $query .= "ORDER BY ach.DateCreated DESC ";
            break;
        case 18:
            $query .= "ORDER BY ach.DateModified DESC ";
            break;
    }

    $query .= "LIMIT $offset, $count ";

    //error_log( $query );
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$achCount] = $db_entry;
            $achCount++;
        }
    } else {
        error_log(__FUNCTION__);
        error_log($query);
    }

    return $achCount;
}

function GetAchievementMetadataJSON($achID)
{
    $retVal = [];
    settype($achID, 'integer');

    $query = "SELECT ach.ID AS AchievementID, ach.GameID, ach.Title AS AchievementTitle, ach.Description, ach.Points, ach.TrueRatio,
                ach.Flags, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.DisplayOrder, ach.AssocVideo, ach.MemAddr,
                c.ID AS ConsoleID, c.Name AS ConsoleName, g.Title AS GameTitle, g.ImageIcon AS GameIcon
              FROM Achievements AS ach
              LEFT JOIN GameData AS g ON g.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = g.ConsoleID
              WHERE ach.ID = $achID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
        $retVal = mysqli_fetch_assoc($dbResult);
    } else {
        error_log($query);
        error_log(__FUNCTION__ . " failed1: ID $achID requested");
    }

    return $retVal;
}

//    01:16 23/02/2013
function GetAchievementMetadata($achievementID, &$dataOut)
{
    $dataOut = GetAchievementMetadataJSON($achievementID);
    return (count($dataOut) > 0);
}

//    00:59 23/02/2013
function getAchievementBadgeFilename($id)
{
    $query = "SELECT BadgeName FROM Achievements WHERE ID = '$id'";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        error_log($query);
        error_log(__FUNCTION__ . " bad request for id: $id");
        return "00000";
    }

    $data = mysqli_fetch_assoc($dbResult);
    return $data['BadgeName'];
}

function ValidationPass($key, $user, $achID)
{
    // $userToken = getUserAppToken( $user );
    // $testValidInput = sprintf( "%d,%d-%s.%s-%d132%s2A%slLIA", $achIDToAward, (strlen($user)*3)+1, $user, $userToken, $achIDToAward, $user, "WOAHi2" );
    // $validationTestMd5 = md5( $testValidInput );
    //if( $validationTestMd5 !== $validation )
    //{
    //    echo "FAILED: Achievement doesn't exist? (validation $validationTestMd5 !== $validation from $testValidInput )";
    //    error_log( __FUNCTION__ . " validation failed: $achIDToAward, $user, $achIDToAward, $fbUser, $userToken, >>$testValidInput<< -> $validationTestMd5 !== $validation " );
    //    return FALSE;
    //}{

    return true; //    TBD
}

function InsertAwardedAchievementDB($user, $achIDToAward, $isHardcore)
{
    //error_log( "InsertAwardedAchievementDB, $user, $achIDToAward, $isHardcore" );

    $query = "INSERT INTO Awarded ( User, AchievementID, Date, HardcoreMode )
              VALUES ( '$user', '$achIDToAward', NOW(), '$isHardcore' )
              ON DUPLICATE KEY
              UPDATE User=User, AchievementID=AchievementID, Date=Date, HardcoreMode=HardcoreMode";

    log_sql($query);
    $dbResult = s_mysql_query($query);
    return ($dbResult !== false);    //    FALSE return value ALWAYS means error here.
}

function HasAward($user, $achIDToAward)
{
    $retVal = array();
    $retVal['HasRegular'] = false;
    $retVal['HasHardcore'] = false;

    $query = "SELECT HardcoreMode
              FROM Awarded
              WHERE AchievementID = '$achIDToAward' AND User = '$user'";

    $dbResult = s_mysql_query($query);
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        if ($nextData['HardcoreMode'] == 0) {
            $retVal['HasRegular'] = true;
        } elseif ($nextData['HardcoreMode'] == 1) {
            $retVal['HasHardcore'] = true;
        }
    }

    return $retVal;
}

function CrossPostToSocial($userData, $activityType, $data)
{
    if ($userData['fbUser'] == 0) {
        //    FB not set
    } else {
        switch ($activityType) {
            case ActivityType::EarnedAchivement:
                {
                    //    Ensure the user wants to post this!
                    if (($userData['fbPrefs'] & FBUserPref::PostFBOn_EarnAchievement) != 0) {
                        //    Post ach:
                        //    Data should be fully contained as assoc array:
                        //$data['AchievementID']
                    }
                }
                break;

            case ActivityType::CompleteGame:
                {
                    //    Ensure the user wants to post this!
                    if (($userData['fbPrefs'] & FBUserPref::PostFBOn_CompleteGame) != 0) {
                        //    Post about game:
                        //    get game ID from $associatedID
                    }
                }
                break;

            default:
                break;
        }
    }

    if (!isset($userData['twitterUser'])) {
        //    Twitter not set
    } else {
        //    TBD
    }
}

function AddEarnedAchievementJSON($user, $achIDToAward, $isHardcore, $validationKey)
{
    //error_log( "AddEarnedAchievementJSON, $user, $achIDToAward, $isHardcore, $validationKey" );

    settype($achIDToAward, 'integer');
    settype($isHardcore, 'integer');

    $retVal = array();
    $retVal['Success'] = true;

    if (!ValidationPass($validationKey, $user, $achIDToAward)) {
        $retVal['Success'] = false;
        $retVal['Error'] = "Validation failed!";
    } elseif ($achIDToAward == 0) {
        $retVal['Success'] = false;
        $retVal['Error'] = "Achievement ID is 0! Cannot award.";
    } elseif (!isset($user) || strlen($user) < 2) {
        $retVal['Success'] = false;
        $retVal['Error'] = "User is '$user', cannot award achievement.";
    } else {
        $achData = GetAchievementMetadataJSON($achIDToAward);
        $userData = GetUserData($user);

        if ($achData == null) {
            $retVal['Success'] = false;
            $retVal['Error'] = "Achievement data cannot be found for $achIDToAward";
        } elseif ($userData == null) {
            $retVal['Success'] = false;
            $retVal['Error'] = "User data cannot be found for $user";
        } elseif ($achData['Flags'] == 5) // do not award Unofficial achievements
        {
            $retVal['Success'] = false;
            $retVal['Error'] = "Unofficial achievements aren't registered on the RetroAchievements.org database";
        } else {
            $hasAwardTypes = HasAward($user, $achIDToAward);
            $hasRegular = $hasAwardTypes['HasRegular'];
            $hasHardcore = $hasAwardTypes['HasHardcore'];

            if (($isHardcore && $hasHardcore) || (!$isHardcore && $hasRegular)) {
                $retVal['Success'] = false;
                if ($isHardcore) {
                    $retVal['Error'] = "User already has hardcore and regular achievements awarded.";
                } else {
                    $retVal['Error'] = "User already has this achievement awarded.";
                }
            } else {
                //error_log( "AddEarnedAchievementJSON, Ready to add" );

                $awardedOK = InsertAwardedAchievementDB($user, $achIDToAward, $isHardcore);
                if ($awardedOK && $isHardcore) {
                    $awardedOK |= InsertAwardedAchievementDB($user, $achIDToAward, false);
                }

                if ($awardedOK == false) {
                    $retVal['Success'] = false;
                    $retVal['Error'] = "Issues allocating awards for user?";
                } else {
                    $pointsToGive = $achData['Points'];
                    settype($pointsToGive, 'integer'); //    Safety

                    if ($isHardcore && !$hasRegular) {
                        //    Double points (award base as well!)
                        $pointsToGive *= 2;
                    }

                    $query = "UPDATE UserAccounts SET RAPoints=RAPoints+$pointsToGive WHERE User='$user'";
                    //error_log( $query );
                    $dbResult = s_mysql_query($query);
                    if ($dbResult == false) {
                        //    Could not add points?!
                        $retVal['Success'] = false;
                        $retVal['Error'] = "Could not add points for this user?";
                        error_log(__FUNCTION__ . " failed: cannot add new achievement to DB! $user, $achIDToAward");
                    } else {
                        //    Achievements all awarded. Now housekeeping (no error handling?)

                        static_setlastearnedachievement($achIDToAward, $user, $achData['Points']);

                        if ($user != $achData['Author']) {
                            attributeDevelopmentAuthor($achData['Author'], $pointsToGive);
                        }

                        //    Update GameData
                        //    Removed: this needs rethinking! //##SD TBD
                        //RecalculateTrueRatio( $gameID );    //    Heavy!
                        //    Add TA to the player for this achievement, NOW that the TA value has been recalculated
                        //    Select the NEW TA from this achievement, as it has just been recalc'd
                        $query = "SELECT TrueRatio
                                  FROM Achievements
                                  WHERE ID='$achIDToAward'";
                        $dbResult = s_mysql_query($query);
                        SQL_ASSERT($dbResult);

                        $data = mysqli_fetch_assoc($dbResult);
                        $newTA = $data['TrueRatio'];
                        settype($newTA, 'integer');

                        //    Pack back into $achData
                        $achData['TrueRatio'] = $newTA;

                        $query = "UPDATE UserAccounts
                                  SET TrueRAPoints=TrueRAPoints+$newTA
                                  WHERE User='$user'";
                        $dbResult = s_mysql_query($query);
                        SQL_ASSERT($dbResult);

                        postActivity($user, ActivityType::EarnedAchivement, $achIDToAward, $isHardcore);

                        testFullyCompletedGame($user, $achIDToAward, $isHardcore);

                        $socialData = array();
                        $socialData['User'] = $user;
                        $socialData['Points'] = $userData['RAPoints'] + $pointsToGive;
                        $socialData['AchievementData'] = $achData; //Passthru
                        $socialData['Hardcore'] = $isHardcore;
                        CrossPostToSocial($userData, ActivityType::EarnedAchivement, $socialData);
                    }
                }
            }
        }
    }

    return $retVal;
}

//    01:09 23/02/2013
function addEarnedAchievement(
    $userIn,
    $validation,
    $achIDToAward,
    $fbUser,
    &$newPointTotal,
    $isHardcore = 0,
    $silent = false
) {
    $user = correctUserCase($userIn);

    //    Sanitise!
    settype($achIDToAward, "integer");
    if ($achIDToAward == 0) {
        echo "FAILED: Achievement doesn't exist?";
        error_log(__FUNCTION__ . " failed: ID 0 requested! user:$user, validation:$validation, achIDToAward:$achIDToAward, fbUser:$fbUser");
        return false;
    }

    //    Validate a given hash for uploading an achievement:
    //    validation is md5 of
    //     "%d,%d-%s.%s-%d132%s2A%slLIA", nID, (strlen(sUser)*3)+1, sUser, sToken, nID, sUser, "WOAHi2"
    //$userToken = getUserAppToken( $user );
    //$testValidInput = sprintf( "%d,%d-%s.%s-%d132%s2A%slLIA", $achIDToAward, (strlen($user)*3)+1, $user, $userToken, $achIDToAward, $user, "WOAHi2" );
    //$validationTestMd5 = md5( $testValidInput );
    //if( $validationTestMd5 !== $validation )
    //{
    //    echo "FAILED: Achievement doesn't exist? (validation $validationTestMd5 !== $validation from $testValidInput )";
    //    error_log( __FUNCTION__ . " validation failed: $achIDToAward, $user, $achIDToAward, $fbUser, $userToken, >>$testValidInput<< -> $validationTestMd5 !== $validation " );
    //    return FALSE;
    //}

    $returnVal = false;

    //    Fetch achievement details:
    $query = "SELECT Points, Author, GameID, TrueRatio
              FROM Achievements
              WHERE ID=$achIDToAward";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false || mysqli_num_rows($dbResult) !== 1) {
        echo "FAILED: Achievement doesn't exist?";
        error_log(__FUNCTION__ . " failed: Achievement $achIDToAward doesn't exist! $user, $achIDToAward, $fbUser");
    } else {
        $db_entry = mysqli_fetch_assoc($dbResult);
        if ($db_entry == false) {
            echo "FAILED: Could not read result from DB?";
            error_log(__FUNCTION__ . " failed: Could not read result from DB! $user, $achIDToAward, $fbUser");
        } else {
            //    Add new achievement to Awarded:
            $gameID = $db_entry['GameID'];
            $achTrueRatio = $db_entry['TrueRatio'];

            $query = "INSERT INTO Awarded VALUES ( '$user', $achIDToAward, NOW(), $isHardcore ) ON DUPLICATE KEY UPDATE Date = NOW()";
            log_sql($query);
            if (s_mysql_query($query) == false) {
                if ($silent == false) {
                    //    Note: this should still just work, now we have "ON DUPLICATE KEY UPDATE"
                    log_sql_fail();
                    echo "FAILED: cannot add new achievement to DB! (already added?)";
                    error_log(__FUNCTION__ . " failed: cannot add new achievement to DB! (already added?)! $user, $achIDToAward, $fbUser, $isHardcore");
                } else {
                    //error_log( __FUNCTION__ . " attempted to add achievement but couldn't: error silently: $user, $achIDToAward, $fbUser, $isHardcore. Nb. Could be attempting to add non-HC where it already exists. This may be OK" );
                }
            } else {
                $points = $db_entry['Points'];
                $author = $db_entry['Author'];

                settype($points, "integer");

                static_setlastearnedachievement($achIDToAward, $user, $points);

                //    Update my score total:
                //$query = "UPDATE UserAccounts SET RAPoints=RAPoints+$points WHERE User='$user'";

                $userPoints = 0;
                $userTA = 0;
                $query = "SELECT RAPoints, TrueRAPoints From UserAccounts WHERE User='$user'";
                $dbResult = s_mysql_query($query);

                if ($dbResult !== false) {
                    $db_entry = mysqli_fetch_assoc($dbResult);
                    $userPoints = $db_entry['RAPoints'];
                    settype($userPoints, 'integer');
                    $userTA = $db_entry['TrueRAPoints'];
                    settype($userTA, 'integer');
                }

                if ($userIn != $author) {
                    attributeDevelopmentAuthor($author, $points);
                }

                //    Fetch the new point total to send back
                $newPointTotal = ($userPoints + $points);

                $query = "UPDATE UserAccounts SET RAPoints=$newPointTotal WHERE User='$user'";
                log_sql($query);
                if (s_mysql_query($query) == false) {
                    //    Could not add points?!
                    echo "FAILED: cannot add points?!";
                    error_log(__FUNCTION__ . " failed: cannot add new achievement to DB! (already added?)! $user, $achIDToAward, $fbUser, $points");
                } else {
                    if ($isHardcore) {
                        //    Ensure the player has the base-level achievement now too
                        addEarnedAchievement($userIn, $validation, $achIDToAward, $fbUser, $newPointTotal, 0, true);
                    }


                    //    Update GameData
                    RecalculateTrueRatio($gameID); //    Heavy!
                    //    Add TA to the player for this achievement, NOW that the TA value has been recalculated
                    //    Select the NEW TA from this achievement, as it has just been recalc'd
                    $query = "SELECT TrueRatio FROM Achievements WHERE ID='$achIDToAward'";
                    $dbResult = s_mysql_query($query);
                    SQL_ASSERT($dbResult);

                    $data = mysqli_fetch_assoc($dbResult);

                    $userNewTA = $data['TrueRatio'] + $userTA;

                    $query = "UPDATE UserAccounts SET TrueRAPoints=$userNewTA WHERE User='$user'";
                    $dbResult = s_mysql_query($query);
                    SQL_ASSERT($dbResult);


                    $returnVal = true;

                    if ($silent == false) {
                        //    For app
                        echo "OK";
                    }

                    if ($silent == false) {
                        postActivity($user, ActivityType::EarnedAchivement, $achIDToAward, $isHardcore);
                    }

                    testFullyCompletedGame($user, $achIDToAward, $isHardcore);

                    if ($silent == false) {
                        if ($fbUser == 0) {
                            echo ":FBNA"; //    Not associated
                        } else {
                            //    Attempt post on FB:
                            global $fbConn;
                            if ($fbConn == false) {
                                echo ":FBDC"; //    Disconnected (?)
                                error_log(__FUNCTION__ . " failed: cannot connect to FB? $user, $achIDToAward, $fbUser, $points");
                            } else {
                                //$wallMsg = "I just earned  $title  on $game for $points points on RetroAchievements.org!";
                                //$linkTo = "https://retroachievements.org/Users/$User.html";
                                //$linkTo = getenv('APP_URL');
                                //$linkTo = '';
                                //$params = array(
                                //    'access_token'=>'490904194261313|WGR9vR4fulyLxEufSRH2CJrthHw',
                                //    'url'=>getenv('APP_URL'),
                                //    'image'=>getenv('APP_URL').'/Trophy1-96.png',
                                //    'message'=>$wallMsg,
                                //    'link'=>$linkTo,
                                //    'caption'=>$title,
                                //    'title'=>$title,
                                //    'description'=>$desc );

                                $access_token = '490904194261313|ea6341e18635a588bab539281e798b97';
                                $params = array(
                                    'access_token' => $access_token,
                                    'achievement' => getenv('APP_URL') . "/Achievement/$achIDToAward"
                                );

                                try {
                                    //$ret_obj = $fbConn->api( "/$fbUser/feed", 'POST', $params );
                                    $message = "/$fbUser/retroachievements:earn?access_token=$access_token";
                                    //echo "<br/>DEBUG:<br/>" . $message . "<br/>" . $params . "<br/>";

                                    $ret_obj = $fbConn->api($message, 'POST', $params);
                                    //echo '<pre>Post ID: ' . $ret_obj['id'] . '</pre>';

                                    error_log("Posted OK to FB for $user ($fbUser) $ret_obj");

                                    echo ":FBOK"; //    Posted OK!
                                } catch (FacebookApiException $e) {
                                    // If the user is logged out, you can have a
                                    // user ID even though the access token is invalid.
                                    // In this case, we'll get an exception, so we'll
                                    // just ask the user to login again here.
                                    //$login_url = $fbConn->getLoginUrl( array( 'scope' => 'publish_stream' ) );
                                    //global $config;
                                    //echo $login_url . "<br/>";
                                    //echo $config['appId'] . "<br/>";
                                    //echo $config['secret'] . "<br/>";
                                    //echo $config['cookie'] . "<br/>";
                                    //echo "fbConn " . fbConn!==FALSE;
                                    //echo 'Please <a href="' . $login_url . '">login.</a><br/>';
                                    error_log($e->getType());
                                    error_log($e->getMessage());

                                    //echo "ERROR: " . $e->getType() . "<br/>";
                                    //echo "ERROR: " . $e->getMessage() . "<br/>";
                                    //echo ":FBER";    //    Error!
                                    error_log(__FUNCTION__ . " failed: fbConn->api exception: $user, $achIDToAward, $fbUser, $points");
                                    echo ":FBER"; //    Posted OK!
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return $returnVal;
}

//    18:27 23/02/2013
function UploadNewAchievement(
    $author,
    $gameID,
    $title,
    $desc,
    $progress,
    $progressMax,
    $progressFmt,
    $points,
    $mem,
    $type,
    &$idInOut,
    $badge,
    &$errorOut
) {
    //    Hack for 'development tutorial game'
    if ($gameID == 10971) {
        $errorOut = "Tutorial: Achievement upload! This reply is happening on the server, to say that we have successfully received your achievement data.";
        return false;
    }

    if ($type == 3 && !isValidConsoleID(GetGameData($gameID)['ConsoleID'])) {
        $errorOut = "You cannot promote achievements for a game from an unsupported console (console ID: " . GetGameData($gameID)['ConsoleID'] . ").";
        return false;
    }

    $title = str_replace("'", "''", $title);
    $desc = str_replace("'", "''", $desc);
    $title = str_replace("/", "_", $title);
    $desc = str_replace("/", "_", $desc);
    $title = str_replace("\\", "_", $title);
    $desc = str_replace("\\", "_", $desc);
    $title = preg_replace('/[^\x20-\x7e]/', '_', $title);
    $desc = preg_replace('/[^\x20-\x7e]/', '_', $desc);

    //    Assume authorised!
    if (!isset($idInOut) || $idInOut == 0) {
        $query = "INSERT INTO Achievements VALUES ( NULL, '$gameID', '$title', '$desc', '$mem', '$progress', '$progressMax', '$progressFmt', '$points', '$type', '$author', NOW(), NOW(), 0, 0, '$badge', 0, NULL, 0 )";
        log_sql($query);
        if (s_mysql_query($query) !== false) {
            global $db;
            $idInOut = mysqli_insert_id($db);
            postActivity($author, ActivityType::UploadAchievement, $idInOut);

            static_addnewachievement($idInOut);
            addArticleComment("Server", 2, $idInOut, "$author uploaded this achievement.");

            error_log(__FUNCTION__ . " $author uploaded new achievement: $idInOut, $title, $desc, $progress, $progressMax, $progressFmt, $points, $mem, $type, $badge");

            return true;
        } else {
            log_sql_fail();
            error_log($query);
            error_log(__FUNCTION__ . " failed: gameID:$gameID title:$title desc:$desc points:$points mem:$mem type:$type");

            return false;
        }
    } else {
        $query = "SELECT Flags, Points FROM Achievements WHERE ID='$idInOut'";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
            $data = mysqli_fetch_assoc($dbResult);

            $changingAchSet = ($data['Flags'] != $type);
            $changingPoints = ($data['Points'] != $points);

            //if( ( $changingAchSet || $changingPoints ) && $type == 3 )
            if ($type == 3) {
                $userPermissions = getUserPermissions($author);
                error_log("changing ach set detected; user is $author, permissions is $userPermissions, target set is $type");
                if ($userPermissions < Permissions::Developer) {
                    //  Must be developer to modify core!
                    $errorOut = "You must be a developer to modify values in Core! Please drop a message in the chat/forums to apply.";
                    return false;
                }
            }

            $query = "UPDATE Achievements SET Title='$title', Description='$desc', Progress='$progress', ProgressMax='$progressMax', ProgressFormat='$progressFmt', MemAddr='$mem', Points=$points, Flags=$type, DateModified=NOW(), BadgeName='$badge' WHERE ID=$idInOut";
            log_sql($query);

            if (s_mysql_query($query) !== false) {
                if ($changingAchSet || $changingPoints) {
                    //    When changing achievement set, all existing achievements that rely on this should be purged.
                    //$query = "DELETE FROM Awarded WHERE ID='$idInOut'";
                    //error_log( $query );
                    // nah, that's a bit harsh... esp if you're changing something tiny like the badge!!

                    if (s_mysql_query($query) !== false) {
                        global $db;
                        $rowsAffected = mysqli_affected_rows($db);
                        //error_log( __FUNCTION__ . " removed $rowsAffected rows in Achieved" );
                        //great
                    } else {
                        //meh
                    }
                }

                static_setlastupdatedgame($gameID);
                static_setlastupdatedachievement($idInOut);

                postActivity($author, ActivityType::EditAchievement, $idInOut);

                if ($changingAchSet) {
                    if ($type == 3) {
                        addArticleComment("Server", 2, $idInOut, "$author promoted this achievement to the Core set.");
                    } elseif ($type == 5) {
                        addArticleComment("Server", 2, $idInOut, "$author demoted this achievement to Unofficial.");
                    }
                } else {
                    addArticleComment("Server", 2, $idInOut, "$author edited this achievement.");
                }

                return true;
            } else {
                error_log($query);
                error_log(__FUNCTION__ . " 3failed: gameID:$gameID title:$title desc:$desc points:$points mem:$mem type:$type ID:$idInOut");

                return false;
            }
        } else {
            error_log(__FUNCTION__ . " 2failed: ach to update doesn't exist? gameID:$gameID title:$title desc:$desc points:$points mem:$mem type:$type ID:$idInOut");

            return false;
        }
    }
}

//    17:47 14/05/2013
function resetAchievements($user, $gameID)
{
    //$query = "SELECT COUNT(*) AS NumAchievements FROM Awarded WHERE User='$user'";
    $query = "DELETE FROM Awarded WHERE User='$user' ";

    $pointsToRemove = 0;
    if (isset($gameID) && $gameID !== 0) {
        $achievementData = [];
        $gameData = [];
        getGameMetadata($gameID, $user, $achievementData, $gameData);
        foreach ($achievementData as $nextAch) {
            if (isset($nextAch['DateAwarded1'])) {
                $pointsToRemove += $nextAch['Points'];
            }
        }

        $query .= "AND AchievementID IN ( SELECT ID FROM Achievements WHERE Achievements.GameID='$gameID' )";
    }

    $numRowsDeleted = 0;

    log_sql($query);
    if (s_mysql_query($query) !== false) {
        global $db;
        $numRowsDeleted = mysqli_affected_rows($db);
        error_log(__FUNCTION__ . " Success - deleted $numRowsDeleted achievements for $user.");
        //echo "SUCCESS! Deleted " . $numRowsDeleted . " achievements.<br/>";

        if (!isset($gameID) || $gameID == 0) {
            //    remove stored points if we're doing a total reset
            $query = "UPDATE UserAccounts SET RAPoints='0' WHERE User='$user'";
            log_sql($query);
            if (s_mysql_query($query) == false) {
                log_email(__FUNCTION__ . " Errors removing RAPoints for $user");
            }
        } elseif ($pointsToRemove > 0) {
            //    remove achieved points if we're doing a game reset
            $query = "UPDATE UserAccounts SET RAPoints=RAPoints-$pointsToRemove WHERE User='$user'";
            log_sql($query);
            if (s_mysql_query($query) == false) {
                log_email(__FUNCTION__ . " Errors adjusting RAPoints for $user");
            }
        }
    } else {
        error_log(__FUNCTION__ . " Delete op failed (no permissions?)!");
        //echo "Delete op failed (no permissions?)!<br/>";
    }

    return $numRowsDeleted;
}

function resetSingleAchievement($user, $achID, $hardcoreMode)
{
    $achData = [];
    if (getAchievementMetadata($achID, $achData)) {
        $pointsToDeduct = $achData['Points'];
        $query = "UPDATE UserAccounts SET RAPoints=RAPoints-$pointsToDeduct WHERE User='$user'";
        $dbResult = s_mysql_query($query);
        if ($dbResult == false) {
            error_log($query);
            error_log(__FUNCTION__ . " failed?! $user, $achID");
        }

        $query = "DELETE FROM Awarded WHERE User='$user' AND AchievementID='$achID' AND HardcoreMode='$hardcoreMode'";
        $dbResult = s_mysql_query($query);

        if ($dbResult == false) {
            error_log($query);
            error_log(__FUNCTION__ . " failed?! $user, $achID");
        }

        return true;
    } else {
        error_log(__FUNCTION__ . " couldn't find achievement $achID!");
        return false;
    }
}

function getRecentlyEarnedAchievements($count, $user, &$dataOut)
{
    settype($count, 'integer');

    $query = "SELECT aw.User, aw.Date AS DateAwarded, aw.AchievementID, ach.Title, ach.Description, ach.BadgeName, ach.Points, ach.GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, c.Name AS ConsoleTitle
               FROM Awarded AS aw
               LEFT JOIN Achievements ach ON aw.AchievementID = ach.ID
               LEFT JOIN GameData gd ON ach.GameID = gd.ID
               LEFT JOIN Console AS c ON c.ID = gd.ConsoleID ";

    if (isset($user) && $user !== false) {
        $query .= "WHERE User='$user' AND HardcoreMode=0 ";
    } else {
        $query .= "WHERE HardcoreMode=0 ";
    }

    $query .= "ORDER BY Date DESC
                LIMIT 0, $count";

    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        error_log($query);
        error_log(__FUNCTION__ . " failed: no achievements found: count:$count user:$user query:$query");
        return 0;
    } else {
        $i = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$i] = $db_entry;
            $i++;
        }
        return $i;
    }
}

function GetAchievementsPatch($gameID, $flags)
{
    $retVal = array();

    $flagsCond = "TRUE";
    if ($flags != 0) {
        $flagsCond = "Flags='$flags'";
    }

    $query = "SELECT ID, MemAddr, Title, Description, Points, Author, UNIX_TIMESTAMP(DateModified) AS Modified, UNIX_TIMESTAMP(DateCreated) AS Created, BadgeName, Flags
              FROM Achievements
              WHERE GameID='$gameID' AND $flagsCond
              ORDER BY DisplayOrder";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            settype($db_entry['ID'], 'integer');
            settype($db_entry['Points'], 'integer');
            settype($db_entry['Modified'], 'integer');
            settype($db_entry['Created'], 'integer');
            settype($db_entry['Flags'], 'integer');

            $retVal[] = $db_entry;
        }
    } else {
        global $db;
        error_log(mysqli_error($db));
        error_log($query);
        error_log(__FUNCTION__ . " failed: gameID:$gameID flags:$flags");
    }

    return $retVal;
}

//    11:31 30/10/2014
function GetPatchData($gameID, $flags, $user)
{
    settype($gameID, 'integer');
    settype($flags, 'integer');

    $retVal = array();

    if ($gameID == 0) {
        error_log(__FUNCTION__ . " cannot lookup game with gameID $gameID for user $user");
    } else {
        //$retVal['MinVer'] = "0.049";
        $retVal = array_merge(GetGameData($gameID));

        $retVal['Achievements'] = GetAchievementsPatch($gameID, $flags);
        $retVal['Leaderboards'] = GetLBPatch($gameID);
    }
    return $retVal;
}

//    01:43 23/02/2013
function getPatch($gameID, $flags, $user, $andLeaderboards)
{
    settype($gameID, 'integer');
    settype($flags, 'integer');

    getGameTitleFromID($gameID, $gameTitle, $consoleID, $consoleName, $forumTopicID, $gameData);

    $minVer = "0.001";
    if ($consoleID == 1) //    Mega Drive
    {
        $minVer = "0.042";
    } //"0.028";
    elseif ($consoleID == 2) //    N64
    {
        $minVer = "0.008";
    } //??
    elseif ($consoleID == 3) //    SNES
    {
        $minVer = "0.008";
    }

    echo $minVer . "\n";
    echo $gameTitle . "\n";

    if ($gameID == 0) {
        error_log(__FUNCTION__ . " cannot lookup game with gameID $gameID for user $user");
        return;
    }

    $query = "SELECT ID, MemAddr, Title, Description, Progress, ProgressMax, ProgressFormat, Points, Author, DateModified, DateCreated, VotesPos, VotesNeg, BadgeName FROM Achievements ";
    $query .= "WHERE GameID='$gameID' AND Flags='$flags' ";
    $query .= "ORDER BY DisplayOrder";

    global $db;

    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            //    Unix time
            $dateStr = strtotime($db_entry["DateCreated"]);
            $dateCreatedSecs = date("U", $dateStr);
            $dateStr = strtotime($db_entry["DateModified"]);
            $dateModifiedSecs = date("U", $dateStr);

            $points = $db_entry["Points"];
            settype($points, 'integer');

            if (!isset($db_entry["Author"])) {
                $db_entry["Author"] = "Unknown";
            }

            if (!isset($db_entry["Progress"]) || $db_entry["Progress"] == '') {
                $db_entry["Progress"] = ' ';
            }
            if (!isset($db_entry["ProgressMax"]) || $db_entry["ProgressMax"] == '') {
                $db_entry["ProgressMax"] = ' ';
            }
            if (!isset($db_entry["ProgressFormat"]) || $db_entry["ProgressFormat"] == '') {
                $db_entry["ProgressFormat"] = ' ';
            }

            echo $db_entry["ID"] . ":" .
                $db_entry["MemAddr"] . ":" .
                $db_entry["Title"] . ":" .
                $db_entry["Description"] . ":" .
                $db_entry["Progress"] . ":" .
                $db_entry["ProgressMax"] . ":" .
                $db_entry["ProgressFormat"] . ":" .
                $db_entry["Author"] . ":" .
                $points . ":" .
                $dateCreatedSecs . ":" .
                $dateModifiedSecs . ":" .
                $db_entry["VotesPos"] . ":" .
                $db_entry["VotesNeg"] . ":" .
                $db_entry["BadgeName"];

            echo "\n";
        }

        //if( $flags == 3 ) //core
        //    postActivity( $user, 3, $title );

        if ($andLeaderboards) {
            $query = "SELECT ld.ID, ld.Mem, ld.Format, ld.Title, ld.Description
                      FROM LeaderboardDef AS ld
                      WHERE ld.GameID = $gameID
                      ORDER BY ld.DisplayOrder, ld.ID ";

            global $db;
            $dbResult = mysqli_query($db, $query);
            if ($dbResult !== false) {
                while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                    echo 'L' . $db_entry['ID'] .
                        '::' . $db_entry['Mem'] .
                        '::FOR:' . $db_entry['Format'] .
                        '::TTL:' . $db_entry['Title'] .
                        '::DES:' . $db_entry['Description'];

                    echo "\n";
                }
            } else {
                //    No leaderboards found: this is probably normal.
            }
        }

        return true;
    } else {
        error_log($query);
        error_log(__FUNCTION__ . " failed: user:$user flags:$flags gameID:$gameID");

        return false;
    }
}

function updateAchievementDisplayID($achID, $newID)
{
    $query = "UPDATE Achievements SET DisplayOrder = $newID WHERE ID = $achID";
    log_sql($query);
    $dbResult = s_mysql_query($query);

    return ($dbResult !== false);
}

function updateAchievementEmbedVideo($achID, $newURL)
{
    $newURL = strip_tags($newURL);
    $query = "UPDATE Achievements SET AssocVideo = '$newURL' WHERE ID = $achID";
    log_sql($query);
    global $db;
    $dbResult = mysqli_query($db, $query);

    return ($dbResult !== false);
}

function updateAchievementFlags($achID, $newFlags)
{
    $query = "UPDATE Achievements SET Flags = '$newFlags' WHERE ID = $achID";
    log_sql($query);
    global $db;
    $dbResult = mysqli_query($db, $query);

    return ($dbResult !== false);
}

function getCommonlyEarnedAchievements($consoleID, $offset, $count, &$dataOut)
{
    $subquery = "";
    if (isset($consoleID) && $consoleID > 0) {
        $subquery = "WHERE cons.ID = $consoleID ";
    }

    $query = "SELECT COALESCE(aw.cnt,0) AS NumTimesAwarded, ach.Title AS AchievementTitle, ach.ID, ach.Description, ach.Points, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, gd.ID AS GameID, cons.Name AS ConsoleName
            FROM Achievements AS ach
            LEFT OUTER JOIN (SELECT AchievementID, count(*) cnt FROM Awarded GROUP BY AchievementID) aw ON ach.ID = aw.AchievementID
            LEFT JOIN GameData gd ON gd.ID = ach.GameID
            LEFT JOIN Console AS cons ON cons.ID = gd.ConsoleID
            $subquery
            GROUP BY ach.ID
            ORDER BY NumTimesAwarded DESC
            LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = array();
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[] = $db_entry;
        }
        return true;
    } else {
        error_log($query);
        error_log(__FUNCTION__ . " failed: consoleID:$consoleID offset:$offset, count:$count");
        log_email("$offset... $count - " . $query);
        return true;
    }
}
