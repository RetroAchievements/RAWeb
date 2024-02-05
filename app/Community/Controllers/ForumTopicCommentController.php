<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\AddCommentAction;
use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Community\Requests\ForumTopicCommentRequest;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ForumTopicCommentController extends CommentController
{
    /**
     * There is no create form for creating a new comment.
     * comments have to be created for something -> use sub resource create route, e.g.
     * - user.comment.create (wall)
     * - achievement-ticket.comment.create
     * - forum-topic-comment.create
     */
    public function create(): void
    {
    }

    public function store(
        ForumTopicCommentRequest $request,
        ForumTopic $topic,
        AddCommentAction $addCommentAction,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('create', [ForumTopicComment::class, $topic]);

        // TODO replace with ForumTopicComment, not a commentable morph anymore
        // $comment = $addCommentAction->execute($request, $topic);

        // if (!$comment) {
        return back()->with('error', $this->resourceActionErrorMessage('topic.comment', 'create'));
        // }

        // return redirect($getUrlToCommentDestinationAction->execute($comment))
        //     ->with('success', $this->resourceActionSuccessMessage('comment', 'create'));
    }

    public function edit(ForumTopicComment $comment): View
    {
        $this->authorize('update', $comment);

        return view('forum-topic-comment.edit')
            ->with('comment', $comment);
    }

    protected function update(
        ForumTopicCommentRequest $request,
        ForumTopicComment $comment,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('update', $comment);

        $comment->fill($request->validated())->save();

        // TODO replace with similar logic for ForumTopicComment, not a commentable morph anymore
        return back();
        // return redirect($getUrlToCommentDestinationAction->execute($comment))
        //     ->with('success', $this->resourceActionSuccessMessage('comment', 'update'));
    }

    protected function destroy(ForumTopicComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $return = $comment->commentable->canonicalUrl;

        /*
         * don't touch
         */
        $comment->timestamps = false;

        $comment->delete();

        return redirect($return)
            ->with('success', $this->resourceActionSuccessMessage('comment', 'delete'));
    }
}
