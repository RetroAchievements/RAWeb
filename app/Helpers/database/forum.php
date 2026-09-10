<?php

use App\Community\Actions\BuildThinRecentForumPostsDataAction;
use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionNotificationService;
use App\Community\Services\SubscriptionService;
use App\Enums\Permissions;
use App\Enums\UserPreference;
use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\User;
use App\Policies\ForumTopicCommentPolicy;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @param array<int, int> $maskedAuthorIds
 */
function getForumList(int $categoryID = 0, array $maskedAuthorIds = []): array
{
    $query = DB::table('forums as f')
        ->selectRaw('
            f.id AS ID, f.forum_category_id AS CategoryID, f.title AS Title, f.description AS Description, f.order_column AS DisplayOrder,
            fc.title AS CategoryName, fc.Description AS CategoryDescription,
            COUNT(DISTINCT ft.id) AS NumTopics, COUNT( ft.id ) AS NumPosts,
            ftc2.id AS LastPostID, ftc2.author_id AS LastPostAuthorID, ua.username AS LastPostAuthor, ftc2.created_at AS LastPostCreated,
            ft2.title AS LastPostTopicName, ft2.id AS LastPostTopicID
        ')
        ->leftJoin('forum_categories as fc', 'fc.id', '=', 'f.forum_category_id')
        ->leftJoin('forum_topics as ft', 'ft.forum_id', '=', 'f.id')
        ->leftJoin('forum_topic_comments as ftc', 'ftc.forum_topic_id', '=', 'ft.id')
        ->leftJoin('forum_topic_comments as ftc2', 'ftc2.id', '=', 'f.latest_comment_id')
        ->leftJoin('users as ua', 'ua.id', '=', 'ftc2.author_id')
        ->leftJoin('forum_topics as ft2', 'ft2.id', '=', 'ftc2.forum_topic_id');

    if ($categoryID > 0) {
        $query->where('fc.id', $categoryID);
    }

    $query->groupBy('f.id')
        ->orderBy('fc.order_column')
        ->orderBy('f.order_column')
        ->orderBy('f.id');

    $forums = $query->get()
        ->map(fn ($row): array => (array) $row)
        ->toArray();

    // Masked (blocked) most recent users are swapped for the
    // most recent post the current user can see.
    foreach ($forums as $index => $forum) {
        $lastPostAuthorId = $forum['LastPostAuthorID'];
        if ($lastPostAuthorId === null || !in_array((int) $lastPostAuthorId, $maskedAuthorIds, true)) {
            continue;
        }

        $replacement = ForumTopicComment::query()
            ->with(['user', 'forumTopic'])
            ->whereNotIn('author_id', $maskedAuthorIds)
            ->whereHas('forumTopic', function ($query) use ($forum, $maskedAuthorIds) {
                $query->where('forum_id', $forum['ID'])
                    ->whereNotIn('author_id', $maskedAuthorIds);
            })
            ->orderByDesc('created_at')
            ->first();

        $forums[$index]['LastPostID'] = $replacement?->id;
        $forums[$index]['LastPostAuthor'] = $replacement?->user?->username;
        $forums[$index]['LastPostCreated'] = $replacement?->created_at?->toDateTimeString();
        $forums[$index]['LastPostTopicName'] = $replacement?->forumTopic?->title;
        $forums[$index]['LastPostTopicID'] = $replacement?->forumTopic?->id;
    }

    return $forums;
}

/**
 * @param array<int, int> $maskedAuthorIds authors the viewing user has blocked
 */
function getForumTopics(
    int $forumID,
    int $offset,
    int $count,
    int $permissions,
    ?int &$maxCountOut,
    array $maskedAuthorIds = [],
): array {
    $maxCountOut = DB::table('forum_topics')
        ->join('forum_topic_comments as ftc', 'ftc.id', '=', 'forum_topics.latest_comment_id')
        ->where('forum_topics.forum_id', $forumID)
        ->where('ftc.is_authorized', 1)
        ->where('forum_topics.required_permissions', '<=', $permissions)
        ->whereNull('forum_topics.deleted_at')
        ->whereNotIn('forum_topics.author_id', $maskedAuthorIds)
        ->count();

    $dataOut = DB::table('forum_topics as ft')
        ->selectRaw('
            f.title AS ForumTitle, ft.id AS ForumTopicID, ft.title AS TopicTitle, SUBSTR( ftc2.body, 1, 54 ) AS TopicPreview,
            ft.author_id AS AuthorID, ft.created_at AS ForumTopicPostedDate, ftc.id AS LatestCommentID,
            ftc.author_id AS LatestCommentAuthorID, ftc.created_at AS LatestCommentPostedDate, (COUNT(ftc2.id)-1) AS NumTopicReplies
        ')
        ->leftJoin('forum_topic_comments as ftc', 'ftc.id', '=', 'ft.latest_comment_id')
        ->leftJoin('forums as f', 'f.id', '=', 'ft.forum_id')
        ->leftJoin('forum_topic_comments as ftc2', function ($join): void {
            $join->on('ftc2.forum_topic_id', '=', 'ft.id')
                ->where('ftc2.is_authorized', '=', 1);
        })
        ->where('ft.forum_id', $forumID)
        ->where('ft.required_permissions', '<=', $permissions)
        ->whereNull('ft.deleted_at')
        ->whereNotIn('ft.author_id', $maskedAuthorIds)
        ->groupBy('ft.id', 'LatestCommentPostedDate')
        ->havingRaw('NumTopicReplies >= 0')
        ->orderByDesc('LatestCommentPostedDate')
        ->offset($offset)
        ->limit($count)
        ->get()
        ->map(fn ($row): array => (array) $row)
        ->values()
        ->toArray();

    if (!empty($maskedAuthorIds)) {
        $dataOut = replaceMaskedLatestComments($dataOut, $maskedAuthorIds);
    }

    return $dataOut;
}

/**
 * @param array<int, array<string, mixed>> $topicRows
 * @param array<int, int> $maskedAuthorIds
 * @return array<int, array<string, mixed>>
 */
function replaceMaskedLatestComments(array $topicRows, array $maskedAuthorIds): array
{
    $affectedTopicIds = [];
    foreach ($topicRows as $row) {
        if (in_array((int) $row['LatestCommentAuthorID'], $maskedAuthorIds, true)) {
            $affectedTopicIds[] = (int) $row['ForumTopicID'];
        }
    }

    if (empty($affectedTopicIds)) {
        return $topicRows;
    }

    $replacements = DB::table('forum_topic_comments as ftc')
        ->select(['ftc.forum_topic_id', 'ftc.id', 'ftc.author_id', 'ftc.created_at'])
        ->whereIn('ftc.forum_topic_id', $affectedTopicIds)
        ->where('ftc.is_authorized', 1)
        ->whereNotIn('ftc.author_id', $maskedAuthorIds)
        ->whereNull('ftc.deleted_at')
        ->whereNotExists(function ($query) use ($maskedAuthorIds): void {
            $query->selectRaw('1')
                ->from('forum_topic_comments as newer_ftc')
                ->whereColumn('newer_ftc.forum_topic_id', 'ftc.forum_topic_id')
                ->where('newer_ftc.is_authorized', 1)
                ->whereNotIn('newer_ftc.author_id', $maskedAuthorIds)
                ->whereNull('newer_ftc.deleted_at')
                ->where(function ($query): void {
                    $query->whereColumn('newer_ftc.created_at', '>', 'ftc.created_at')
                        ->orWhere(function ($query): void {
                            $query->whereColumn('newer_ftc.created_at', 'ftc.created_at')
                                ->whereColumn('newer_ftc.id', '>', 'ftc.id');
                        });
                });
        })
        ->get();

    $newestVisibleByTopic = [];
    foreach ($replacements as $replacement) {
        $newestVisibleByTopic[(int) $replacement->forum_topic_id] = $replacement;
    }

    foreach ($topicRows as $index => $row) {
        if (!in_array((int) $row['LatestCommentAuthorID'], $maskedAuthorIds, true)) {
            continue;
        }

        $replacement = $newestVisibleByTopic[(int) $row['ForumTopicID']] ?? null;

        $topicRows[$index]['LatestCommentID'] = $replacement ? (int) $replacement->id : null;
        $topicRows[$index]['LatestCommentAuthorID'] = $replacement ? (int) $replacement->author_id : null;
        $topicRows[$index]['LatestCommentPostedDate'] = $replacement?->created_at;
    }

    return $topicRows;
}

function getUnauthorisedForumLinks(): array
{
    $dataOut = DB::table('forum_topics as ft')
        ->selectRaw('
            ft.id AS ForumTopicID, ft.title AS TopicTitle, LEFT( ftc2.body, 60 ) AS TopicPreview,
            ft.author_id AS AuthorID, ft.created_at AS ForumTopicPostedDate, ftc.id AS LatestCommentID,
            ftc.author_id AS LatestCommentAuthorID, ftc.created_at AS LatestCommentPostedDate, (COUNT(ftc2.id)-1) AS NumTopicReplies
        ')
        ->leftJoin('forum_topic_comments as ftc', 'ftc.forum_topic_id', '=', 'ft.id')
        ->leftJoin('forums as f', 'f.id', '=', 'ft.forum_id')
        ->leftJoin('forum_topic_comments as ftc2', 'ftc2.forum_topic_id', '=', 'ft.id')
        ->where('ftc.is_authorized', 0)
        ->whereNull('ftc.deleted_at')
        ->whereNull('ft.deleted_at')
        ->groupBy('ft.id', 'LatestCommentPostedDate')
        ->orderByDesc('LatestCommentPostedDate')
        ->get()
        ->map(fn ($row): array => (array) $row)
        ->values()
        ->toArray();

    return $dataOut;
}

/**
 * @param User $user the user account that will appear as the topic author (may be a team account)
 * @param User|null $sentByUser the actual user creating the topic (when posting on behalf of a team account)
 */
function submitNewTopic(
    User $user,
    int $forumID,
    string $topicTitle,
    string $topicPayload,
    ?User $sentByUser = null,
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
    return submitTopicComment($user, $newTopic->id, $topicTitle, $topicPayload, $sentByUser);
}

function setLatestCommentInForumTopic(ForumTopic $forumTopic, int $commentID): bool
{
    // Update ForumTopic table
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

// TODO convert to action
/**
 * @param User $user the user account that will appear as the comment author (may be a team account)
 * @param User|null $sentByUser the actual user posting the comment (when posting on behalf of a team account)
 */
function submitTopicComment(
    User $user,
    int $topicId,
    ?string $topicTitle,
    string $commentPayload,
    ?User $sentByUser = null,
): ForumTopicComment {
    // Take any RA links and convert them to relevant shortcodes.
    // eg: "https://retroachievements.org/game/1" --> "[game=1]"
    $commentPayload = normalize_shortcodes($commentPayload);

    // Convert [user=$user->username] to [user=$user->id].
    $commentPayload = Shortcode::convertUserShortcodesToUseIds($commentPayload);

    // Convert [game={legacy_hub_id}] to [hub={game_set_id}].
    $commentPayload = Shortcode::convertLegacyGameHubShortcodesToHubShortcodes($commentPayload);

    // Convert [game=X?set=Y] to [game=backingGameId].
    $commentPayload = Shortcode::convertGameSetShortcodesToBackingGame($commentPayload);

    // if this exact message was just posted by this user, assume it's an
    // accidental double submission and ignore.
    $latestPost = $user->forumPosts()->latest('created_at')->first();
    if ($latestPost && $latestPost->forum_topic_id === $topicId
        && $latestPost->body === $commentPayload) {
        return $latestPost;
    }

    $topic = ForumTopic::findOrFail($topicId);

    // Comments by an effective author who satisfies the topic's role
    // whitelist are trusted & auto-authorized.
    $isAuthorized = ($user->ManuallyVerified ?? false)
        || (new ForumTopicCommentPolicy())->matchesCommentRoleAllowlist($user, $topic);

    $newComment = new ForumTopicComment([
        'forum_topic_id' => $topicId,
        'body' => $commentPayload,
        'author_id' => $user->id,
        'sent_by_id' => $sentByUser?->id,
        'is_authorized' => $isAuthorized,
    ]);
    $newComment->save();

    setLatestCommentInForumTopic($topic, $newComment->id);

    if ($isAuthorized) {
        // if user has any notifications pending for this post, assume they're no longer needed
        $notificationService = new SubscriptionNotificationService();
        $notificationService->resetNotification($user->id, SubscriptionSubjectType::ForumTopic, $topic->id);

        notifyUsersAboutForumActivity($topic, $user, $newComment);
    }

    return $newComment;
}

function notifyUsersAboutForumActivity(ForumTopic $topic, User $author, ForumTopicComment $newComment): void
{
    $subscriptionService = new SubscriptionService();
    $subscribers = $subscriptionService->getSegmentedSubscriberIds(SubscriptionSubjectType::ForumTopic, $topic->id, $topic->author_id);

    $notificationService = new SubscriptionNotificationService();
    $notificationService->queueNotifications($subscribers['implicitlySubscribedNotifyLater'], SubscriptionSubjectType::ForumTopic, $topic->id, $newComment->id, UserPreference::EmailOn_ForumReply);

    $emailTargets = $notificationService->getEmailTargets(
        array_merge($subscribers['explicitlySubscribed'], $subscribers['implicitlySubscribedNotifyNow']),
        UserPreference::EmailOn_ForumReply);

    if (!$emailTargets->isEmpty()) {
        $payload = nl2br(Shortcode::stripAndClamp($newComment->body, previewLength: 1000, preserveWhitespace: true));
        $urlTarget = route('forum-topic.show', ['topic' => $topic->id, 'comment' => $newComment->id]) . '#' . $newComment->id;

        foreach ($emailTargets as $subscriber) {
            sendActivityEmail(
                $subscriber,
                $topic->id,
                $author,
                CommentableType::Forum,
                $topic->title ?? '',
                $urlTarget,
                payload: $payload
            );
        }
    }
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
    if ($game->forum_topic_id > 0 && ForumTopic::find($game->forum_topic_id)->exists()) {
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

    $searchTitle = "$gameTitle $consoleName";

    $searchUrl = fn (string $query): string => 'https://www.google.com/search?' . http_build_query(['q' => $query]);

    $hashesURL = route('game.hashes.index', ['game' => $gameId]);
    $gameFAQsURL = $searchUrl("site:www.gamefaqs.com $searchTitle");
    $longplaysURL = $searchUrl("site:www.youtube.com longplay $searchTitle");
    $wikipediaURL = $searchUrl("site:en.wikipedia.org $searchTitle");

    $topicPayload = "Official Topic Post for discussion about [game=$gameId]\n" .
        "Created " . date("j M, Y H:i") . " by [user={$user->display_name}]\n\n" .
        "[b][url=$hashesURL]Supported Game Hashes[/url][/b]\n\n" .
        "[b]Resources:[/b]\n" .
        "[url=$gameFAQsURL]GameFAQs[/url]\n" .
        "[url=$longplaysURL]Longplay[/url]\n" .
        "[url=$wikipediaURL]Wikipedia[/url]\n";

    $forumTopicComment = submitNewTopic($user, $forumId, $topicTitle, $topicPayload);

    $game->forum_topic_id = $forumTopicComment->forumTopic->id;
    $game->save();

    return $forumTopicComment;
}

/**
 * @param array<int, int> $maskedAuthorIds authors the viewing user has blocked
 * @return Collection<int, non-empty-array>
 */
function getRecentForumPosts(
    int $offset,
    int $limit,
    int $numMessageChars,
    ?int $permissions = Permissions::Unregistered,
    ?int $fromAuthorId = null,
    array $maskedAuthorIds = [],
): Collection {
    $effectivePermissions = $permissions ?? Permissions::Unregistered;

    $latestComments = DB::table('forum_topic_comments as ftc')
        ->select('*')
        ->when(
            !empty($fromAuthorId),
            function ($query) use ($fromAuthorId, $permissions): void {
                $query->where('ftc.author_id', $fromAuthorId);
                if ($permissions < Permissions::Moderator) {
                    $query->where('ftc.is_authorized', 1);
                }
            },
            function ($query): void {
                $query->where('ftc.is_authorized', 1);
            }
        )
        ->whereNotIn('ftc.author_id', $maskedAuthorIds)
        ->orderByDesc('ftc.created_at')
        ->offset($offset)
        ->limit($limit + 20 + (empty($maskedAuthorIds) ? 0 : BuildThinRecentForumPostsDataAction::MASKED_AUTHOR_SCAN_BUFFER));

    $query = DB::query()
        ->fromSub($latestComments, 'LatestComments')
        ->selectRaw('
            LatestComments.created_at AS PostedAt,
            LatestComments.body AS Payload,
            ua.username as Author,
            ua.display_name as AuthorDisplayName,
            ft.id AS ForumTopicID,
            ft.title AS ForumTopicTitle,
            LatestComments.author_id AS author_id,
            LatestComments.id AS CommentID
        ')
        ->join('forum_topics as ft', 'ft.id', '=', 'LatestComments.forum_topic_id')
        ->leftJoin('forums as f', 'f.id', '=', 'ft.forum_id')
        ->leftJoin('users as ua', 'ua.id', '=', 'LatestComments.author_id')
        ->where('ft.required_permissions', '<=', $effectivePermissions)
        ->whereNull('ft.deleted_at')
        ->whereNotIn('ft.author_id', $maskedAuthorIds)
        ->orderByDesc('LatestComments.created_at')
        ->limit($limit);

    /** @var Collection<int, non-empty-array> */
    return $query->get()
        ->map(fn ($row): array => (array) $row)
        ->map(function ($post) use ($numMessageChars) {
            $post['ShortMsg'] = mb_substr($post['Payload'], 0, $numMessageChars);
            $post['IsTruncated'] = mb_strlen($post['Payload']) > $numMessageChars;

            return $post;
        });
}

function authorizeAllForumPostsForUser(User $user): bool
{
    $userUnauthorizedPosts = $user->forumPosts()
        ->unauthorized()
        ->with(['forumTopic' => function ($query) {
            $query->select('id', 'title', 'author_id');
        }])
        ->get();

    foreach ($userUnauthorizedPosts as $unauthorizedPost) {
        if ($unauthorizedPost->forumTopic) {
            notifyUsersAboutForumActivity(
                $unauthorizedPost->forumTopic,
                $user,
                $unauthorizedPost,
            );
        }
    }

    // Set all unauthorized forum posts by the user to authorized.
    $postIds = $user->forumPosts()->unauthorized()->pluck('id');
    $user->forumPosts()->unauthorized()->update([
        'is_authorized' => 1,
        'authorized_at' => now(),
    ]);

    // Re-index the newly authorized posts so they appear in search results.
    if ($postIds->isNotEmpty()) {
        ForumTopicComment::whereIn('id', $postIds)->get()->searchable();
    }

    return true;
}
