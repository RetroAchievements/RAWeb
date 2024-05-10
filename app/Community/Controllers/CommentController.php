<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Http\Controller;
use App\Models\Comment;
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

    public function indexForLeaderboard(Request $request, Leaderboard $leaderboard): View
    {
        return view('pages.leaderboard.[leaderboard].comments', [
            'leaderboard' => $leaderboard,
        ]);
    }
}
