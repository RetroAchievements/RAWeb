<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\ForumTopicComment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApproveUserForumPostsAction
{
    public function execute(User $user): void
    {
        $user->ManuallyVerified = 1;
        $user->forum_verified_at = Carbon::now();
        $user->saveQuietly();

        $userUnauthorizedPosts = $user->forumPosts()
            ->unauthorized()
            ->with(['forumTopic' => function ($query) {
                $query->select('id', 'title', 'author_id');
            }])
            ->get();

        $latestTopicCommentIds = ForumTopicComment::query()
            ->whereIn('forum_topic_id', $userUnauthorizedPosts->pluck('forum_topic_id'))
            ->authorized()
            ->select('forum_topic_id', DB::raw('MAX(id) AS max_id'))
            ->groupBy('forum_topic_id')
            ->pluck('max_id', 'forum_topic_id')
            ->toArray();

        foreach ($userUnauthorizedPosts as $unauthorizedPost) {
            if ($unauthorizedPost->forumTopic) {
                // if the forum_topic_id is not in the latestTopicCommentIds dictionary, then the only posts are unauthorized.
                $hasNewerAuthorizedPost = ($latestTopicCommentIds[$unauthorizedPost->forum_topic_id] ?? 0) > $unauthorizedPost->id;

                notifyUsersAboutForumActivity(
                    $unauthorizedPost->forumTopic,
                    $user,
                    $unauthorizedPost,
                    canQueueNotification: !$hasNewerAuthorizedPost,
                );
            }
        }

        // Set all unauthorized forum posts by the user to authorized.
        $postIds = $userUnauthorizedPosts->pluck('id');
        $user->forumPosts()->unauthorized()->update([
            'is_authorized' => true,
            'authorized_at' => Carbon::now(),
        ]);

        // Re-index the newly authorized posts so they appear in search results.
        if ($postIds->isNotEmpty()) {
            ForumTopicComment::whereIn('id', $postIds)->get()->searchable();
        }
    }
}
