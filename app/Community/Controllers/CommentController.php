<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Leaderboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function show(
        Comment $comment,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('view', $comment);

        abort_if($comment->commentable === null, 404);

        return redirect($getUrlToCommentDestinationAction->execute($comment));
    }

    public function indexForGame(Request $request, Game $game): View
    {
        return view('pages.game.[game].comments', [
            'game' => $game,
        ]);
    }

    public function indexForGameModifications(Request $request, Game $game): View
    {
        $this->authorize('viewModifications', $game);

        return view('pages.game.[game].modification-comments', [
            'game' => $game,
        ]);
    }

    public function indexForAchievement(Request $request, Achievement $achievement): View
    {
        return view('pages.achievement.[achievement].comments', [
            'achievement' => $achievement,
        ]);
    }

    public function indexForLeaderboard(Request $request, Leaderboard $leaderboard): View
    {
        return view('pages.leaderboard.[leaderboard].comments', [
            'leaderboard' => $leaderboard,
        ]);
    }
}
