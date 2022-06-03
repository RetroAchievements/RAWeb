<?php

use App\Site\Enums\UserPreference;

function CreateNewMessage(string $author, string $destUser, string $messageTitle, string $messagePayloadIn): bool
{
    $messagePayload = nl2br($messagePayloadIn);

    sanitize_sql_inputs($author, $destUser, $messageTitle, $messagePayloadIn);

    $query = "INSERT INTO Messages (UserTo, UserFrom, Title, Payload) VALUES ( '$destUser', '$author', '$messageTitle', '$messagePayloadIn')";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        // Message sent!
        UpdateCachedUnreadTotals($destUser);

        // Inform target?
        if (getAccountDetails($destUser, $userDetails)) {
            $websitePrefs = $userDetails['websitePrefs'];
            $destEmail = $userDetails['EmailAddress'];

            if (isBitSet($websitePrefs, UserPreference::EmailOn_PrivateMessage)) {
                sendPrivateMessageEmail($destUser, $destEmail, $messageTitle, $messagePayload, $author);
            }
        }

        return true;
    }
    // Unconfirmed friend:
    log_sql_fail();

    return false;
}

function GetMessageCount(string $username, ?int &$totalMessageCount = null): int
{
    $unreadMessageCount = 0;
    $totalMessageCount = 0;

    $query = "SELECT Unread, COUNT(*) AS NumFound
              FROM Messages
              WHERE UserTo = :user
              GROUP BY Unread";

    $data = legacyDbFetchAll($query, ['user' => $username]);

    foreach ($data as $datum) {
        if ($datum['Unread'] == 1) {
            $unreadMessageCount = (int) $datum['NumFound'];
        }

        $totalMessageCount += (int) $datum['NumFound'];
    }

    return $unreadMessageCount;
}

function GetSentMessageCount(string $user): int
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

function GetMessage(string $user, int $id): ?array
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

function GetAllMessages(string $user, int $offset, int $count, bool $unreadOnly): array
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

function GetSentMessages(string $user, int $offset, int $count): array
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

function UpdateCachedUnreadTotals(string $user): void
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

function markMessageAsRead(string $user, int $messageID, bool $setAsUnread = false): bool
{
    sanitize_sql_inputs($user);

    $newReadStatus = (int) $setAsUnread;

    $query = "UPDATE Messages AS msg
            SET msg.Unread=$newReadStatus
            WHERE msg.ID = $messageID AND msg.UserTo = '$user'";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        UpdateCachedUnreadTotals($user);
    }

    return $dbResult !== false;
}

function DeleteMessage(string $user, int $messageID): bool
{
    sanitize_sql_inputs($user);

    $messageToDelete = GetMessage($user, $messageID);

    if (!$messageToDelete) {
        return false;
    }

    if (strtolower($messageToDelete['UserTo']) !== strtolower($user)) {
        return false;
    }

    $query = "DELETE FROM Messages WHERE Messages.ID = $messageID";
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        UpdateCachedUnreadTotals($user);
    }

    return $dbResult !== false;
}
