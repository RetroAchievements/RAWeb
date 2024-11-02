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
        return ForumTopic::where("RequiredPermissions", "<=", $permissions)
            ->whereNull("deleted_at")
            ->where(function ($query) {
                $query
                    ->whereNotNull("LatestCommentID")
                    ->orWhereIn("ID", function ($subQuery) {
                        $subQuery
                            ->select("ForumTopicID")
                            ->distinct()
                            ->from("ForumTopicComment")
                            ->where("Authorised", 1);
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
                ft.ID AS ForumTopicID, 
                ft.Title AS ForumTopicTitle,
                f.ID AS ForumID, 
                f.Title AS ForumTitle,
                lftc.ID AS CommentID, 
                lftc.DateCreated AS PostedAt, 
                lftc.author_id,
                ua.User AS Author, 
                ua.display_name AS AuthorDisplayName,
                LEFT(lftc.Payload, 260) AS ShortMsg,
                LENGTH(lftc.Payload) > 260 AS IsTruncated,
                d1.CommentID AS CommentID_1d,
                d1.Count AS Count_1d,
                d7.CommentID AS CommentID_7d,
                d7.Count AS Count_7d
            FROM (
                SELECT ft.ID, ft.Title, ft.ForumID, ft.LatestCommentID
                FROM ForumTopic ft
                FORCE INDEX (idx_permissions_deleted_latest)
                WHERE ft.RequiredPermissions <= ? AND ft.deleted_at IS NULL
                ORDER BY ft.LatestCommentID DESC
            ) AS ft
            INNER JOIN Forum AS f ON f.ID = ft.ForumID
            INNER JOIN ForumTopicComment AS lftc ON lftc.ID = ft.LatestCommentID AND lftc.Authorised = 1
            LEFT JOIN UserAccounts AS ua ON ua.ID = lftc.author_id
            LEFT JOIN (
                SELECT ForumTopicId, MIN(ID) AS CommentID, COUNT(*) AS Count
                FROM ForumTopicComment
                WHERE Authorised = 1 AND DateCreated >= NOW() - INTERVAL 1 DAY
                GROUP BY ForumTopicId
            ) AS d1 ON d1.ForumTopicId = ft.ID
            LEFT JOIN (
                SELECT ForumTopicId, MIN(ID) AS CommentID, COUNT(*) AS Count
                FROM ForumTopicComment
                WHERE Authorised = 1 AND DateCreated >= NOW() - INTERVAL 7 DAY
                GROUP BY ForumTopicId
            ) AS d7 ON d7.ForumTopicId = ft.ID
            ORDER BY lftc.DateCreated DESC
            LIMIT ?, ?
        SQL, [$permissions, $offset, $count]);

        return array_map(fn ($result) => (array) $result, $results);
    }
}
