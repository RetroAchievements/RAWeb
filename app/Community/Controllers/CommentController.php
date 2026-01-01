<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Http\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function show(
        Comment $comment,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction,
    ): RedirectResponse {
        abort_if($comment->trashed(), 404);
        abort_unless($comment->commentable_type->supportsCommentRedirect(), 404);

        return redirect($getUrlToCommentDestinationAction->execute($comment));
    }
}
