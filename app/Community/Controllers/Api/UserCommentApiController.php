<?php

namespace App\Community\Controllers\Api;

use App\Community\Data\StoreCommentData;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Requests\StoreCommentRequest;
use App\Http\Controller;
use App\Models\User;
use App\Models\UserComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserCommentApiController extends Controller
{
    public function index(): void
    {
    }

    public function store(StoreCommentRequest $request, User $user): JsonResponse
    {
        $this->authorize('create', [UserComment::class, $user]);

        $data = StoreCommentData::fromRequest($request);

        /** @var User $me */
        $me = Auth::user();

        // Automatically subscribe the user to the user wall if they've never previously
        // been subscribed to it and then later unsubscribed.
        $doesSubscriptionExist = $me->subscriptions()
            ->whereSubjectType(SubscriptionSubjectType::UserWall)
            ->whereSubjectId($user->id)
            ->exists();
        if (!$doesSubscriptionExist) {
            updateSubscription(SubscriptionSubjectType::UserWall, $user->id, $me->id, true);
        }

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
