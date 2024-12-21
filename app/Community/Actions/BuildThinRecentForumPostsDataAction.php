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
        int $limit = 4,
        int $numMessageChars = 260,
        ?int $permissions = Permissions::Unregistered,
        ?int $fromAuthorId = null,
    ): Collection {
        $userClause = $this->buildUserClause($fromAuthorId, $permissions);

        $subQuery = DB::table('forum_topic_comments as ftc')
            ->select('*')
            ->whereRaw($userClause)
            ->orderBy('ftc.created_at', 'desc')
            ->limit($limit + 20); // cater for spam messages

        $latestComments = DB::table(DB::raw("({$subQuery->toSql()}) as LatestComments"))
            ->mergeBindings($subQuery)
            ->join('forum_topics as ft', 'ft.id', '=', 'LatestComments.forum_topic_id')
            ->leftJoin('forums as f', 'f.id', '=', 'ft.forum_id')
            ->leftJoin('UserAccounts as ua', 'ua.ID', '=', 'LatestComments.author_id')
            ->select([
                'LatestComments.created_at as PostedAt',
                'LatestComments.body as Payload',
                'ua.User as Author',
                'ua.display_name as AuthorDisplayName',
                'ua.RAPoints',
                'ua.Motto',
                'ft.id as ForumTopicID',
                'ft.title as ForumTopicTitle',
                'LatestComments.author_id as author_id',
                'LatestComments.ID as CommentID',
            ])
            ->where('ft.required_permissions', '<=', $permissions ?? Permissions::Unregistered)
            ->whereNull('ft.deleted_at')
            ->orderBy('LatestComments.created_at', 'desc')
            ->limit($limit)
            ->get();

        return $latestComments->map(function ($post) use ($numMessageChars) {
            $postArray = (array) $post;
            $postArray['ShortMsg'] = mb_substr($postArray['Payload'], 0, $numMessageChars);

            return ForumTopicData::fromHomePageQuery($postArray)->include('latestComment');
        });
    }

    private function buildUserClause(?int $fromAuthorId, ?int $permissions): string
    {
        if (empty($fromAuthorId)) {
            return 'ftc.is_authorized = 1';
        }

        $clause = 'ftc.author_id = ?';
        if ($permissions < Permissions::Moderator) {
            $clause .= ' AND ftc.is_authorized = 1';
        }

        return $clause;
    }
}
