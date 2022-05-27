<?php

use RA\UserPref;

function CreateNewMessage($author, $destUser, $messageTitle, $messagePayloadIn): bool
{
    $messagePayload = nl2br($messagePayloadIn);

    sanitize_sql_inputs($author, $destUser, $messageTitle, $messagePayloadIn);

    $query = "INSERT INTO Messages VALUES ( NULL, '$destUser', '$author', '$messageTitle', '$messagePayloadIn', NOW(), 1, 0 )";

    global $db;
    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        // Message sent!
        UpdateCachedUnreadTotals($destUser);

        // Inform target?
        if (getAccountDetails($destUser, $userDetails)) {
            $websitePrefs = $userDetails['websitePrefs'];
            $destEmail = $userDetails['EmailAddress'];

            if (BitSet($websitePrefs, UserPref::EmailOn_PrivateMessage)) {
                sendPrivateMessageEmail($destUser, $destEmail, $messageTitle, $messagePayload, $author);
            }
        }

        return true;
    } else {
        // Unconfirmed friend:
        log_sql_fail();
        return false;
    }
}

function GetMessageCount($user, &$totalMessageCount): int
{
    sanitize_sql_inputs($user);

    if (!isset($user)) {
        $totalMessageCount = 0;
        return 0;
    }

    // Returns unread message count.

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

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            if ($data['Unread'] == 1) {
                $unreadMessageCount = (int) $data['NumFound'];
            }

            $totalMessageCount += (int) $data['NumFound'];
        }

        return $unreadMessageCount;
    } else {
        return 0;
    }
}

function GetSentMessageCount($user): int
{
    sanitize_sql_inputs($user);

    if (!isset($user)) {
        return 0;
    }

    $messageCount = 0;

    $query = "
        SELECT COUNT(*) AS NumFound
        FROM Messages AS msg
        WHERE msg.UserFrom = '$user'
    ";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $messageCount = (int) $data['NumFound'];
        }
    }

    return $messageCount;
}

function GetMessage($user, $id): ?array
{
    sanitize_sql_inputs($user, $id);

    $query = "SELECT * FROM Messages AS msg
              WHERE msg.ID='$id' AND msg.UserTo='$user'";

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        return null;
    }

    $numFound = mysqli_num_rows($dbResult);
    if ($numFound > 0) {
        return mysqli_fetch_assoc($dbResult);
    }

    return null;
}

function GetAllMessages($user, $offset, $count, $unreadOnly): array
{
    sanitize_sql_inputs($user, $offset, $count);

    $retval = [];

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
        log_sql_fail();
    }

    return $retval;
}

function GetSentMessages($user, $offset, $count): array
{
    sanitize_sql_inputs($user, $offset, $count);

    $retval = [];

    $query = "SELECT * FROM Messages AS msg
              WHERE msg.UserFrom='$user'
              ORDER BY msg.TimeSent DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

function UpdateCachedUnreadTotals($user): void
{
    sanitize_sql_inputs($user);

    $query = "
    UPDATE UserAccounts AS ua
    SET UnreadMessageCount = (
        SELECT COUNT(*) FROM
        (
            SELECT *
            FROM Messages AS msg
            WHERE msg.UserTo = '$user' AND msg.Unread = 1
        ) InnerTable
    ), Updated=NOW() WHERE ua.User = '$user'";

    s_mysql_query($query);
}

function markMessageAsRead($user, $messageID, $setAsUnread = 0): bool
{
    sanitize_sql_inputs($user, $messageID);

    $newReadStatus = $setAsUnread == 1 ? 1 : 0;

    $query = "UPDATE Messages AS msg
            SET msg.Unread=$newReadStatus
            WHERE msg.ID = $messageID";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        UpdateCachedUnreadTotals($user);
    }

    return $dbResult !== false;
}

function DeleteMessage($user, $messageID): bool
{
    sanitize_sql_inputs($user, $messageID);

    $messageToDelete = GetMessage($user, $messageID);

    if (!$messageToDelete) {
        return false;
    } elseif ($messageToDelete['UserTo'] !== $user) {
        return false;
    } else {
        $query = "DELETE FROM Messages WHERE Messages.ID = $messageID";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            s_mysql_query("INSERT INTO DeletedModels SET ModelType='Messages', ModelID=$messageID");
        }
        return $dbResult !== false;
    }
}
