<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Data\StoreCommentData;
use App\Community\Requests\StoreCommentRequest;
use App\Http\Controller;
use App\Models\Event;
use App\Models\EventComment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EventCommentApiController extends Controller
{
    public function store(StoreCommentRequest $request, Event $event): JsonResponse
    {
        $this->authorize('create', [EventComment::class, $event]);

        $data = StoreCommentData::fromRequest($request);

        /** @var User $user */
        $user = Auth::user();

        addArticleComment($user->username, $data->commentableType, $data->commentableId, $data->body);

        return response()->json(['success' => true]);
    }

    public function destroy(Event $event, EventComment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->timestamps = false;
        $comment->delete();

        return response()->json(['success' => true]);
    }
}
