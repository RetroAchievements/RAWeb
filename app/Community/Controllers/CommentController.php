<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Community\Enums\ArticleType;
use App\Http\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    private const SUPPORTED_COMMENTABLE_TYPES = [
        ArticleType::Game,
        ArticleType::Achievement,
        ArticleType::User,
        ArticleType::Leaderboard,
    ];

    public function show(
        Comment $comment,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction,
    ): RedirectResponse {
        abort_if($comment->trashed(), 404);
        abort_unless(in_array($comment->ArticleType, self::SUPPORTED_COMMENTABLE_TYPES, true), 404);

        return redirect($getUrlToCommentDestinationAction->execute($comment));
    }
}
