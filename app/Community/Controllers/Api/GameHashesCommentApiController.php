<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Data\StoreCommentData;
use App\Community\Requests\StoreCommentRequest;
use App\Http\Controller;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GameHashesCommentApiController extends Controller
{
    public function index(): void
    {
    }

    public function store(StoreCommentRequest $request): JsonResponse
    {
        $this->authorize('manage', [GameHash::class]);

        $data = StoreCommentData::fromRequest($request);

        /** @var User $user */
        $user = Auth::user();

        addArticleComment($user->username, $data->commentableType, $data->commentableId, $data->body);

        return response()->json(['success' => true]);
    }

    public function show(): void
    {
    }

    public function update(): void
    {
    }

    public function destroy(Game $game, Comment $comment): JsonResponse
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
