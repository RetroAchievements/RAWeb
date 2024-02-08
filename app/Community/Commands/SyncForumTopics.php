<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\ForumTopic;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SyncForumTopics extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:forum-topics {forumTopicId?} {--f|full} {--p|no-post}';

    protected $description = 'Sync forum topics';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('forum_topics');
    }

    public function query(): Builder
    {
        if ($this->hasArgument('forumTopicId') && $this->argument('forumTopicId')) {
            $this->incremental = false;
        }

        $query = DB::table(function ($query) {
            $query->from('ForumTopic')
                ->select('ForumTopic.*', 'UserAccounts.ID as ExistingAuthorID')
                ->addSelect([
                    // fetch first comment to be used as actual data
                    'FirstForumCommentID' => function ($query) {
                        /* @var Builder $query */
                        $query->select('ForumTopicComment.ID')
                            ->from('ForumTopicComment')
                            ->whereColumn('ForumTopicComment.ForumTopicID', 'ForumTopic.ID')
                            ->orderBy('ForumTopicComment.DateCreated', 'asc')
                            ->limit(1);
                    },
                    'FirstForumCommentDateCreated' => function ($query) {
                        /* @var Builder $query */
                        $query->selectRaw('ForumTopicComment.DateCreated')
                            ->from('ForumTopicComment')
                            ->whereColumn('ForumTopicComment.ForumTopicID', 'ForumTopic.ID')
                            ->orderBy('ForumTopicComment.DateCreated', 'asc')
                            ->limit(1);
                    },
                    'FirstForumCommentDateUpdated' => function ($query) {
                        /* @var Builder $query */
                        $query->selectRaw('ForumTopicComment.DateModified')
                            ->from('ForumTopicComment')
                            ->whereColumn('ForumTopicComment.ForumTopicID', 'ForumTopic.ID')
                            ->orderBy('ForumTopicComment.DateCreated', 'asc')
                            ->limit(1);
                    },
                    'FirstForumCommentPayload' => function ($query) {
                        /* @var Builder $query */
                        $query->selectRaw('ForumTopicComment.Payload')
                            ->from('ForumTopicComment')
                            ->whereColumn('ForumTopicComment.ForumTopicID', 'ForumTopic.ID')
                            ->orderBy('ForumTopicComment.DateCreated', 'asc')
                            ->limit(1);
                    },
                    // reference column
                    'LastForumCommentDateUpdated' => function ($query) {
                        /* @var Builder $query */
                        $query->selectRaw('COALESCE(ForumTopicComment.DateModified, ForumTopicComment.DateCreated) as ForumTopicUpdated')
                            ->from('ForumTopicComment')
                            ->whereColumn('ForumTopicComment.ForumTopicID', 'ForumTopic.ID')
                            ->orderBy('ForumTopicUpdated', 'desc')
                            ->limit(1);
                    },
                ])
                // left join users, may be null/deleted
                ->leftJoin('UserAccounts', 'UserAccounts.ID', '=', 'ForumTopic.AuthorID')
                // by inner joining users we can make sure only comments with a valid user attached are imported
                // only select game data though
                ->join('Forum', 'Forum.ID', '=', 'ForumTopic.ForumID');

            if ($this->hasArgument('forumTopicId') && $this->argument('forumTopicId')) {
                $query->where('ForumTopic.ID', $this->argument('forumTopicId'));
            }
        })
            // if it no first comment -> not a valid forum topic worth keeping around as there's no content then
            ->whereNotNull('FirstForumCommentID');

        return $query;
    }

    protected function postProcessEntity(object $origin, object $transformed): void
    {
        // dump($transformed);
        // dd($origin);

        /**
         * sync forum comments here as topics are already properly filtered - makes it a bit easier
         * resyncing existing comments should not be an issue as the origin id has to be unique
         */
        $commentableType = resource_type(ForumTopic::class);

        $comments = DB::table('ForumTopicComment')
            ->select('ForumTopicComment.*', 'UserAccounts.ID as ExistingAuthorID')
            ->where('ForumTopicID', $transformed->id)
            ->orderBy('DateCreated')
            /*
             * by inner joining users we can make sure only comments with a valid user attached are imported
             */
            ->leftJoin('UserAccounts', 'UserAccounts.ID', '=', 'ForumTopicComment.AuthorID')
            ->get();

        /**
         * first comment -> topic body
         */
        $firstComment = $comments->shift();

        foreach ($comments as $comment) {
            $this->connection->table('comments')
                ->upsert([
                    'commentable_id' => $transformed->id,
                    'commentable_type' => $commentableType,
                    'origin_id' => $commentableType . '_' . $comment->ID,
                    'user_id' => $comment->ExistingAuthorID,
                    /*
                     * TODO: save again to make the saving hook boot up to replace user ids?
                     */
                    'body' => $this->fixEncoding(trim($comment->Payload)),
                    'created_at' => $comment->DateCreated,
                    'updated_at' => $comment->DateModified,
                ], 'origin_id');
        }
    }
}
