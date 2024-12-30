<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Data\ForumTopicData;
use App\Data\PaginatedData;
use App\Enums\Permissions;
use App\Models\ForumTopic;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Results from this action also include aggregate topic
 * posts counts from the last day and week.
 */
class BuildAggregateRecentForumPostsDataAction
{
    public function execute(
        int $permissions = Permissions::Unregistered,
        ?int $page = null,
        int $limit = 25,
        ?string $paginationPath = null,
        array $paginationQuery = [],
    ): PaginatedData|array {
        $topics = $this->getRecentForumTopics($page, $permissions, $limit);

        $transformedTopics = array_map(
            fn ($topic) => ForumTopicData::fromRecentlyActiveTopic($topic)->include(
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
            total: $this->getTotalRecentForumTopics($permissions),
            perPage: $limit,
            currentPage: $page,
            options: [
                'path' => $paginationPath,
                'query' => $paginationQuery,
            ],
        );

        return PaginatedData::fromLengthAwarePaginator($paginator);
    }

    private function getTotalRecentForumTopics(int $permissions = Permissions::Unregistered): int
    {
        return ForumTopic::where("required_permissions", "<=", $permissions)
            ->whereNull("deleted_at")
            ->where(function ($query) {
                $query
                    ->whereNotNull("latest_comment_id")
                    ->orWhereIn("id", function ($subQuery) {
                        $subQuery
                            ->select("forum_topic_id")
                            ->distinct()
                            ->from("forum_topic_comments")
                            ->where("is_authorized", 1);
                    });
            })
            ->count();
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
                ua.User AS Author, 
                ua.display_name AS AuthorDisplayName,
                LEFT(lftc.body, 260) AS ShortMsg,
                LENGTH(lftc.body) > 260 AS IsTruncated,
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
            LEFT JOIN UserAccounts AS ua ON ua.ID = lftc.author_id
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
