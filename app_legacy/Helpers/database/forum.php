<?php

use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Community\Enums\SubscriptionSubjectType;
use LegacyApp\Site\Enums\Permissions;

function getForumList($categoryID = 0): ?array
{
    sanitize_sql_inputs($categoryID);
    settype($categoryID, 'integer');

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
        $dataOut = [];

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numResults] = $db_entry;
            $numResults++;
        }

        return $dataOut;
    } else {
        log_sql_fail();

        return null;
    }
}

function getForumDetails($forumID, &$forumDataOut): bool
{
    sanitize_sql_inputs($forumID);
    settype($forumID, "integer");
    $query = "    SELECT f.ID, f.Title AS ForumTitle, f.Description AS ForumDescription, fc.ID AS CategoryID, fc.Name AS CategoryName
                FROM Forum AS f
                LEFT JOIN ForumCategory AS fc ON fc.ID = f.CategoryID
                WHERE f.ID = $forumID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $forumDataOut = mysqli_fetch_assoc($dbResult);

        return $forumDataOut != null;
    } else {
        log_sql_fail();
        $forumDataOut = null;

        return false;
    }
}

function getForumTopics($forumID, $offset, $count, $permissions, &$maxCountOut): ?array
{
    sanitize_sql_inputs($forumID, $offset, $count);
    settype($forumID, "integer");

    $query = "  SELECT COUNT(*) FROM ForumTopic AS ft
                LEFT JOIN ForumTopicComment AS ftc ON ftc.ID = ft.LatestCommentID
                WHERE ft.ForumID = $forumID AND ftc.Authorised = 1
                AND ft.RequiredPermissions <= $permissions";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        $maxCountOut = (int) $data['COUNT(*)'];
    }

    $query = "  SELECT f.Title AS ForumTitle, ft.ID AS ForumTopicID, ft.Title AS TopicTitle, LEFT( ftc2.Payload, 54 ) AS TopicPreview, ft.Author, ft.AuthorID, ft.DateCreated AS ForumTopicPostedDate, ftc.ID AS LatestCommentID, ftc.Author AS LatestCommentAuthor, ftc.AuthorID AS LatestCommentAuthorID, ftc.DateCreated AS LatestCommentPostedDate, (COUNT(ftc2.ID)-1) AS NumTopicReplies
                FROM ForumTopic AS ft
                LEFT JOIN ForumTopicComment AS ftc ON ftc.ID = ft.LatestCommentID
                LEFT JOIN Forum AS f ON f.ID = ft.ForumID
                LEFT JOIN ForumTopicComment AS ftc2 ON ftc2.ForumTopicID = ft.ID AND ftc2.Authorised = 1
                WHERE ft.ForumID = $forumID
                AND ft.RequiredPermissions <= $permissions
                GROUP BY ft.ID, LatestCommentPostedDate
                HAVING NumTopicReplies >= 0
                ORDER BY LatestCommentPostedDate DESC
                LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = [];

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numResults] = $db_entry;
            $numResults++;
        }

        return $dataOut;
    } else {
        log_sql_fail();

        return null;
    }
}

function getUnauthorisedForumLinks(): ?array
{
    $query = "  SELECT f.Title AS ForumTitle, ft.ID AS ForumTopicID, ft.Title AS TopicTitle, LEFT( ftc2.Payload, 60 ) AS TopicPreview, ft.Author, ft.AuthorID, ft.DateCreated AS ForumTopicPostedDate, ftc.ID AS LatestCommentID, ftc.Author AS LatestCommentAuthor, ftc.AuthorID AS LatestCommentAuthorID, ftc.DateCreated AS LatestCommentPostedDate, (COUNT(ftc2.ID)-1) AS NumTopicReplies
                FROM ForumTopic AS ft
                LEFT JOIN ForumTopicComment AS ftc ON ftc.ForumTopicID = ft.ID
                LEFT JOIN Forum AS f ON f.ID = ft.ForumID
                LEFT JOIN ForumTopicComment AS ftc2 ON ftc2.ForumTopicID = ft.ID
                WHERE ftc.Authorised = 0
                GROUP BY ft.ID, LatestCommentPostedDate
                ORDER BY LatestCommentPostedDate DESC ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = [];

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numResults] = $db_entry;
            $numResults++;
        }

        return $dataOut;
    } else {
        log_sql_fail();

        return null;
    }
}

function getTopicDetails($topicID, &$topicDataOut): bool
{
    sanitize_sql_inputs($topicID);
    settype($topicID, "integer");
    $query = "  SELECT ft.ID, ft.Author, ft.AuthorID, fc.ID AS CategoryID, fc.Name AS Category, fc.ID as CategoryID, f.ID AS ForumID, f.Title AS Forum, ft.Title AS TopicTitle, ft.RequiredPermissions
                FROM ForumTopic AS ft
                LEFT JOIN Forum AS f ON f.ID = ft.ForumID
                LEFT JOIN ForumCategory AS fc ON fc.ID = f.CategoryID
                WHERE ft.ID = $topicID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $topicDataOut = mysqli_fetch_assoc($dbResult);

        return $topicID == ($topicDataOut['ID'] ?? null);
    } else {
        $topicDataOut = null;

        return false;
    }
}

function getTopicComments($topicID, $offset, $count, &$maxCountOut): ?array
{
    sanitize_sql_inputs($topicID);
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
        $dataOut = [];

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numResults] = $db_entry;
            $numResults++;
        }

        return $dataOut;
    } else {
        log_sql_fail();

        return null;
    }
}

function getSingleTopicComment($forumPostID, &$dataOut): bool
{
    sanitize_sql_inputs($forumPostID);
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

function submitNewTopic($user, $forumID, $topicTitle, $topicPayload, &$newTopicIDOut): bool
{
    sanitize_sql_inputs($user, $forumID);
    $userID = getUserIDFromUser($user);

    if (mb_strlen($topicTitle) < 2) {
        $topicTitle = "$user's topic";
    }

    // Replace inverted commas, Remove HTML, TBD: allow phpbb
    $topicTitle = str_replace("'", "''", $topicTitle);
    $topicTitle = strip_tags($topicTitle);

    // Create new topic, then submit new comment

    // $authFlags = getUserForumPostAuth( $user );

    $query = "INSERT INTO ForumTopic (ForumID, Title, Author, AuthorID, DateCreated, LatestCommentID, RequiredPermissions) VALUES ( $forumID, '$topicTitle', '$user', $userID, NOW(), 0, 0 )";

    $db = getMysqliConnection();
    if (!mysqli_query($db, $query)) {
        log_sql_fail();

        return false;
    }

    $newTopicIDOut = mysqli_insert_id($db);

    return submitTopicComment($user, $newTopicIDOut, $topicTitle, $topicPayload, $newCommentID);
}

function setLatestCommentInForumTopic($topicID, $commentID): bool
{
    sanitize_sql_inputs($topicID, $commentID);

    // Update ForumTopic table
    $query = "UPDATE ForumTopic SET LatestCommentID=$commentID WHERE ID=$topicID";
    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();
    }

    // Propogate to Forum table
    $query = "  UPDATE Forum AS f
                INNER JOIN ForumTopic AS ft ON ft.ForumID = f.ID
                SET f.LatestCommentID = ft.LatestCommentID
                WHERE ft.ID = $topicID ";

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();
    }

    return true;
}

function editTopicComment($commentID, $newPayload): bool
{
    sanitize_sql_inputs($commentID);
    settype($commentID, 'integer');
    $newPayload = str_replace("'", "''", $newPayload);
    $newPayload = str_replace("<", "&lt;", $newPayload);
    $newPayload = str_replace(">", "&gt;", $newPayload);

    $query = "UPDATE ForumTopicComment SET Payload = '$newPayload' WHERE ID=$commentID";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);    // TBD: unprotected to allow all characters..
    if ($dbResult !== false) {
        return true;
    } else {
        log_sql_fail();

        return false;
    }
}

function submitTopicComment($user, $topicID, $topicTitle, $commentPayload, &$newCommentIDOut): bool
{
    sanitize_sql_inputs($user, $topicID);
    $userID = getUserIDFromUser($user);

    // Replace inverted commas, Remove HTML
    $commentPayload = str_replace("'", "''", $commentPayload);
    $commentPayload = str_replace("<", "&lt;", $commentPayload);
    $commentPayload = str_replace(">", "&gt;", $commentPayload);
    // $commentPayload = strip_tags( $commentPayload );

    $authFlags = getUserForumPostAuth($user) ? 1 : 0;

    $query = "INSERT INTO ForumTopicComment VALUES ( NULL, $topicID, '$commentPayload', '$user', $userID, NOW(), NULL, $authFlags ) ";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);    // TBD: unprotected to allow all characters..
    if ($dbResult !== false) {
        $newCommentIDOut = mysqli_insert_id($db);
        setLatestCommentInForumTopic($topicID, $newCommentIDOut);

        if ($topicTitle == null) {
            $topicData = [];
            if (getTopicDetails($topicID, $topicData)) {
                $topicTitle = $topicData['TopicTitle'];
            } else {
                $topicTitle = '';
            }
        }

        if ($authFlags) {
            notifyUsersAboutForumActivity($topicID, $topicTitle, $user, $newCommentIDOut);
        }

        return true;
    } else {
        log_sql_fail();

        return false;
    }
}

function notifyUsersAboutForumActivity($topicID, $topicTitle, $author, $commentID): void
{
    sanitize_sql_inputs($topicID, $author, $commentID);

    // $author has made a post in the topic $topicID
    // Find all people involved in this forum topic, and if they are not the author and prefer to
    // hear about comments, let them know! Also notify users that have explicitly subscribed to
    // the topic.

    $subscribers = getSubscribersOf(
        SubscriptionSubjectType::ForumTopic,
        $topicID,
        1 << 3,
        "
            SELECT DISTINCT ua.*
            FROM
                ForumTopicComment as ftc
                INNER JOIN UserAccounts AS ua ON ua.ID = ftc.AuthorID
            WHERE
                ftc.ForumTopicID = $topicID
        "
    );

    $urlTarget = "viewtopic.php?t=$topicID&c=$commentID#$commentID";
    foreach ($subscribers as $sub) {
        sendActivityEmail($sub['User'], $sub['EmailAddress'], $topicID, $author, ArticleType::Forum, $topicTitle, null, $urlTarget);
    }
}

function getTopicCommentCommentOffset($forumTopicID, $commentID, $count, &$offset): bool
{
    // Focus on most recent comment
    if ($commentID == -1) {
        $commentID = 99_999_999;
    }

    sanitize_sql_inputs($forumTopicID, $commentID);

    $query = "SELECT COUNT(ID) AS CommentOffset
              FROM ForumTopicComment
              WHERE DateCreated < (SELECT DateCreated FROM ForumTopicComment WHERE ID = $commentID)
              AND ForumTopicID = $forumTopicID";

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

function generateGameForumTopic($user, $gameID, &$forumTopicID): bool
{
    sanitize_sql_inputs($user, $gameID);
    settype($gameID, 'integer');
    if ($gameID == 0) {
        return false;
    }

    $achievementData = null;
    $gameData = null;
    getGameMetaData($gameID, $user, $achievementData, $gameData);

    if (isset($gameData['ForumTopicID'])
        && $gameData['ForumTopicID'] != 0
        && getTopicDetails($gameData['ForumTopicID'], $dumbData)) {
        // Bad times?!
        return false;
    }

    $consoleID = $gameData['ConsoleID'];

    $forumID = match ($consoleID) {
        // Mega Drive
        1 => 10,
        // SNES
        3 => 13,
        // GB, GBC, GBA
        4, 5, 6 => 16,
        // NES
        7 => 18,
        // PC Engine
        8 => 22,
        // Mega Drive
        default => 10,
    };

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

        return $dbResult !== false;
    } else {
        return false;
    }
}

function getRecentForumPosts($offset, $count, $numMessageChars, $permissions, &$dataOut, $fromUser = null): ?int
{
    sanitize_sql_inputs($offset, $count, $numMessageChars, $fromUser);

    if (!empty($fromUser)) {
        $userClause = "ftc.Author='$fromUser'";
        if ($permissions < Permissions::Admin) {
            $userClause .= " AND ftc.Authorised=1";
        }
    } else {
        $userClause = "ftc.Authorised=1";
    }

    // 02:08 21/02/2014 - cater for 20 spam messages
    $countPlusSpam = $count + 20;
    $query = "
        SELECT LatestComments.DateCreated AS PostedAt,
            LEFT( LatestComments.Payload, $numMessageChars ) AS ShortMsg,
            LENGTH(LatestComments.Payload) > $numMessageChars AS IsTruncated,
            LatestComments.Author,
            ua.RAPoints,
            ua.Motto,
            ft.ID AS ForumTopicID,
            ft.Title AS ForumTopicTitle,
            LatestComments.ID AS CommentID
        FROM
        (
            SELECT *
            FROM ForumTopicComment AS ftc
            WHERE $userClause
            ORDER BY ftc.DateCreated DESC
            LIMIT $offset, $countPlusSpam
        ) AS LatestComments
        INNER JOIN ForumTopic AS ft ON ft.ID = LatestComments.ForumTopicID
        LEFT JOIN Forum AS f ON f.ID = ft.ForumID
        LEFT JOIN UserAccounts AS ua ON ua.User = LatestComments.Author
        WHERE ft.RequiredPermissions <= '$permissions'
        ORDER BY LatestComments.DateCreated DESC
        LIMIT 0, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = [];

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numResults] = $db_entry;
            $numResults++;
        }

        return $numResults;
    } else {
        log_sql_fail();

        return null;
    }
}

function getRecentForumTopics(int $offset, int $count, int $permissions, int $numMessageChars = 90): array
{
    $query = "
        SELECT ft.ID as ForumTopicID, ft.Title as ForumTopicTitle,
               f.ID as ForumID, f.Title as ForumTitle,
               lc.CommentID, lftc.DateCreated as PostedAt, lftc.Author,
               LEFT(lftc.Payload, $numMessageChars) AS ShortMsg,
               LENGTH(lftc.Payload) > $numMessageChars AS IsTruncated,
               d1.CommentID as CommentID_1d, d1.Count as Count_1d,
               d7.CommentID as CommentID_7d, d7.Count as Count_7d
        FROM ForumTopic AS ft
        LEFT JOIN Forum AS f on f.ID = ft.ForumID
        LEFT JOIN (
            SELECT ftc.ForumTopicId, MAX(ftc.ID) as CommentID
            FROM ForumTopicComment ftc
            WHERE ftc.Authorised=1
            GROUP BY ftc.ForumTopicId
        ) AS lc ON lc.ForumTopicId = ft.ID
        LEFT JOIN ForumTopicComment AS lftc ON lftc.ID = lc.CommentID
        LEFT JOIN (
            SELECT ftc.ForumTopicId, MIN(ftc.ID) as CommentID, COUNT(ftc.ID) as Count
            FROM ForumTopicComment ftc
            WHERE ftc.Authorised=1 AND DateCreated >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            GROUP BY ftc.ForumTopicId
        ) AS d1 ON d1.ForumTopicId = ft.ID
        LEFT JOIN (
            SELECT ftc.ForumTopicId, MIN(ftc.ID) as CommentID, COUNT(ftc.ID) as Count
            FROM ForumTopicComment ftc
            WHERE ftc.Authorised=1 AND DateCreated >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY ftc.ForumTopicId
        ) AS d7 ON d7.ForumTopicId = ft.ID
        WHERE ft.RequiredPermissions <= $permissions
        ORDER BY PostedAt DESC
        LIMIT $offset, $count";

    $dataOut = [];

    $dbResult = s_mysql_query($query);
    if ($dbResult === false) {
        log_sql_fail();
    } else {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[] = $db_entry;
        }
    }

    return $dataOut;
}

function updateTopicPermissions(int $topicId, int $permissions): bool
{
    $query = "  UPDATE ForumTopic AS ft
                SET RequiredPermissions='$permissions'
                WHERE ID=$topicId";

    if (!s_mysql_query($query)) {
        log_sql_fail();

        return false;
    }

    return true;
}

function RemoveUnauthorisedForumPosts($user): bool
{
    sanitize_sql_inputs($user);

    // Removes all 'unauthorised' forum posts by a particular user
    $query = "DELETE FROM ForumTopicComment
              WHERE Author = '$user' AND Authorised = 0";

    if (!s_mysql_query($query)) {
        log_sql_fail();

        return false;
    }

    return true;
}

function AuthoriseAllForumPosts($user): bool
{
    sanitize_sql_inputs($user);

    // notify users of the posts now that they've been authorised
    $query = "SELECT ft.ID as TopicID, ft.Title as TopicTitle, ftc.ID as CommentID
              FROM ForumTopic ft
              LEFT JOIN ForumTopicComment ftc ON ftc.ForumTopicID=ft.ID
              WHERE ftc.Author = '$user' AND ftc.Authorised = 0";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            notifyUsersAboutForumActivity($db_entry['TopicID'], $db_entry['TopicTitle'], $user, $db_entry['CommentID']);
        }
    } else {
        log_sql_fail();
    }

    // Sets all unauthorised forum posts by a particular user to authorised
    $query = "UPDATE ForumTopicComment AS ftc
              SET ftc.Authorised = 1
              WHERE Author = '$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        // user's forum post comments have all been authorised!
        return true;
    } else {
        log_sql_fail();

        return false;
    }
}

function isUserSubscribedToForumTopic($topicID, $userID): bool
{
    return isUserSubscribedTo(
        SubscriptionSubjectType::ForumTopic,
        $topicID,
        $userID,
        "SELECT 1 FROM ForumTopicComment WHERE ForumTopicID = $topicID AND AuthorID = $userID"
    );
}
