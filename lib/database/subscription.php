<?php

require_once(__DIR__ . '/../bootstrap.php');

/**
 * Update a subscription, i.e, either subscribe or unsubscribe a given user to or from a subject.
 *
 * @param string $subjectType subject type
 * @param integer $subjectID subject id
 * @param integer $userID user id
 * @param bool $state whether the user is to be subscribed (true) or unsubscribed (false)
 * @return bool whether the update was successful
 */
function updateSubscription($subjectType, $subjectID, $userID, $state)
{
    $stateInt = ($state ? 1 : 0);
    $query = "
        INSERT INTO Subscription(SubjectType, SubjectID, UserID, State)
        VALUES ('$subjectType', $subjectID, $userID, b'$stateInt')
        ON DUPLICATE KEY UPDATE State = b'$stateInt'
    ";

    $dbResult = s_mysql_query($query);
    if ($dbResult === false) {
        global $db;
        error_log(__FUNCTION__ . ": " . mysqli_error($db));
        error_log($query);
        return false;
    }

    return true;
}

/**
 * Checks whether a given user is subscribed to a subject, whether implicitly or explicitly.
 *
 * @param string $subjectType subject type
 * @param integer $subjectID subject id
 * @param integer $userID user id
 * @param string $implicitSubscriptionQry optional sql query capable of identifying the existence of an implicit
 *                                         subscription to the subject (must be usable inside an EXISTS clause)
 * @return bool whether the user is subscribed to the subject
 */
function isUserSubscribedTo($subjectType, $subjectID, $userID, $implicitSubscriptionQry = null)
{

    if (is_null($implicitSubscriptionQry)) {
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

    global $db;
    $dbResult = s_mysql_query($query);
    if ($dbResult === false) {
        error_log(__FUNCTION__ . ": " . mysqli_error($db));
        // error_log($query);
        return false;
    }

    $isSubscribed = mysqli_num_rows($dbResult) > 0;
    mysqli_free_result($dbResult);
    return $isSubscribed;
}

/**
 * Retrieves the list of users that are subscribed to a given subject either implicitly or explicitly.
 *
 * @param string $subjectType subject type
 * @param integer $subjectID subject id
 * @param integer $reqWebsitePrefs optional required website preferences for a user to be considered a subscriber
 * @param string $implicitSubscriptionQry sql query that returns the set of users that are implicitly subscribed to
 *                                        the subject (must return whole UserAccounts rows)
 * @return array of subscribers, each as an assoc array with "User" and "Email Address" keys
 */
function getSubscribersOf($subjectType, $subjectID, $reqWebsitePrefs = null, $implicitSubscriptionQry = null)
{
    $websitePrefsFilter = (
    is_null($reqWebsitePrefs) ? "" : "AND (_ua.websitePrefs & $reqWebsitePrefs) != 0");

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

    if (is_null($implicitSubscriptionQry)) {
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

    if ($dbResult === false) {
        error_log($query);
        return [];
    }

    return mysqli_fetch_all($dbResult, MYSQLI_ASSOC);
}

/**
 * Merges two lists of subscribers as returned by `getSubscribersOf`.
 *
 * @param array $subscribersA
 * @param array $subscribersB
 * @return array
 */
function mergeSubscribers($subscribersA, $subscribersB)
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
