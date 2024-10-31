<?php

namespace App\Community\Controllers\Api;

use App\Community\Data\StoreCommentData;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Requests\StoreCommentRequest;
use App\Http\Controller;
use App\Models\Game;
use App\Models\GameComment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GameCommentApiController extends Controller
{
    public function index(): void
    {
    }

    public function store(StoreCommentRequest $request, Game $game): JsonResponse
    {
        $this->authorize('create', [GameComment::class, $game]);

        $data = StoreCommentData::fromRequest($request);

        /** @var User $user */
        $user = Auth::user();

        // Automatically subscribe the user to the game wall if they've never previously
        // been subscribed to it and then later unsubscribed.
        $doesSubscriptionExist = $user->subscriptions()
            ->whereSubjectType(SubscriptionSubjectType::GameWall)
            ->whereSubjectId($game->id)
            ->exists();
        if (!$doesSubscriptionExist) {
            updateSubscription(SubscriptionSubjectType::GameWall, $game->id, $user->id, true);
        }

        addArticleComment($user->username, $data->commentableType, $data->commentableId, $data->body);

        return response()->json(['success' => true]);
    }

    public function show(): void
    {
    }

    public function update(): void
    {
    }

    public function destroy(Game $game, GameComment $comment): JsonResponse
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
