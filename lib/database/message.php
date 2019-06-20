<?php
require_once(__DIR__ . '/../bootstrap.php');
//////////////////////////////////////////////////////////////////////////////////////////
//    Messages 
//////////////////////////////////////////////////////////////////////////////////////////
function CreateNewMessage($author, $destUser, $messageTitle, $messagePayloadIn)
{
    //if( isFriendsWith( $author, $destUser ) )    //    nah
    {
        $messagePayload = nl2br($messagePayloadIn);

        global $db;
        $messageTitleSafe = mysqli_real_escape_string($db, $messageTitle);
        $messagePayloadSafe = mysqli_real_escape_string($db, $messagePayload);
        $authorSafe = mysqli_real_escape_string($db, $author);
        $destUserSafe = mysqli_real_escape_string($db, $destUser);

        $query = "INSERT INTO Messages VALUES ( NULL, '$destUserSafe', '$authorSafe', '$messageTitleSafe', '$messagePayloadSafe', NOW(), 1, 0 )";
        log_sql($query);

        $dbResult = mysqli_query($db, $query); //    Unprotected: all params *should* be safe now.
        if ($dbResult !== false) {
            //    Message sent!
            UpdateCachedUnreadTotals($destUser);

            //    Inform target?
            if (getAccountDetails($destUser, $userDetails)) {
                $websitePrefs = $userDetails['websitePrefs'];
                $destEmail = $userDetails['EmailAddress'];

                if (BitSet($websitePrefs, UserPref::EmailOn_PrivateMessage)) {
                    error_log("Sending email to $destUser, from $author, about $messageTitle, containing: $messagePayload");
                    sendPrivateMessageEmail($destUser, $destEmail, $messageTitle, $messagePayload, $author);
                }
            }

            error_log("Sent new PM from $author to $destUser, about $messageTitle, containing: $messagePayload");
            return true;
        } else {
            //    Unconfirmed friend:
            log_sql_fail();
            error_log($query);
            error_log(__FUNCTION__ . " failed: insert query failed: user:$author, friend:$destUser, $messageTitle, $messagePayload");
            return false;
        }
    }
    // else
    // {
    // //    Cannot send PM to a non-friend
    // error_log( __FUNCTION__ . " cannot PM a non-friend!" );
    // return FALSE;
    // }
}

function GetMessageCount($user, &$totalMessageCount)
{
    if (!isset($user)) {
        $totalMessageCount = 0;
        return 0;
    }

    //    Returns unread message count.

    $unreadMessageCount = 0;
    $totalMessageCount = 0;

    $query = "
        SELECT Unread, COUNT(*) AS NumFound FROM
        (
            SELECT *
            FROM Messages AS msg
            WHERE msg.UserTo = '$user'
        ) Inner1
        GROUP BY Unread";

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            //error_log( var_dump( $data ) );

            if ($data['Unread'] == 1) {
                $unreadMessageCount = $data['NumFound'];
            }

            $totalMessageCount += $data['NumFound'];
        }

        //error_log( "For $user, found $unreadMessageCount and $totalMessageCount" );

        settype($unreadMessageCount, 'integer');
        return $unreadMessageCount;
    } else {
        log_email("Unread message count fetch failed...");
        return 0;
    }
}

function GetTotalMessageCount($user)
{
    $query = "SELECT COUNT(*) AS NumUnreadMessages
              FROM Messages AS msg
              WHERE msg.UserTo = '$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['NumUnreadMessages'];
    } else {
        log_email("Unread message count fetch failed...");
        return 0;
    }
}

function GetMessage($user, $id)
{
    $query = "SELECT * FROM Messages AS msg
              WHERE msg.ID='$id' AND msg.UserTo='$user'";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $numFound = mysqli_num_rows($dbResult);
        if ($numFound > 0) {
            return mysqli_fetch_assoc($dbResult);
        } else {
            log_email("Failed to get message ID $id for $user");
            return false;
        }
    } else {
        log_email(__FUNCTION__ . " failed with user $user and ID $id");
        return false;
    }
}

function GetUnreadMessages($user, $offset, $count)
{
    $retval = array();

    $query = "SELECT * FROM Messages AS msg
              WHERE msg.UserTo='$user' AND msg.Unread = 1
              ORDER BY msg.TimeSent DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    } else {
        log_email(__FUNCTION__ . " failed: $user, $offset, $count");
    }

    return $retval;
}

function GetAllMessages($user, $offset, $count, $unreadOnly)
{
    $retval = array();

    $subQuery = '';
    if ($unreadOnly) {
        $subQuery = " AND msg.Unread=1";
    }

    $query = "SELECT * FROM Messages AS msg
              WHERE msg.UserTo='$user' $subQuery
              ORDER BY msg.TimeSent DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    } else {
        error_log($query);
        log_email(__FUNCTION__ . " failed with $user, $offset, $count");
    }

    return $retval;
}

function GetSentMessages($user, $offset, $count)
{
    $retval = array();

    $query = "SELECT * FROM Messages AS msg
              WHERE msg.UserFrom='$user'
              ORDER BY msg.TimeSent DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    } else {
        log_email(__FUNCTION__ . " failed with $user, $offset, $count");
    }

    return $retval;
}

function UpdateCachedUnreadTotals($user)
{
    $query = "
    UPDATE UserAccounts AS ua
    SET UnreadMessageCount = (
        SELECT COUNT(*) FROM
        (
            SELECT *
            FROM Messages AS msg
            WHERE msg.UserTo = '$user' AND msg.Unread = 1
        ) InnerTable
    ) WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);
}

function markMessageAsRead($user, $messageID, $setAsUnread = 0)
{
    $newReadStatus = $setAsUnread == 1 ? 1 : 0;

    $query = "UPDATE Messages AS msg
            SET msg.Unread=$newReadStatus
            WHERE msg.ID = $messageID";

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    if ($dbResult !== false) {
        UpdateCachedUnreadTotals($user);
    }

    return ($dbResult !== false);
}

function DeleteMessage($user, $messageID)
{
    $messageToDelete = GetMessage($user, $messageID);

    if ($messageToDelete == false) {
        log_email(__FUNCTION__ . " could not delete message ID $messageID for $user!");
        return false;
    } elseif ($messageToDelete['UserTo'] !== $user) {
        log_email(__FUNCTION__ . " $user is trying to delete a message $messageID that was sent to " . $messageToDelete['UserTo']);
        return false;
    } else {
        error_log("Deleting message: ");
        error_log("From: " . $messageToDelete['UserFrom'] . " To: " . $messageToDelete['UserTo'] . " at " . $messageToDelete['TimeSent']);
        error_log("Title: " . $messageToDelete['Title']);
        error_log("Payload: " . $messageToDelete['Payload']);

        $query = "DELETE FROM Messages WHERE Messages.ID = $messageID";
        $dbResult = s_mysql_query($query);

        return ($dbResult !== false);
    }
}
