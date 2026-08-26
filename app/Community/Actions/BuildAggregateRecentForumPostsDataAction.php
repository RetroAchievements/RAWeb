<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Data\ForumTopicData;
use App\Data\PaginatedData;
use App\Enums\Permissions;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Support\Shortcode\Shortcode;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Results from this action also include aggregate topic
 * posts counts from the last day and week.
 */
class BuildAggregateRecentForumPostsDataAction
{
    /** Maximum comments to inspect for the masked recent-topics paginator. */
    private const int MASKED_SCAN_WINDOW = 20000;

    /**
     * @param array<int, int> $maskedAuthorIds authors the viewer has blocked
     */
    public function execute(
        int $permissions = Permissions::Unregistered,
        ?int $page = null,
        int $limit = 25,
        ?string $paginationPath = null,
        array $paginationQuery = [],
        array $maskedAuthorIds = [],
    ): PaginatedData|array {
        $currentPage = $page ?? 1;

        if (empty($maskedAuthorIds)) {
            $topics = $this->getRecentForumTopics($currentPage, $permissions, $limit);
            $total = $this->getTotalRecentForumTopics($permissions);
        } else {
            $maskedTopics = $this->getMaskedRecentForumTopics(
                $currentPage,
                $permissions,
                $limit,
                $maskedAuthorIds,
            );
            $topics = $maskedTopics['topics'];
            $total = $maskedTopics['total'];
        }

        $shortcodeIds = [];
        foreach ($topics as $topic) {
            $postShortcodeIds = Shortcode::extractShortcodeIds($topic['ShortMsg']);
            foreach ($postShortcodeIds as $key => $ids) {
                $shortcodeIds[$key] = array_merge($shortcodeIds[$key] ?? [], $ids);
            }
        }
        $shortcodeRecords = Shortcode::fetchRecords($shortcodeIds);

        $transformedTopics = array_map(
            fn ($topic) => ForumTopicData::fromRecentlyActiveTopic($topic, $shortcodeRecords)->include(
                'commentCount24h',
                'oldestComment24hId',
                'commentCount7d',
                'oldestComment7dId',
            ),
            $topics
        );

        // Create a paginated response.
        $paginator = new LengthAwarePaginator(
            items: $transformedTopics,
            total: $total,
            perPage: $limit,
            currentPage: $currentPage,
            options: [
                'path' => $paginationPath,
                'query' => $paginationQuery,
            ],
        );

        return PaginatedData::fromLengthAwarePaginator($paginator);
    }

    private function getTotalRecentForumTopics(int $permissions = Permissions::Unregistered): int
    {
        return ForumTopic::where('required_permissions', '<=', $permissions)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query
                    ->whereNotNull('latest_comment_id')
                    ->orWhereIn('id', function ($subQuery) {
                        $subQuery
                            ->select('forum_topic_id')
                            ->distinct()
                            ->from('forum_topic_comments')
                            ->where('is_authorized', 1);
                    });
            })
            ->count();
    }

    /**
     * @param array<int, int> $maskedAuthorIds
     * @return array{topics: array<int, array<string, mixed>>, total: int}
     */
    private function getMaskedRecentForumTopics(
        int $page,
        int $permissions,
        int $count,
        array $maskedAuthorIds,
    ): array {
        $offset = ($page - 1) * $count;

        $recentVisibleComments = ForumTopicComment::query()
            ->select(['forum_topic_comments.id', 'forum_topic_comments.forum_topic_id'])
            ->join('forum_topics', 'forum_topics.id', '=', 'forum_topic_comments.forum_topic_id')
            ->where('forum_topic_comments.is_authorized', 1)
            ->whereNotIn('forum_topic_comments.author_id', $maskedAuthorIds)
            ->whereNotIn('forum_topics.author_id', $maskedAuthorIds)
            ->where('forum_topics.required_permissions', '<=', $permissions)
            ->whereNull('forum_topics.deleted_at')
            ->orderByDesc('forum_topic_comments.created_at')
            ->orderByDesc('forum_topic_comments.id')
            ->limit(self::MASKED_SCAN_WINDOW)
            ->toBase()
            ->get();

        $latestVisibleCommentIdByTopic = [];
        foreach ($recentVisibleComments as $comment) {
            $latestVisibleCommentIdByTopic[(int) $comment->forum_topic_id] ??= (int) $comment->id;
        }

        $pagedCommentIds = array_slice(array_values($latestVisibleCommentIdByTopic), $offset, $count);

        return [
            'topics' => empty($pagedCommentIds) ? [] : $this->hydrateTopicsFromComments($pagedCommentIds, $maskedAuthorIds),
            'total' => count($latestVisibleCommentIdByTopic),
        ];
    }

    /**
     * @param array<int, int> $commentIds
     * @param array<int, int> $maskedAuthorIds
     */
    private function hydrateTopicsFromComments(array $commentIds, array $maskedAuthorIds): array
    {
        $oneDayAgo = now()->subDay()->toDateTimeString();
        $sevenDaysAgo = now()->subDays(7)->toDateTimeString();

        $countsOneDay = ForumTopicComment::query()
            ->selectRaw('forum_topic_id, MIN(id) AS CommentID, COUNT(*) AS Count')
            ->where('is_authorized', 1)
            ->whereNotIn('author_id', $maskedAuthorIds)
            ->where('created_at', '>=', $oneDayAgo)
            ->groupBy('forum_topic_id');

        $countsSevenDays = ForumTopicComment::query()
            ->selectRaw('forum_topic_id, MIN(id) AS CommentID, COUNT(*) AS Count')
            ->where('is_authorized', 1)
            ->whereNotIn('author_id', $maskedAuthorIds)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->groupBy('forum_topic_id');

        $results = ForumTopicComment::query()
            ->select([
                'forum_topics.id as ForumTopicID',
                'forum_topics.title as ForumTopicTitle',
                'f.id as ForumID',
                'f.title as ForumTitle',
                'forum_topic_comments.id as CommentID',
                'forum_topic_comments.created_at as PostedAt',
                'forum_topic_comments.author_id',
                'ua.username as Author',
                'ua.display_name as AuthorDisplayName',
                'ua.avatar_updated_at',
                'forum_topic_comments.body as ShortMsg',
                'd1.CommentID as CommentID_1d',
                'd1.Count as Count_1d',
                'd7.CommentID as CommentID_7d',
                'd7.Count as Count_7d',
            ])
            ->selectRaw('0 AS IsTruncated')
            ->join('forum_topics', 'forum_topics.id', '=', 'forum_topic_comments.forum_topic_id')
            ->join('forums as f', 'f.id', '=', 'forum_topics.forum_id')
            ->leftJoin('users as ua', 'ua.id', '=', 'forum_topic_comments.author_id')
            ->leftJoinSub($countsOneDay, 'd1', 'd1.forum_topic_id', '=', 'forum_topics.id')
            ->leftJoinSub($countsSevenDays, 'd7', 'd7.forum_topic_id', '=', 'forum_topics.id')
            ->whereIn('forum_topic_comments.id', $commentIds)
            ->get()
            ->map(fn (ForumTopicComment $comment): array => $comment->getAttributes())
            ->keyBy(fn (array $topic): int => (int) $topic['CommentID']);

        return array_values(array_filter(array_map(
            fn (int $commentId): ?array => $results->get($commentId),
            $commentIds,
        )));
    }

    private function getRecentForumTopics(int $page = 1, int $permissions = Permissions::Unregistered, int $count = 25): array
    {
        $offset = ($page - 1) * $count;

        // This is a very tough query to optimize. At the very least, we can have
        // it run through the DB facade so the code doesn't crash in tests.
        // Heredoc syntax with <<<SQL gives us proper SQL syntax highlighting.
        $results = DB::select(<<<SQL
            SELECT 
                ft.id AS ForumTopicID, 
                ft.title AS ForumTopicTitle,
                f.id AS ForumID, 
                f.title AS ForumTitle,
                lftc.id AS CommentID, 
                lftc.created_at AS PostedAt, 
                lftc.author_id,
                ua.username AS Author, 
                ua.display_name AS AuthorDisplayName,
                ua.avatar_updated_at,
                lftc.body AS ShortMsg,
                0 AS IsTruncated,
                d1.CommentID AS CommentID_1d,
                d1.Count AS Count_1d,
                d7.CommentID AS CommentID_7d,
                d7.Count AS Count_7d
            FROM (
                SELECT ft.id, ft.title, ft.forum_id, ft.latest_comment_id
                FROM forum_topics ft
                FORCE INDEX (idx_permissions_deleted_latest)
                WHERE ft.required_permissions <= ? AND ft.deleted_at IS NULL
                ORDER BY ft.latest_comment_id DESC
            ) AS ft
            INNER JOIN forums AS f ON f.id = ft.forum_id
            INNER JOIN forum_topic_comments AS lftc ON lftc.id = ft.latest_comment_id AND lftc.is_authorized = 1
            LEFT JOIN users AS ua ON ua.id = lftc.author_id
            LEFT JOIN (
                SELECT forum_topic_id, MIN(id) AS CommentID, COUNT(*) AS Count
                FROM forum_topic_comments
                WHERE is_authorized = 1 AND created_at >= NOW() - INTERVAL 1 DAY
                GROUP BY forum_topic_id
            ) AS d1 ON d1.forum_topic_id = ft.id
            LEFT JOIN (
                SELECT forum_topic_id, MIN(id) AS CommentID, COUNT(*) AS Count
                FROM forum_topic_comments
                WHERE is_authorized = 1 AND created_at >= NOW() - INTERVAL 7 DAY
                GROUP BY forum_topic_id
            ) AS d7 ON d7.forum_topic_id = ft.id
            ORDER BY lftc.created_at DESC
            LIMIT ?, ?
        SQL, [$permissions, $offset, $count]);

        return array_map(fn ($result) => (array) $result, $results);
    }
}
