<?php

namespace App\Community\Controllers\Api;

use App\Community\Data\StoreCommentData;
use App\Community\Requests\StoreCommentRequest;
use App\Http\Controller;
use App\Models\User;
use App\Models\UserComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserModerationCommentApiController extends Controller
{
    public function index(): void
    {
    }

    public function store(StoreCommentRequest $request, User $user): JsonResponse
    {
        $this->authorize('manage', $user);

        $data = StoreCommentData::fromRequest($request);

        /** @var User $me */
        $me = Auth::user();

        addArticleComment($me->username, $data->commentableType, $data->commentableId, $data->body);

        return response()->json(['success' => true]);
    }

    public function show(): void
    {
    }

    public function update(): void
    {
    }

    public function destroy(User $user, UserComment $comment): JsonResponse
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
