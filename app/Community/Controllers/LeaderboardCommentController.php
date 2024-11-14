<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Concerns\IndexesComments;
use App\Community\Data\CommentData;
use App\Community\Data\LeaderboardCommentsPagePropsData;
use App\Data\PaginatedData;
use App\Models\Comment;
use App\Models\Leaderboard;
use App\Platform\Data\LeaderboardData;
use App\Policies\CommentPolicy;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class LeaderboardCommentController extends CommentController
{
    use IndexesComments;

    public function index(Leaderboard $leaderboard): InertiaResponse|RedirectResponse
    {
        return $this->handleCommentIndex(
            commentable: $leaderboard,
            policy: Comment::class,
            routeName: 'leaderboard.comment.index',
            routeParam: 'leaderboard',
            view: 'leaderboard/[leaderboard]/comments',
            createPropsData: function ($leaderboard, $paginatedComments, $isSubscribed, $user) {
                return new LeaderboardCommentsPagePropsData(
                    leaderboard: LeaderboardData::fromLeaderboard($leaderboard)->include('game.system', 'game.badgeUrl'),
                    paginatedComments: PaginatedData::fromLengthAwarePaginator(
                        $paginatedComments,
                        total: $paginatedComments->total(),
                        items: CommentData::fromCollection($paginatedComments->getCollection())
                    ),
                    isSubscribed: $isSubscribed,
                    canComment: (new CommentPolicy())->create($user, $leaderboard)
                );
            }
        );
    }

    /**
     * @see UserCommentController::create()
     */
    public function create(): void
    {
    }

    public function store(): void
    {
    }

    public function edit(): void
    {
    }

    protected function update(): void
    {
    }

    protected function destroy(): void
    {
    }

    public function destroyAll(): void
    {
    }
}
