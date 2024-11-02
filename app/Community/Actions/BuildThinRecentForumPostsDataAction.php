<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Data\ForumTopicData;
use App\Enums\Permissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildThinRecentForumPostsDataAction
{
    /**
     * @return Collection<int, ForumTopicData>
     */
    public function execute(
        int $offset = 0,
        int $limit = 4,
        int $numMessageChars = 260,
        ?int $permissions = Permissions::Unregistered,
        ?int $fromAuthorId = null,
    ): Collection {
        $userClause = $this->buildUserClause($fromAuthorId, $permissions);

        /**
         * This is a very frequently-called and well-optimized query.
         * Converting it directly to Eloquent results in a nearly 100x
         * performance slowdown. Because the query is called quite often, it's
         * ideal to keep the perf, even if we need to reach for native SQL.
         * Therefore, we'll run it through the DB facade so the code doesn't
         * crash in tests. Heredoc syntax with <<<SQL gives us proper SQL
         * syntax highlighting.
         */
        $results = DB::select(<<<SQL
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
                WHERE {$userClause}
                ORDER BY ftc.DateCreated DESC
                LIMIT ?, ?
            ) AS LatestComments
            INNER JOIN ForumTopic AS ft ON ft.ID = LatestComments.ForumTopicID
            LEFT JOIN Forum AS f ON f.ID = ft.ForumID
            LEFT JOIN UserAccounts AS ua ON ua.ID = LatestComments.author_id
            WHERE ft.RequiredPermissions <= ? AND ft.deleted_at IS NULL
            ORDER BY LatestComments.DateCreated DESC
            LIMIT 0, ?
        SQL, [
            $offset,
            $limit + 20, // cater for spam messages
            $permissions ?? Permissions::Unregistered,
            $limit,
        ]);

        return collect($results)
            ->map(function ($post) use ($numMessageChars) {
                $postArray = (array) $post;
                $postArray['ShortMsg'] = mb_substr($postArray['Payload'], 0, $numMessageChars);

                return ForumTopicData::fromHomePageQuery($postArray)->include('latestComment');
            });
    }

    private function buildUserClause(?int $fromAuthorId, ?int $permissions): string
    {
        if (empty($fromAuthorId)) {
            return 'ftc.Authorised = 1';
        }

        $clause = 'ftc.author_id = ?';
        if ($permissions < Permissions::Moderator) {
            $clause .= ' AND ftc.Authorised = 1';
        }

        return $clause;
    }
}
