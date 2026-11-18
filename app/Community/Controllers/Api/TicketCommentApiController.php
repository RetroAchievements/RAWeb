<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Enums\CommentableType;
use App\Community\Requests\StoreCommentRequest;
use App\Http\Controller;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Platform\Actions\ReopenTicketOnReporterReplyAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TicketCommentApiController extends Controller
{
    public function store(
        StoreCommentRequest $request,
        Ticket $ticket,
        ReopenTicketOnReporterReplyAction $reopenTicketOnReporterReplyAction,
    ): JsonResponse {
        $this->authorize('create', [TicketComment::class, $ticket]);

        /** @var User $user */
        $user = Auth::user();

        $wasCommentAdded = addArticleComment($user->username, CommentableType::Ticket, $ticket->id, $request->validated('body'));

        if ($wasCommentAdded) {
            $reopenTicketOnReporterReplyAction->execute($ticket, $user);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Ticket $ticket, TicketComment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        /*
         * don't touch
         */
        $comment->timestamps = false;
        $comment->delete();

        return response()->json(['success' => true]);
    }
}
