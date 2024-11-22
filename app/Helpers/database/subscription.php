<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\UserPreference;
use App\Models\Comment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Update a subscription, i.e, either subscribe or unsubscribe a given user to or from a subject.
 *
 * @param bool $state whether the user is to be subscribed (true) or unsubscribed (false)
 */
function updateSubscription(string $subjectType, int $subjectId, int $userId, bool $state): bool
{
    Subscription::updateOrCreate(
        [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'user_id' => $userId,
        ],
        [
            'state' => $state,
        ]
    );

    return true;
}

/**
 * Checks whether a given user is subscribed to a subject explicitly.
 */
function isUserSubscribedTo(string $subjectType, int $topicID, int $userID): bool
{
    return Subscription::where('subject_type', $subjectType)
        ->where('subject_id', $topicID)
        ->where('user_id', $userID)
        ->where('state', 1)
        ->exists();
}

/**
 * @deprecated $implicitSubscriptionQry considered harmful. Use Eloquent ORM.
 *
 * Retrieves the list of users that are subscribed to a given subject either implicitly or explicitly.
 *
 * @param ?int $reqWebsitePrefs optional required website preferences for a user to be considered a subscriber
 * @param ?string $implicitSubscriptionQry sql query that returns the set of users that are implicitly subscribed to
 *                                        the subject (must return whole UserAccounts rows)
 */
function getSubscribersOf(string $subjectType, int $subjectID, ?int $reqWebsitePrefs = null, ?string $implicitSubscriptionQry = null): array
{
    $explicitSubscribers = Subscription::query()
        ->select('UserAccounts.User', 'UserAccounts.EmailAddress')
        ->join('UserAccounts', 'UserAccounts.ID', '=', 'subscriptions.user_id')
        ->where('subscriptions.subject_type', $subjectType)
        ->where('subscriptions.subject_id', $subjectID)
        ->where('subscriptions.state', 1);

    if ($reqWebsitePrefs !== null) {
        $explicitSubscribers->whereRaw('(UserAccounts.websitePrefs & ?) != 0', [$reqWebsitePrefs]);
    }

    $explicitSubscribersResult = $explicitSubscribers->get()->toArray();

    if ($implicitSubscriptionQry === null) {
        return $explicitSubscribersResult;
    } else {
        $implicitUsers = DB::select($implicitSubscriptionQry);

        // Once executed, extract user IDs from the result.
        $userIds = array_map(function ($user) {
            return $user->ID;
        }, $implicitUsers);

        $implicitSubscribersQuery = User::query()
            ->select('UserAccounts.User', 'UserAccounts.EmailAddress')
            ->leftJoin('subscriptions as _sub', function ($join) use ($subjectType, $subjectID) {
                $join->on('_sub.user_id', '=', 'UserAccounts.ID')
                     ->where('_sub.subject_type', '=', $subjectType)
                     ->where('_sub.subject_id', '=', $subjectID);
            })
            ->whereIn('UserAccounts.ID', $userIds)
            ->whereRaw('COALESCE(_sub.state, 1) = 1');

        if ($reqWebsitePrefs !== null) {
            $implicitSubscribersQuery->whereRaw('(UserAccounts.websitePrefs & ?) != 0', [$reqWebsitePrefs]);
        }

        $implicitSubscribersResult = $implicitSubscribersQuery->get()->toArray();

        // Merge and remove duplicates.
        $mergedResults = array_merge($explicitSubscribersResult, $implicitSubscribersResult);
        $uniqueResults = [];

        foreach ($mergedResults as $result) {
            if (isset($result['User']) && isset($result['EmailAddress'])) {
                $key = $result['User'] . '|' . $result['EmailAddress'];
                $uniqueResults[$key] = $result;
            }
        }

        return array_values($uniqueResults);
    }
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
    return getSubscribersOfArticle(ArticleType::Game, $gameID, 1 << UserPreference::EmailOn_AchievementComment);
}

function getSubscribersOfAchievement(int $achievementID, int $gameID, string $achievementAuthor): array
{
    // users directly subscribed to the achievement
    $achievementSubs = getSubscribersOfArticle(ArticleType::Achievement, $achievementID, 1 << UserPreference::EmailOn_AchievementComment, $achievementAuthor);

    // devs subscribed to the achievement through the game
    $gameAchievementsSubs = getSubscribersOf(SubscriptionSubjectType::GameAchievements, $gameID, 1 << UserPreference::EmailOn_ActivityComment);

    return mergeSubscribers($achievementSubs, $gameAchievementsSubs);
}

function getSubscribersOfUserWall(int $userID, string $userName): array
{
    return getSubscribersOfArticle(ArticleType::User, $userID, 1 << UserPreference::EmailOn_UserWallComment, $userName);
}

function getSubscribersOfTicket(int $ticketID, string $ticketAuthor, int $gameID): array
{
    // users directly subscribed to the ticket
    $ticketSubs = getSubscribersOfArticle(ArticleType::AchievementTicket, $ticketID, 1 << UserPreference::EmailOn_TicketActivity, $ticketAuthor, true);

    // devs subscribed to the ticket through the game
    $gameTicketsSubs = getSubscribersOf(SubscriptionSubjectType::GameTickets, $gameID, 1 << UserPreference::EmailOn_TicketActivity);

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
        INNER JOIN UserAccounts as _ua ON _ua.ID = _c.user_id
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
        1 << UserPreference::EmailOn_ActivityComment,  // code suggests the value of $reqWebsitePrefs should be used, but the feature is disabled for now
        $qry
    );
}

function isUserSubscribedToArticleComments(int $articleType, int $articleID, int $userID): bool
{
    $subjectType = SubscriptionSubjectType::fromArticleType($articleType);

    if ($subjectType === null) {
        return false;
    }

    $explicitSubcription = Subscription::where('subject_type', $subjectType)
        ->where('subject_id', $articleID)
        ->where('user_id', $userID)
        ->first();

    if ($explicitSubcription) {
        return $explicitSubcription->state;
    }

    // a user is implicitly subscribed if they've authored at least one comment for the article
    return Comment::where('ArticleType', $articleType)
        ->where('ArticleID', $articleID)
        ->where('user_id', $userID)
        ->exists();
}
