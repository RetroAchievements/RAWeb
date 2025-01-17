<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\Permissions;
use App\Models\Forum;
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
                    f.id AS ID, f.forum_category_id AS CategoryID, f.title AS Title, f.description AS Description, f.order_column AS DisplayOrder,
                    fc.title AS CategoryName, fc.Description AS CategoryDescription,
                    COUNT(DISTINCT ft.id) AS NumTopics, COUNT( ft.id ) AS NumPosts,
                    ftc2.id AS LastPostID, ua.User AS LastPostAuthor, ftc2.created_at AS LastPostCreated,
                    ft2.title AS LastPostTopicName, ft2.id AS LastPostTopicID
                FROM forums AS f
                LEFT JOIN forum_categories AS fc ON fc.id = f.forum_category_id
                LEFT JOIN forum_topics AS ft ON ft.forum_id = f.id
                LEFT JOIN forum_topic_comments AS ftc ON ftc.forum_topic_id = ft.id
                LEFT JOIN forum_topic_comments AS ftc2 ON ftc2.id = f.latest_comment_id
                LEFT JOIN UserAccounts AS ua ON ua.ID = ftc2.author_id
                LEFT JOIN forum_topics AS ft2 ON ft2.id = ftc2.forum_topic_id ";

    if ($categoryID > 0) {
        $query .= "WHERE fc.id = '$categoryID' ";
    }
    $query .= "GROUP BY f.id ";
    $query .= "ORDER BY fc.order_column, f.order_column, f.id ";

    return legacyDbFetchAll($query)->toArray();
}

function getForumTopics(int $forumID, int $offset, int $count, int $permissions, ?int &$maxCountOut): ?array
{
    $query = "  SELECT COUNT(*) FROM forum_topics AS ft
                LEFT JOIN forum_topic_comments AS ftc ON ftc.id = ft.latest_comment_id
                WHERE ft.forum_id = $forumID AND ftc.is_authorized = 1
                AND ft.deleted_at IS NULL
                AND ft.required_permissions <= $permissions";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        $maxCountOut = (int) $data['COUNT(*)'];
    }

    $query = "  SELECT f.title AS ForumTitle, ft.id AS ForumTopicID, ft.Title AS TopicTitle, LEFT( ftc2.body, 54 ) AS TopicPreview, ft.author_id AS AuthorID, ft.created_at AS ForumTopicPostedDate, ftc.id AS LatestCommentID, ftc.author_id AS LatestCommentAuthorID, ftc.created_at AS LatestCommentPostedDate, (COUNT(ftc2.id)-1) AS NumTopicReplies
                FROM forum_topics AS ft
                LEFT JOIN forum_topic_comments AS ftc ON ftc.id = ft.latest_comment_id
                LEFT JOIN forums AS f ON f.id = ft.forum_id
                LEFT JOIN forum_topic_comments AS ftc2 ON ftc2.forum_topic_id = ft.id AND ftc2.is_authorized = 1
                WHERE ft.forum_id = $forumID
                AND ft.required_permissions <= $permissions
                AND ft.deleted_at IS NULL
                GROUP BY ft.id, LatestCommentPostedDate
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
    $query = "  SELECT ft.id AS ForumTopicID, ft.title AS TopicTitle, LEFT( ftc2.body, 60 ) AS TopicPreview, ft.author_id AS AuthorID, ft.created_at AS ForumTopicPostedDate, ftc.id AS LatestCommentID, ftc.author_id AS LatestCommentAuthorID, ftc.created_at AS LatestCommentPostedDate, (COUNT(ftc2.id)-1) AS NumTopicReplies
                FROM forum_topics AS ft
                LEFT JOIN forum_topic_comments AS ftc ON ftc.forum_topic_id = ft.id
                LEFT JOIN forums AS f ON f.id = ft.forum_id
                LEFT JOIN forum_topic_comments AS ftc2 ON ftc2.forum_topic_id = ft.id
                WHERE ftc.is_authorized = 0 AND ftc.deleted_at IS NULL AND ft.deleted_at IS NULL
                GROUP BY ft.id, LatestCommentPostedDate
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
        'forum_id' => $forumID,
        'title' => $topicTitle,
        'author_id' => $user->id,
        'latest_comment_id' => 0,
        'required_permissions' => 0,
    ]);

    // Finally, submit the first comment of the new topic.
    return submitTopicComment($user, $newTopic->id, $topicTitle, $topicPayload);
}

function setLatestCommentInForumTopic(int $topicID, int $commentID): bool
{
    // Update ForumTopic table
    $forumTopic = ForumTopic::find($topicID);
    if (!$forumTopic) {
        return false;
    }

    $forumTopic->latest_comment_id = $commentID;
    $forumTopic->timestamps = false;
    $forumTopic->save();

    $forum = Forum::find($forumTopic->forum_id);
    if ($forum) {
        $forum->latest_comment_id = $commentID;
        $forum->timestamps = false;
        $forum->save();
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
    $comment->body = $newPayload;
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
    $latestPost = $user->forumPosts()->latest('created_at')->first();
    if ($latestPost && $latestPost->forum_topic_id === $topicId
        && $latestPost->body === $commentPayload) {
        return $latestPost;
    }

    $newComment = new ForumTopicComment([
        'forum_topic_id' => $topicId,
        'body' => $commentPayload,
        'author_id' => $user->id,
        'is_authorized' => $user->ManuallyVerified ?? false,
    ]);
    $newComment->save();

    setLatestCommentInForumTopic($topicId, $newComment->id);

    if (!$topicTitle) {
        $topic = ForumTopic::find($topicId);
        $topicTitle = $topic?->title ?? '';
    }

    if ($user->ManuallyVerified ?? false) {
        notifyUsersAboutForumActivity($topicId, $topicTitle, $user, $newComment->id);
    }

    return $newComment;
}

function notifyUsersAboutForumActivity(int $topicID, string $topicTitle, User $author, int $commentID): void
{
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
                forum_topic_comments as ftc
                INNER JOIN UserAccounts AS ua ON ua.ID = ftc.author_id
            WHERE
                ftc.forum_topic_id = $topicID
        "
    );

    $payload = null;
    $comment = ForumTopicComment::find($commentID);
    if ($comment) {
        $payload = nl2br(Shortcode::stripAndClamp($comment->body, previewLength: 1000, preserveWhitespace: true));
    }

    $urlTarget = "viewtopic.php?t=$topicID&c=$commentID#$commentID";
    foreach ($subscribers as $sub) {
        sendActivityEmail($sub['User'], $sub['EmailAddress'], $topicID, $author->display_name, ArticleType::Forum, $topicTitle, $urlTarget, payload: $payload);
    }
}

function getTopicCommentCommentOffset(int $forumTopicID, int $commentID, int $count, ?int &$offset): bool
{
    // Focus on most recent comment
    if ($commentID == -1) {
        $commentID = 99_999_999;
    }

    $query = "SELECT COUNT(id) AS CommentOffset
              FROM forum_topic_comments
              WHERE created_at < (SELECT created_at FROM forum_topic_comments WHERE id = $commentID)
              AND forum_topic_id = $forumTopicID";

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

    $hashesURL = route('game.hashes.index', ['game' => $gameId]);
    $gameFAQsURL = "https://www.google.com/search?q=site:www.gamefaqs.com+$urlSafeGameTitle";
    $longplaysURL = "https://www.google.com/search?q=site:www.youtube.com+longplay+$urlSafeGameTitle";
    $wikipediaURL = "https://www.google.com/search?q=site:en.wikipedia.org+$urlSafeGameTitle";

    $topicPayload = "Official Topic Post for discussion about [game=$gameId]\n" .
        "Created " . date("j M, Y H:i") . " by [user={$user->User}]\n\n" .
        "[b][url=$hashesURL]Supported Game Files[/url][/b]\n\n" .
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
            $userClause .= " AND ftc.is_authorized = 1";
        }
    } else {
        $userClause = "ftc.is_authorized = 1";
    }

    // 02:08 21/02/2014 - cater for 20 spam messages
    $query = "
        SELECT LatestComments.created_at AS PostedAt,
            LatestComments.body AS Payload,
            ua.User as Author,
            ua.display_name as AuthorDisplayName,
            ua.RAPoints,
            ua.Motto,
            ft.id AS ForumTopicID,
            ft.title AS ForumTopicTitle,
            LatestComments.author_id AS author_id,
            LatestComments.id AS CommentID
        FROM
        (
            SELECT *
            FROM forum_topic_comments AS ftc
            WHERE $userClause
            ORDER BY ftc.created_at DESC
            LIMIT :fromOffset, :fromLimit
        ) AS LatestComments
        INNER JOIN forum_topics AS ft ON ft.id = LatestComments.forum_topic_id
        LEFT JOIN forums AS f ON f.id = ft.forum_id
        LEFT JOIN UserAccounts AS ua ON ua.ID = LatestComments.author_id
        WHERE ft.required_permissions <= :permissions AND ft.deleted_at IS NULL
        ORDER BY LatestComments.created_at DESC
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
    $query = "  UPDATE forum_topics AS ft
                SET required_permissions='$permissions'
                WHERE id=$topicId";

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
            $query->select('id', 'title');
        }])
        ->get();

    foreach ($userUnauthorizedPosts as $unauthorizedPost) {
        if ($unauthorizedPost->forumTopic) {
            notifyUsersAboutForumActivity(
                $unauthorizedPost->forumTopic->id,
                $unauthorizedPost->forumTopic->title,
                $user,
                $unauthorizedPost->id,
            );
        }
    }

    // Set all unauthorized forum posts by the user to authorized.
    $user->forumPosts()->unauthorized()->update([
        'is_authorized' => 1,
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
    return ForumTopicComment::where('forum_topic_id', $topicID)
        ->where('author_id', $userID)
        ->exists();
}
