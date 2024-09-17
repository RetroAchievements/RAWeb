<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\Permissions;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Collection;

function getForumList(int $categoryID = 0): array
{
    $query = "  SELECT
                    f.ID, f.CategoryID, f.Title, f.Description, f.DisplayOrder,
                    fc.Name AS CategoryName, fc.Description AS CategoryDescription,
                    COUNT(DISTINCT ft.ID) AS NumTopics, COUNT( ft.ID ) AS NumPosts,
                    ftc2.ID AS LastPostID, ua.User AS LastPostAuthor, ftc2.DateCreated AS LastPostCreated,
                    ft2.Title AS LastPostTopicName, ft2.ID AS LastPostTopicID
                FROM Forum AS f
                LEFT JOIN ForumCategory AS fc ON fc.ID = f.CategoryID
                LEFT JOIN ForumTopic AS ft ON ft.ForumID = f.ID
                LEFT JOIN ForumTopicComment AS ftc ON ftc.ForumTopicID = ft.ID
                LEFT JOIN ForumTopicComment AS ftc2 ON ftc2.ID = f.LatestCommentID
                LEFT JOIN UserAccounts AS ua ON ua.ID = ftc2.author_id
                LEFT JOIN ForumTopic AS ft2 ON ft2.ID = ftc2.ForumTopicID ";

    if ($categoryID > 0) {
        $query .= "WHERE fc.ID = '$categoryID' ";
    }
    $query .= "GROUP BY f.ID ";
    $query .= "ORDER BY fc.DisplayOrder, f.DisplayOrder, f.ID ";

    return legacyDbFetchAll($query)->toArray();
}

function getForumTopics(int $forumID, int $offset, int $count, int $permissions, ?int &$maxCountOut): ?array
{
    $query = "  SELECT COUNT(*) FROM ForumTopic AS ft
                LEFT JOIN ForumTopicComment AS ftc ON ftc.ID = ft.LatestCommentID
                WHERE ft.ForumID = $forumID AND ftc.Authorised = 1
                AND ft.deleted_at IS NULL
                AND ft.RequiredPermissions <= $permissions";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        $maxCountOut = (int) $data['COUNT(*)'];
    }

    $query = "  SELECT f.Title AS ForumTitle, ft.ID AS ForumTopicID, ft.Title AS TopicTitle, LEFT( ftc2.Payload, 54 ) AS TopicPreview, ft.author_id AS AuthorID, ft.DateCreated AS ForumTopicPostedDate, ftc.ID AS LatestCommentID, ftc.author_id AS LatestCommentAuthorID, ftc.DateCreated AS LatestCommentPostedDate, (COUNT(ftc2.ID)-1) AS NumTopicReplies
                FROM ForumTopic AS ft
                LEFT JOIN ForumTopicComment AS ftc ON ftc.ID = ft.LatestCommentID
                LEFT JOIN Forum AS f ON f.ID = ft.ForumID
                LEFT JOIN ForumTopicComment AS ftc2 ON ftc2.ForumTopicID = ft.ID AND ftc2.Authorised = 1
                WHERE ft.ForumID = $forumID
                AND ft.RequiredPermissions <= $permissions
                AND ft.deleted_at IS NULL
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
    }
    log_sql_fail();

    return null;
}

function getUnauthorisedForumLinks(): ?array
{
    $query = "  SELECT ft.ID AS ForumTopicID, ft.Title AS TopicTitle, LEFT( ftc2.Payload, 60 ) AS TopicPreview, ft.author_id AS AuthorID, ft.DateCreated AS ForumTopicPostedDate, ftc.ID AS LatestCommentID, ftc.author_id AS LatestCommentAuthorID, ftc.DateCreated AS LatestCommentPostedDate, (COUNT(ftc2.ID)-1) AS NumTopicReplies
                FROM ForumTopic AS ft
                LEFT JOIN ForumTopicComment AS ftc ON ftc.ForumTopicID = ft.ID
                LEFT JOIN Forum AS f ON f.ID = ft.ForumID
                LEFT JOIN ForumTopicComment AS ftc2 ON ftc2.ForumTopicID = ft.ID
                WHERE ftc.Authorised = 0 AND ftc.deleted_at IS NULL AND ft.deleted_at IS NULL
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
    }
    log_sql_fail();

    return null;
}

function submitNewTopic(
    User $user,
    int $forumID,
    string $topicTitle,
    string $topicPayload,
): ForumTopicComment {
    // First, create the topic.
    $newTopic = ForumTopic::create([
        'ForumID' => $forumID,
        'Title' => $topicTitle,
        'author_id' => $user->id,
        'LatestCommentID' => 0,
        'RequiredPermissions' => 0,
    ]);

    // Finally, submit the first comment of the new topic.
    return submitTopicComment($user, $newTopic->id, $topicTitle, $topicPayload);
}

function setLatestCommentInForumTopic(int $topicID, int $commentID): bool
{
    // Update ForumTopic table
    $query = "UPDATE ForumTopic SET LatestCommentID=$commentID WHERE ID=$topicID";
    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();
    }

    // Propagate to Forum table
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

function editTopicComment(int $commentId, string $newPayload): void
{
    // Take any RA links and convert them to relevant shortcodes.
    // eg: "https://retroachievements.org/game/1" --> "[game=1]"
    $newPayload = normalize_shortcodes($newPayload);

    // Convert [user=$user->username] to [user=$user->id].
    $newPayload = Shortcode::convertUserShortcodesToUseIds($newPayload);

    $comment = ForumTopicComment::findOrFail($commentId);
    $comment->Payload = $newPayload;
    $comment->save();
}

function submitTopicComment(
    User $user,
    int $topicId,
    ?string $topicTitle,
    string $commentPayload,
): ForumTopicComment {
    // Take any RA links and convert them to relevant shortcodes.
    // eg: "https://retroachievements.org/game/1" --> "[game=1]"
    $commentPayload = normalize_shortcodes($commentPayload);

    // Convert [user=$user->username] to [user=$user->id].
    $commentPayload = Shortcode::convertUserShortcodesToUseIds($commentPayload);

    // if this exact message was just posted by this user, assume it's an
    // accidental double submission and ignore.
    $latestPost = $user->forumPosts()->latest('DateCreated')->first();
    if ($latestPost && $latestPost->forum_topic_id === $topicId
        && $latestPost->body === $commentPayload) {
        return $latestPost;
    }

    $newComment = new ForumTopicComment([
        'ForumTopicID' => $topicId,
        'Payload' => $commentPayload,
        'author_id' => $user->id,
        'Authorised' => $user->ManuallyVerified ?? false,
    ]);
    $newComment->save();

    setLatestCommentInForumTopic($topicId, $newComment->id);

    if (!$topicTitle) {
        $topic = ForumTopic::find($topicId);
        $topicTitle = $topic?->title ?? '';
    }

    if ($user->ManuallyVerified) {
        notifyUsersAboutForumActivity($topicId, $topicTitle, $user->User, $newComment->id);
    }

    return $newComment;
}

function notifyUsersAboutForumActivity(int $topicID, string $topicTitle, string $author, int $commentID): void
{
    sanitize_sql_inputs($author);

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
                INNER JOIN UserAccounts AS ua ON ua.ID = ftc.author_id
            WHERE
                ftc.ForumTopicID = $topicID
        "
    );

    $urlTarget = "viewtopic.php?t=$topicID&c=$commentID#$commentID";
    foreach ($subscribers as $sub) {
        sendActivityEmail($sub['User'], $sub['EmailAddress'], $topicID, $author, ArticleType::Forum, $topicTitle, $urlTarget);
    }
}

function getTopicCommentCommentOffset(int $forumTopicID, int $commentID, int $count, ?int &$offset): bool
{
    // Focus on most recent comment
    if ($commentID == -1) {
        $commentID = 99_999_999;
    }

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
    }
    $offset = 0;

    return false;
}

function generateGameForumTopic(User $user, int $gameId): ?ForumTopicComment
{
    if ($gameId === 0) {
        return null;
    }

    $game = Game::with('system')->find($gameId);
    if (!$game) {
        return null;
    }

    // If a valid forum topic already exists for the game, bail.
    if ($game->ForumTopicID > 0 && ForumTopic::find($game->ForumTopicID)->exists()) {
        return null;
    }

    // TODO we probably can't get away with hardcoding this indefinitely.
    $forumId = match ($game->system->id) {
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

    $gameTitle = $game->title;
    $consoleName = $game->system->name;

    $topicTitle = $gameTitle;

    $urlSafeGameTitle = str_replace(" ", "+", "$gameTitle $consoleName");
    $urlSafeGameTitle = str_replace("'", "''", $urlSafeGameTitle);

    $gameFAQsURL = "https://www.google.com/search?q=site:www.gamefaqs.com+$urlSafeGameTitle";
    $longplaysURL = "https://www.google.com/search?q=site:www.youtube.com+longplay+$urlSafeGameTitle";
    $wikipediaURL = "https://www.google.com/search?q=site:en.wikipedia.org+$urlSafeGameTitle";

    $topicPayload = "Official Topic Post for discussion about [game=$gameId]\n" .
        "Created " . date("j M, Y H:i") . " by [user={$user->User}]\n\n" .
        "[b]Resources:[/b]\n" .
        // FIXME there is a bug here. these links are malformed for some games, such as game id 26257
        "[url=$gameFAQsURL]GameFAQs[/url]\n" .
        "[url=$longplaysURL]Longplay[/url]\n" .
        "[url=$wikipediaURL]Wikipedia[/url]\n";

    $forumTopicComment = submitNewTopic($user, $forumId, $topicTitle, $topicPayload);

    $game->ForumTopicID = $forumTopicComment->forumTopic->id;
    $game->save();

    return $forumTopicComment;
}

/**
 * @return Collection<int, non-empty-array>
 */
function getRecentForumPosts(
    int $offset,
    int $limit,
    int $numMessageChars,
    ?int $permissions = Permissions::Unregistered,
    ?int $fromAuthorId = null
): Collection {
    $bindings = [
        'fromOffset' => $offset,
        'fromLimit' => $limit + 20,
        'permissions' => $permissions ?? Permissions::Unregistered,
        'limit' => $limit,
    ];

    if (!empty($fromAuthorId)) {
        $bindings['fromAuthorId'] = $fromAuthorId;
        $userClause = "ftc.author_id = :fromAuthorId";
        if ($permissions < Permissions::Moderator) {
            $userClause .= " AND ftc.Authorised = 1";
        }
    } else {
        $userClause = "ftc.Authorised = 1";
    }

    // 02:08 21/02/2014 - cater for 20 spam messages
    $query = "
        SELECT LatestComments.DateCreated AS PostedAt,
            LatestComments.Payload,
            ua.User as Author,
            ua.display_name as AuthorDisplayName,
            ua.RAPoints,
            ua.Motto,
            ft.ID AS ForumTopicID,
            ft.Title AS ForumTopicTitle,
            LatestComments.author_id AS author_id,
            LatestComments.ID AS CommentID
        FROM
        (
            SELECT *
            FROM ForumTopicComment AS ftc
            WHERE $userClause
            ORDER BY ftc.DateCreated DESC
            LIMIT :fromOffset, :fromLimit
        ) AS LatestComments
        INNER JOIN ForumTopic AS ft ON ft.ID = LatestComments.ForumTopicID
        LEFT JOIN Forum AS f ON f.ID = ft.ForumID
        LEFT JOIN UserAccounts AS ua ON ua.ID = LatestComments.author_id
        WHERE ft.RequiredPermissions <= :permissions AND ft.deleted_at IS NULL
        ORDER BY LatestComments.DateCreated DESC
        LIMIT 0, :limit";

    /** @var Collection<int, non-empty-array> */
    return legacyDbFetchAll($query, $bindings)
        ->map(function ($post) use ($numMessageChars) {
            $post['ShortMsg'] = mb_substr($post['Payload'], 0, $numMessageChars);
            $post['IsTruncated'] = mb_strlen($post['Payload']) > $numMessageChars;

            return $post;
        });
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

function authorizeAllForumPostsForUser(User $user): bool
{
    $userUnauthorizedPosts = $user->forumPosts()
        ->unauthorized()
        ->with(['forumTopic' => function ($query) {
            $query->select('ID', 'Title');
        }])
        ->get();

    foreach ($userUnauthorizedPosts as $unauthorizedPost) {
        if ($unauthorizedPost->forumTopic) {
            notifyUsersAboutForumActivity(
                $unauthorizedPost->forumTopic->id,
                $unauthorizedPost->forumTopic->title,
                $user->User,
                $unauthorizedPost->id,
            );
        }
    }

    // Set all unauthorized forum posts by the user to authorized.
    $user->forumPosts()->unauthorized()->update([
        'Authorised' => 1,
        'authorized_at' => now(),
    ]);

    return true;
}

function isUserSubscribedToForumTopic(int $topicID, int $userID): bool
{
    $explicitSubscription = Subscription::where('subject_type', SubscriptionSubjectType::ForumTopic)
        ->where('subject_id', $topicID)
        ->where('user_id', $userID)
        ->first();

    if ($explicitSubscription) {
        return $explicitSubscription->state;
    }

    // a user is implicitly subscribed if they've authored at least one post in the topic
    return ForumTopicComment::where('ForumTopicID', $topicID)
        ->where('author_id', $userID)
        ->exists();
}
