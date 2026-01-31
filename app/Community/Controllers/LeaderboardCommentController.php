<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildCommentPageAction;
use App\Models\Comment;
use App\Models\Leaderboard;
use App\Platform\Data\LeaderboardData;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class LeaderboardCommentController extends CommentController
{
    public function index(Leaderboard $leaderboard, BuildCommentPageAction $action): InertiaResponse|RedirectResponse
    {
        $this->authorize('viewAny', [Comment::class, $leaderboard]);

        return $action->execute(
            $leaderboard,
            view: 'leaderboard/[leaderboard]/comments',
            policyClass: Comment::class,
            entityKey: 'leaderboard',
            createEntityData: fn ($lb) => LeaderboardData::fromLeaderboard($lb)->include('game.system', 'game.badgeUrl'),
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
