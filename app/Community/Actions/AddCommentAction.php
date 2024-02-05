<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Contracts\HasComments;
use App\Community\Requests\CommentRequest;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;

class AddCommentAction
{
    public function execute(CommentRequest $request, HasComments $commentable): Model|false
    {
        $comment = new Comment($request->validated());

        $comment->user_id = $request->user()->id;

        return $commentable->comments()->save($comment);
    }
}
