<?php

use RA\Permissions;

require_once(__DIR__ . '/../bootstrap.php');

abstract class ModifyTopicField
{
    const ModifyTitle = 0;
    const DeleteTopic = 1;
    const RequiredPermissions = 2;
};

//////////////////////////////////////////////////////////////////////////////////////////
// Forums
//////////////////////////////////////////////////////////////////////////////////////////
function getForumList($categoryID = 0)
{
    // Specify NULL for all categories

    $query = "    SELECT f.ID, f.CategoryID, fc.Name AS CategoryName, fc.Description AS CategoryDescription, f.Title, f.Description, COUNT(DISTINCT ft.ID) AS NumTopics, COUNT( ft.ID ) AS NumPosts, ftc2.ID AS LastPostID, ftc2.Author AS LastPostAuthor, ftc2.DateCreated AS LastPostCreated, ft2.Title AS LastPostTopicName, ft2.ID AS LastPostTopicID, f.DisplayOrder
                FROM Forum AS f
                LEFT JOIN ForumCategory AS fc ON fc.ID = f.CategoryID
                LEFT JOIN ForumTopic AS ft ON ft.ForumID = f.ID
                LEFT JOIN ForumTopicComment AS ftc ON ftc.ForumTopicID = ft.ID
                LEFT JOIN ForumTopicComment AS ftc2 ON ftc2.ID = f.LatestCommentID
                LEFT JOIN ForumTopic AS ft2 ON ft2.ID = ftc2.ForumTopicID ";

    if ($categoryID > 0) {
        settype($categoryID, "integer");
        $query .= "WHERE fc.ID = '$categoryID' ";
    }
    $query .= "GROUP BY f.ID ";
    $query .= "ORDER BY fc.DisplayOrder, f.DisplayOrder, f.ID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = Array();

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numResults] = $db_entry;
            $numResults++;
        }
        return $dataOut;
    } else {
        error_log(__FUNCTION__ . " error");
        error_log($query);
        return null;
    }
}

function getForumDetails($forumID, &$forumDataOut)
{
    settype($forumID, "integer");
    $query = "    SELECT f.ID, f.Title AS ForumTitle, f.Description AS ForumDescription, fc.ID AS CategoryID, fc.Name AS CategoryName
                FROM Forum AS f
                LEFT JOIN ForumCategory AS fc ON fc.ID = f.CategoryID
                WHERE f.ID = $forumID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $forumDataOut = mysqli_fetch_assoc($dbResult);
        return true;
    } else {
        error_log(__FUNCTION__ . " error");
        error_log($query);
        $forumDataOut = null;
        return false;
    }
}

function getForumTopics($forumID, $offset, $count)
{
    settype($forumID, "integer");

    $query = "  SELECT f.Title AS ForumTitle, ft.ID AS ForumTopicID, ft.Title AS TopicTitle, LEFT( ftc2.Payload, 50 ) AS TopicPreview, ft.Author, ft.AuthorID, ft.DateCreated AS ForumTopicPostedDate, ftc.ID AS LatestCommentID, ftc.Author AS LatestCommentAuthor, ftc.AuthorID AS LatestCommentAuthorID, ftc.DateCreated AS LatestCommentPostedDate, (COUNT(ftc2.ID)-1) AS NumTopicReplies
                FROM ForumTopic AS ft
                LEFT JOIN ForumTopicComment AS ftc ON ftc.ID = ft.LatestCommentID
                LEFT JOIN Forum AS f ON f.ID = ft.ForumID
                LEFT JOIN ForumTopicComment AS ftc2 ON ftc2.ForumTopicID = ft.ID
                WHERE ft.ForumID = $forumID AND ftc.Authorised = 1
                GROUP BY ft.ID
                ORDER BY LatestCommentPostedDate DESC ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = Array();

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numResults] = $db_entry;
            $numResults++;
        }
        return $dataOut;
    } else {
        error_log(__FUNCTION__ . " error");
        error_log($query);
        return null;
    }
}

function getUnauthorisedForumLinks()
{
    $query = "  SELECT f.Title AS ForumTitle, ft.ID AS ForumTopicID, ft.Title AS TopicTitle, LEFT( ftc2.Payload, 50 ) AS TopicPreview, ft.Author, ft.AuthorID, ft.DateCreated AS ForumTopicPostedDate, ftc.ID AS LatestCommentID, ftc.Author AS LatestCommentAuthor, ftc.AuthorID AS LatestCommentAuthorID, ftc.DateCreated AS LatestCommentPostedDate, (COUNT(ftc2.ID)-1) AS NumTopicReplies
                FROM ForumTopic AS ft
                LEFT JOIN ForumTopicComment AS ftc ON ftc.ID = ft.LatestCommentID
                LEFT JOIN Forum AS f ON f.ID = ft.ForumID
                LEFT JOIN ForumTopicComment AS ftc2 ON ftc2.ForumTopicID = ft.ID
                WHERE ftc.Authorised = 0
                GROUP BY ft.ID
                ORDER BY LatestCommentPostedDate DESC ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = Array();

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numResults] = $db_entry;
            $numResults++;
        }
        return $dataOut;
    } else {
        error_log(__FUNCTION__ . " error");
        error_log($query);
        return null;
    }
}

function getTopicDetails($topicID, &$topicDataOut)
{
    settype($topicID, "integer");
    $query = "  SELECT ft.ID, ft.Author, ft.AuthorID, fc.ID AS CategoryID, fc.Name AS Category, fc.ID as CategoryID, f.ID AS ForumID, f.Title AS Forum, ft.Title AS TopicTitle, ft.RequiredPermissions
                FROM ForumTopic AS ft
                LEFT JOIN Forum AS f ON f.ID = ft.ForumID
                LEFT JOIN ForumCategory AS fc ON fc.ID = f.CategoryID
                WHERE ft.ID = $topicID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        //error_log( __FUNCTION__ . " $topicID, " . mysqli_num_rows( $dbResult ) );
        $topicDataOut = mysqli_fetch_assoc($dbResult);
        return $topicDataOut['ID'] == $topicID;
    } else {
        $topicDataOut = null;
        return false;
    }
}

function getTopicComments($topicID, $offset, $count, &$maxCountOut)
{
    settype($topicID, "integer");

    $query = "    SELECT COUNT(*) FROM ForumTopicComment AS ftc
                WHERE ftc.ForumTopicID = $topicID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        $maxCountOut = $data['COUNT(*)'];
    }


    $query = "SELECT ftc.ID, ftc.ForumTopicID, ftc.Payload, ftc.Author, ftc.AuthorID, ftc.DateCreated, ftc.DateModified, ftc.Authorised, ua.RAPoints
                FROM ForumTopicComment AS ftc
                LEFT JOIN UserAccounts AS ua ON ua.ID = ftc.AuthorID
                WHERE ftc.ForumTopicID = $topicID
                ORDER BY DateCreated ASC
                LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = Array();

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numResults] = $db_entry;
            $numResults++;
        }
        return $dataOut;
    } else {
        error_log(__FUNCTION__ . " error");
        error_log($query);
        return null;
    }
}

function getSingleTopicComment($forumPostID, &$dataOut)
{
    settype($forumPostID, 'integer');
    $query = "    SELECT ID, ForumTopicID, Payload, Author, AuthorID, DateCreated, DateModified 
                FROM ForumTopicComment
                WHERE ID=$forumPostID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = mysqli_fetch_assoc($dbResult);
        return true;
    }

    return false;
}

function submitNewTopic($user, $forumID, $topicTitle, $topicPayload, &$newTopicIDOut)
{
    $userID = getUserIDFromUser($user);

    if (strlen($topicTitle) < 2) {
        $topicTitle = "$user's topic";
    }

    // Replace inverted commas, Remove HTML, TBD: allow phpbb
    $topicTitle = str_replace("'", "''", $topicTitle);
    $topicTitle = strip_tags($topicTitle);

    // Create new topic, then submit new comment

    //$authFlags = getUserForumPostAuth( $user );

    $query = "INSERT INTO ForumTopic VALUES ( NULL, $forumID, '$topicTitle', '$user', $userID, NOW(), 0, 0 )";
    log_sql($query);

    global $db;
    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        global $db;
        $newTopicIDOut = mysqli_insert_id($db);

        if (submitTopicComment($user, $newTopicIDOut, $topicPayload, $newCommentID)) {
            //error_log( __FUNCTION__ . " posted OK!" );
            //error_log( "$user posted new topic $topicTitle giving topic ID $newTopicIDOut with added comment ID $newCommentID" );
            return true;
        } else {
            log_sql_fail();
            error_log(__FUNCTION__ . " struggled to post comment after adding new topic...");
            error_log($query);
            error_log("$user posted $topicPayload for topic ID $newTopicIDOut");
            return false;
        }
    } else {
        log_sql_fail();
        error_log($query);
        error_log(__FUNCTION__ . " failed!");
        return false;
    }
}

function setLatestCommentInForumTopic($topicID, $commentID)
{
    // Update ForumTopic table
    $query = "UPDATE ForumTopic SET LatestCommentID=$commentID WHERE ID=$topicID";
    log_sql($query);
    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        error_log(__FUNCTION__ . " failed!");
        error_log($query);
    }

    // Propogate to Forum table
    $query = "  UPDATE Forum AS f
                INNER JOIN ForumTopic AS ft ON ft.ForumID = f.ID
                SET f.LatestCommentID = ft.LatestCommentID
                WHERE ft.ID = $topicID ";

    log_sql($query);
    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        error_log(__FUNCTION__ . " failed!");
        error_log($query);
    }

    return true;
}

function editTopicComment($commentID, $newPayload)
{
    settype($commentID, 'integer');
    $newPayload = str_replace("'", "''", $newPayload);
    $newPayload = str_replace("<", "&lt;", $newPayload);
    $newPayload = str_replace(">", "&gt;", $newPayload);

    $query = "UPDATE ForumTopicComment SET Payload = '$newPayload' WHERE ID=$commentID";
    log_sql($query);

    global $db;
    $dbResult = mysqli_query($db, $query);    //    TBD: unprotected to allow all characters..
    if ($dbResult !== false) {
        //error_log( __FUNCTION__ . " posted OK!" );
        error_log(__FUNCTION__ . " ID $commentID now becomes $newPayload");
        return true;
    } else {
        log_sql_fail();
        error_log("$query");
        error_log(__FUNCTION__ . " failed!");
        return false;
    }
}

function submitTopicComment($user, $topicID, $commentPayload, &$newCommentIDOut)
{
    $userID = getUserIDFromUser($user);

    // Replace inverted commas, Remove HTML
    $commentPayload = str_replace("'", "''", $commentPayload);
    $commentPayload = str_replace("<", "&lt;", $commentPayload);
    $commentPayload = str_replace(">", "&gt;", $commentPayload);
    //$commentPayload = strip_tags( $commentPayload );

    $authFlags = getUserForumPostAuth($user);

    $query = "INSERT INTO ForumTopicComment VALUES ( NULL, $topicID, '$commentPayload', '$user', $userID, NOW(), NULL, $authFlags ) ";
    log_sql($query);

    global $db;
    $dbResult = mysqli_query($db, $query);    //    TBD: unprotected to allow all characters..
    if ($dbResult !== false) {
        $newCommentIDOut = mysqli_insert_id($db);
        setLatestCommentInForumTopic($topicID, $newCommentIDOut);

        notifyUsersAboutForumActivity($topicID, $user, $newCommentIDOut);

        //error_log( __FUNCTION__ . " posted OK!" );
        error_log(__FUNCTION__ . " $user posted $commentPayload for topic ID $topicID");
        return true;
    } else {
        log_sql_fail();
        error_log("$query");
        error_log(__FUNCTION__ . " failed!");
        return false;
    }
}

function notifyUsersAboutForumActivity($topicID, $author, $commentID)
{
    //    $author has made a post in the topic $topicID
    //    Find all people involved in this forum thread, and if they are not the author and prefer to 
    //     hear about comments, let them know!

    $query = "SELECT ua.User, ua.EmailAddress FROM ForumTopicComment AS ftc
                INNER JOIN ForumTopic AS ft ON ftc.ForumTopicID = ft.ID
                INNER JOIN UserAccounts AS ua ON ua.ID = ftc.AuthorID AND (ua.websitePrefs & (1<<3) != 0) 
                WHERE ft.ID = $topicID AND ua.User != '$author'
                GROUP BY ua.ID ";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $nextUser = $db_entry['User'];
            $nextEmail = $db_entry['EmailAddress'];

            $urlTarget = "viewtopic.php?t=$topicID&c=$commentID";

            sendActivityEmail($nextUser, $nextEmail, $topicID, $author, 6, null, $urlTarget);
        }
    } else {
        error_log($query);
        error_log(__FUNCTION__ . "wtf!! Can't notify anybody");
    }
}

function getTopicCommentCommentOffset($forumTopicID, $commentID, $count, &$offset)
{
    // Focus on most recent comment
    if ($commentID == -1) {
        $commentID = 99999999;
    }

    $query = "SELECT COUNT(ID) AS CommentOffset FROM
                ( SELECT ID FROM ForumTopicComment
                  WHERE ForumTopicID = $forumTopicID
                  ORDER BY ID ) AS InnerTable
                WHERE ID < $commentID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);

        $commentOffset = $data['CommentOffset'];
        $pageOffset = 0;
        while ($pageOffset <= $commentOffset) {
            $pageOffset += $count;
        }

        $offset = $pageOffset - $count;
        return true;
    } else {
        $offset = 0;
        return false;
    }
}

function generateGameForumTopic($user, $gameID, &$forumTopicID)
{
    settype($gameID, 'integer');
    if ($gameID == 0) {
        return false;
    }

    getGameMetaData($gameID, $user, $achievementData, $gameData);

    if (isset($gameData['ForumTopicID'])
        && $gameData['ForumTopicID'] != 0
        && getTopicDetails($gameData['ForumTopicID'], $dumbData)) {
        // Bad times?!
        error_log(__FUNCTION__ . ", $user trying to create a forum topic for " . $gameData['Title'] . " when one already exists!");
        return false;
    }

    $forumID = 0;

    $consoleID = $gameData['ConsoleID'];

    switch ($consoleID) {
        case 1: // Mega Drive
            $forumID = 10;
            break;
        case 3: // SNES
            $forumID = 13;
            break;
        case 4: // GB
        case 5: // GBC
        case 6: // GBA
            $forumID = 16;
            break;
        case 7: // NES
            $forumID = 18;
            break;
        case 8: // PC Engine
            $forumID = 22;
            break;
        default: // Default to Mega Drive
            $forumID = 10;
    }

    $gameTitle = $gameData['Title'];
    $consoleName = $gameData['ConsoleName'];

    $topicTitle = $gameTitle;

    $urlSafeGameTitle = str_replace(" ", "+", "$gameTitle $consoleName");
    $urlSafeGameTitle = str_replace("'", "''", $urlSafeGameTitle);

    $gameFAQsURL = "https://www.google.com/search?q=site:www.gamefaqs.com+$urlSafeGameTitle";
    $longplaysURL = "https://www.google.com/search?q=site:www.youtube.com+longplay+$urlSafeGameTitle";
    $wikipediaURL = "https://www.google.com/search?q=site:en.wikipedia.org+$urlSafeGameTitle";

    $topicPayload = "Official Topic Post for discussion about [game=$gameID]\n" .
        "Created " . date("j M, Y H:i") . " by [user=$user]\n\n" .
        "[b]Resources:[/b]\n" .
        "[url=$gameFAQsURL]GameFAQs[/url]\n" .
        "[url=$longplaysURL]Longplay[/url]\n" .
        "[url=$wikipediaURL]Wikipedia[/url]\n";

    if (submitNewTopic($user, $forumID, $topicTitle, $topicPayload, $forumTopicID)) {
        $query = "UPDATE GameData SET ForumTopicID = $forumTopicID 
                  WHERE ID=$gameID ";

        $dbResult = s_mysql_query($query);
        return ($dbResult !== false);
    } else {
        log_email(__FUNCTION__ . " failed :( $user, $gameID, $gameTitle )");
        return false;
    }
}

function getRecentForumPosts($offset, $count, $numMessageChars, &$dataOut)
{
    //    02:08 21/02/2014 - cater for 20 spam messages
    $countPlusSpam = $count + 20;
    $query = "
        SELECT LatestComments.DateCreated AS PostedAt, LEFT( LatestComments.Payload, $numMessageChars ) AS ShortMsg, LatestComments.Author, ua.RAPoints, ua.Motto, ft.ID AS ForumTopicID, ft.Title AS ForumTopicTitle, LatestComments.ID AS CommentID
        FROM 
        (
            SELECT * 
            FROM ForumTopicComment AS ftc
            WHERE ftc.Authorised = 1
            ORDER BY ftc.DateCreated DESC
            LIMIT $offset, $countPlusSpam
        ) AS LatestComments

        INNER JOIN ForumTopic AS ft ON ft.ID = LatestComments.ForumTopicID
        LEFT JOIN Forum AS f ON f.ID = ft.ForumID
        LEFT JOIN UserAccounts AS ua ON ua.User = LatestComments.Author
        ORDER BY LatestComments.DateCreated DESC
        LIMIT 0, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = Array();

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numResults] = $db_entry;
            $numResults++;
        }
        return $numResults;
    } else {
        error_log(__FUNCTION__ . " error");
        error_log($query);
        return null;
    }
}

function requestModifyTopic($user, $permissions, $topicID, $field, $value)
{
    settype($field, 'integer');
    settype($topicID, 'integer');

    if (!getTopicDetails($topicID, $topicData)) {
        error_log(__FUNCTION__ . " cannot process, $topicID doesn't exist?!");
        return false;
    }

    switch ($field) {
        case ModifyTopicField::ModifyTitle:
            if (($permissions >= Permissions::Admin) || ($user == $topicData['Author'])) {
                $query = "  UPDATE ForumTopic AS ft
                            SET Title='$value'
                            WHERE ID=$topicID";

                $dbResult = s_mysql_query($query);
                if ($dbResult !== false) {
                    error_log("$user changed forum topic $topicID title from '" . $topicData['TopicTitle'] . "' to '$value'");
                    return true;
                } else {
                    error_log(__FUNCTION__ . " change title error");
                    error_log($query);
                    return false;
                }
            } else {
                error_log(__FUNCTION__ . " change title... not enough permissions?!");
                error_log($query);
                return false;
            }
            break;
        case ModifyTopicField::DeleteTopic:
            if ($permissions >= Permissions::Admin) {
                $query = "  DELETE FROM ForumTopic
                            WHERE ID=$topicID";

                $dbResult = s_mysql_query($query);
                if ($dbResult !== false) {
                    error_log("$user deleted forum topic $topicID ('" . $topicData['TopicTitle'] . "')");
                    return true;
                } else {
                    error_log(__FUNCTION__ . " delete error");
                    error_log($query);
                    return false;
                }
            } else {
                error_log(__FUNCTION__ . " delete title... not enough permissions?!");
                error_log($query);
                return false;
            }
            break;
        case ModifyTopicField::RequiredPermissions:
            if ($permissions >= Permissions::Admin) {
                $query = "  UPDATE ForumTopic AS ft
                            SET RequiredPermissions='$value'
                            WHERE ID=$topicID";

                $dbResult = s_mysql_query($query);
                if ($dbResult !== false) {
                    error_log("$user changed permissions for topic ID $topicID ('" . $topicData['TopicTitle'] . "') to $value");
                    return true;
                } else {
                    error_log(__FUNCTION__ . " modify error");
                    error_log($query);
                    return false;
                }
            } else {
                error_log(__FUNCTION__ . " modify topic... not enough permissions?!");
                error_log($query);
                return false;
            }
            break;
    }
}

function RemoveUnauthorisedForumPosts($user)
{
    //    Removes all 'unauthorised' forum posts by a particular user
    $query = "DELETE FROM ForumTopicComment
              WHERE Author = '$user' AND Authorised = 0";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        log_email(__FUNCTION__ . " user's forum post comments have all been permanently removed!");
        error_log("$user's posts have been removed!");
        return true;
    } else {
        error_log(__FUNCTION__ . " error");
        error_log($query);
        return false;
    }
}

function AuthoriseAllForumPosts($user)
{
    //    Sets all unauthorised forum posts by a particular user to authorised
    //    Removes all 'unauthorised' forum posts by a particular user
    $query = "UPDATE ForumTopicComment AS ftc
              SET ftc.Authorised = 1
              WHERE Author = '$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        //log_email( __FUNCTION__ . " user's forum post comments have all been authorised!" );
        error_log("$user's posts have all been authorised!");
        return true;
    } else {
        error_log(__FUNCTION__ . " error");
        error_log($query);
        return false;
    }
}
