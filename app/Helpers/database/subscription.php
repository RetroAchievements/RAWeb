<?php

use App\Community\Enums\SubscriptionSubjectType;

/**
 * Update a subscription, i.e, either subscribe or unsubscribe a given user to or from a subject.
 *
 * @param bool $state whether the user is to be subscribed (true) or unsubscribed (false)
 */
function updateSubscription(string $subjectType, int $subjectID, int $userID, bool $state): bool
{
    sanitize_sql_inputs($subjectType);
    $state = (int) $state;

    $query = "
        INSERT INTO Subscription(SubjectType, SubjectID, UserID, State)
        VALUES ('$subjectType', $subjectID, $userID, '$state')
        ON DUPLICATE KEY UPDATE State = '$state'
    ";

    $dbResult = s_mysql_query($query);

    return (bool) $dbResult;
}

/**
 * Checks whether a given user is subscribed to a subject, whether implicitly or explicitly.
 *
 * @param string|null $implicitSubscriptionQry optional sql query capable of identifying the existence of an implicit
 *                                         subscription to the subject (must be usable inside an EXISTS clause)
 */
function isUserSubscribedTo(string $subjectType, int $subjectID, int $userID, ?string $implicitSubscriptionQry = null): bool
{
    if (!$userID) {
        return false;
    }
    sanitize_sql_inputs($subjectType);

    if ($implicitSubscriptionQry === null) {
        $query = "
            SELECT 1
            FROM Subscription
            WHERE
              SubjectType = '$subjectType'
              AND SubjectID = $subjectID
              AND UserID = $userID
              AND State = 1
        ";
    } else {
        // either there's an explicit subscription...
        // ...or there's an implicit subscription without an explicit unsubscription
        // optional sql query capable of identifying the existence of an implicit
        // subscription to the subject (must be usable inside an EXISTS clause)
        $query = "
            SELECT 1
            FROM Subscription
            WHERE
              EXISTS (
                SELECT 1
                FROM Subscription
                WHERE
                  SubjectType = '$subjectType'
                  AND SubjectID = $subjectID
                  AND UserID = $userID
                  AND State = 1
              )
              OR (
                  EXISTS (
                    $implicitSubscriptionQry
                  )
                  AND NOT EXISTS (
                    SELECT 1
                    FROM Subscription
                    WHERE
                      SubjectType = '$subjectType'
                      AND SubjectID = $subjectID
                      AND UserID = $userID
                      AND State = 0
                  )
              )
        ";
    }

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return false;
    }

    $isSubscribed = mysqli_num_rows($dbResult) > 0;
    mysqli_free_result($dbResult);

    return $isSubscribed;
}

/**
 * Retrieves the list of users that are subscribed to a given subject either implicitly or explicitly.
 *
 * @param ?int $reqWebsitePrefs optional required website preferences for a user to be considered a subscriber
 * @param ?string $implicitSubscriptionQry sql query that returns the set of users that are implicitly subscribed to
 *                                        the subject (must return whole UserAccounts rows)
 */
function getSubscribersOf(string $subjectType, int $subjectID, int $reqWebsitePrefs = null, string $implicitSubscriptionQry = null): array
{
    sanitize_sql_inputs($subjectType);

    $websitePrefsFilter = (
        $reqWebsitePrefs === null ? "" : "AND (_ua.websitePrefs & $reqWebsitePrefs) != 0"
    );

    $explicitSubscriptionQry = "
        SELECT
          _ua.User,
          _ua.EmailAddress
        FROM
          Subscription AS _sub
          INNER JOIN UserAccounts AS _ua
              ON _ua.ID = _sub.UserID
        WHERE
          _sub.SubjectType = '$subjectType'
          AND _sub.SubjectID = $subjectID
          AND _sub.State = 1
          $websitePrefsFilter
    ";

    if ($implicitSubscriptionQry === null) {
        $query = $explicitSubscriptionQry;
    } else {
        $query = "
          SELECT
            _ua.User,
            _ua.EmailAddress
          FROM
            (
              $implicitSubscriptionQry
            ) as _ua
            LEFT JOIN Subscription AS _sub
                ON (_sub.SubjectType = '$subjectType'
                    AND _sub.SubjectID = $subjectID
                    AND _sub.UserID = _ua.ID)
            WHERE
              COALESCE(_sub.State, 1) = 1
              $websitePrefsFilter
          UNION
          $explicitSubscriptionQry
      ";
    }

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        return [];
    }

    return mysqli_fetch_all($dbResult, MYSQLI_ASSOC);
}

/**
 * Merges two lists of subscribers as returned by `getSubscribersOf`.
 *
 * TODO replace with standard collection/array merge?
 */
function mergeSubscribers(array $subscribersA, array $subscribersB): array
{
    // not efficient, but we'll never be dealing with *very* large lists here...

    foreach ($subscribersB as $subB) {
        $found = false;
        foreach ($subscribersA as $subA) {
            if ($subA['User'] == $subB['User']) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $subscribersA[] = $subB;
        }
    }

    return $subscribersA;
}

function getSubscribersOfGameWall(int $gameID): array
{
    return getSubscribersOfArticle(1, $gameID, 1 << 1);
}

function getSubscribersOfAchievement(int $achievementID, int $gameID, string $achievementAuthor): array
{
    // users directly subscribed to the achievement
    $achievementSubs = getSubscribersOfArticle(2, $achievementID, 1 << 1, $achievementAuthor);

    // devs subscribed to the achievement through the game
    $gameAchievementsSubs = getSubscribersOf(SubscriptionSubjectType::GameAchievements, $gameID, 1 << 0 /* (1 << 1) */);

    return mergeSubscribers($achievementSubs, $gameAchievementsSubs);
}

function getSubscribersOfUserWall(int $userID, string $userName): array
{
    return getSubscribersOfArticle(3, $userID, 1 << 2, $userName);
}

function getSubscribersOfFeedActivity(int $activityID, string $author): array
{
    return getSubscribersOfArticle(5, $activityID, 1 << 0, $author, true);
}

function getSubscribersOfTicket(int $ticketID, string $ticketAuthor, int $gameID): array
{
    // users directly subscribed to the ticket
    $ticketSubs = getSubscribersOfArticle(7, $ticketID, 1 << 1, $ticketAuthor, true);

    // devs subscribed to the ticket through the game
    $gameTicketsSubs = getSubscribersOf(SubscriptionSubjectType::GameTickets, $gameID, 1 << 0 /* (1 << 1) */);

    return mergeSubscribers($ticketSubs, $gameTicketsSubs);
}

function getSubscribersOfArticle(
    int $articleType,
    int $articleID,
    int $reqWebsitePrefs,
    ?string $subjectAuthor = null,
    bool $noExplicitSubscriptions = false
): array {
    $websitePrefsFilter = $noExplicitSubscriptions ? "AND (_ua.websitePrefs & $reqWebsitePrefs) != 0" : "";

    $authorQry = ($subjectAuthor === null ? "" : "
        UNION
        SELECT _ua.*
        FROM UserAccounts as _ua
        WHERE _ua.User = '$subjectAuthor'
              $websitePrefsFilter
    ");

    $qry = "
        SELECT DISTINCT _ua.*
        FROM Comment AS _c
        INNER JOIN UserAccounts as _ua ON _ua.ID = _c.UserID
        WHERE _c.ArticleType = $articleType
              AND _c.ArticleID = $articleID
              $websitePrefsFilter
        $authorQry
    ";

    if ($noExplicitSubscriptions) {
        $dbResult = s_mysql_query($qry);
        if (!$dbResult) {
            log_sql_fail();

            return [];
        }

        return mysqli_fetch_all($dbResult, MYSQLI_ASSOC);
    }

    $subjectType = SubscriptionSubjectType::fromArticleType($articleType);
    if ($subjectType === null) {
        return [];
    }

    return getSubscribersOf(
        $subjectType,
        $articleID,
        1 << 0,  // code suggests the value of $reqWebsitePrefs should be used, but the feature is disabled for now
        $qry
    );
}

function isUserSubscribedToArticleComments(int $articleType, int $articleID, int $userID): bool
{
    $subjectType = SubscriptionSubjectType::fromArticleType($articleType);

    if ($subjectType === null) {
        return false;
    }

    return isUserSubscribedTo(
        $subjectType,
        $articleID,
        $userID,
        "
            SELECT DISTINCT ua.*
            FROM
                Comment AS c
                LEFT JOIN UserAccounts AS ua ON ua.ID = c.UserID
            WHERE
                c.ArticleType = $articleType
                AND c.ArticleID = $articleID
                AND c.UserID = $userID
        "
    );
}
