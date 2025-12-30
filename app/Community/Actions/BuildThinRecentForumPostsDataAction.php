<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Data\ForumTopicData;
use App\Enums\Permissions;
use App\Support\Shortcode\Shortcode;
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
            ->leftJoin('users as ua', 'ua.id', '=', 'LatestComments.author_id')
            ->select([
                'LatestComments.created_at as PostedAt',
                'LatestComments.body as Payload',
                'ua.username as Author',
                'ua.display_name as AuthorDisplayName',
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

        $shortcodeIds = [];
        foreach ($latestComments as $post) {
            $postShortcodeIds = Shortcode::extractShortcodeIds($post->Payload);
            foreach ($postShortcodeIds as $key => $ids) {
                $shortcodeIds[$key] = array_merge($shortcodeIds[$key] ?? [], $ids);
            }
        }
        $shortcodeRecords = Shortcode::fetchRecords($shortcodeIds);

        return $latestComments->map(function ($post) use ($numMessageChars, $shortcodeRecords) {
            $postArray = (array) $post;

            return ForumTopicData::fromHomePageQuery($postArray, $numMessageChars, $shortcodeRecords)->include('latestComment');
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
