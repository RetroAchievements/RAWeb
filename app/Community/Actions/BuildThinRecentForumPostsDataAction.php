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

        $subQuery = DB::table('ForumTopicComment as ftc')
            ->select('*')
            ->whereRaw($userClause)
            ->orderBy('ftc.DateCreated', 'desc')
            ->limit($limit + 20); // cater for spam messages

        $latestComments = DB::table(DB::raw("({$subQuery->toSql()}) as LatestComments"))
            ->mergeBindings($subQuery)
            ->join('ForumTopic as ft', 'ft.ID', '=', 'LatestComments.ForumTopicID')
            ->leftJoin('Forum as f', 'f.ID', '=', 'ft.ForumID')
            ->leftJoin('UserAccounts as ua', 'ua.ID', '=', 'LatestComments.author_id')
            ->select([
                'LatestComments.DateCreated as PostedAt',
                'LatestComments.Payload',
                'ua.User as Author',
                'ua.display_name as AuthorDisplayName',
                'ua.RAPoints',
                'ua.Motto',
                'ft.ID as ForumTopicID',
                'ft.Title as ForumTopicTitle',
                'LatestComments.author_id as author_id',
                'LatestComments.ID as CommentID',
            ])
            ->where('ft.RequiredPermissions', '<=', $permissions ?? Permissions::Unregistered)
            ->whereNull('ft.deleted_at')
            ->orderBy('LatestComments.DateCreated', 'desc')
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
            return 'ftc.Authorised = 1';
        }

        $clause = 'ftc.author_id = ?';
        if ($permissions < Permissions::Moderator) {
            $clause .= ' AND ftc.Authorised = 1';
        }

        return $clause;
    }
}
