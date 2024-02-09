<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\Comment;
use Illuminate\Support\Facades\Route;

class GetUrlToCommentDestinationAction
{
    public function execute(Comment $comment): string
    {
        /*
         * commentable might not yet have been loaded e.g. after the comment has been created
         */
        $comment->loadMissing('commentable');

        $query = '?' . http_build_query(array_filter(['highlight' => 'comment-' . $comment->id])) . '#comments';

        if (Route::has(resource_type($comment->commentable) . '.comment.index')) {
            return route(resource_type($comment->commentable) . '.comment.index', $comment->commentable) . $query;
        }

        return $comment->commentable->canonicalUrl . $query;
    }
}
