<?php

namespace App\Community\Controllers\Api;

use App\Community\Data\StoreCommentData;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Requests\StoreCommentRequest;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\AchievementComment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AchievementCommentApiController extends Controller
{
    public function index(): void
    {
    }

    public function store(StoreCommentRequest $request, Achievement $achievement): JsonResponse
    {
        $this->authorize('create', [AchievementComment::class, $achievement]);

        $data = StoreCommentData::fromRequest($request);

        /** @var User $user */
        $user = Auth::user();

        // Automatically subscribe the user to the achievement wall if they've never previously
        // been subscribed to it and then later unsubscribed.
        $doesSubscriptionExist = $user->subscriptions()
            ->whereSubjectType(SubscriptionSubjectType::Achievement)
            ->whereSubjectId($achievement->id)
            ->exists();
        if (!$doesSubscriptionExist) {
            updateSubscription(SubscriptionSubjectType::Achievement, $achievement->id, $user->id, true);
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

    public function destroy(Achievement $achievement, AchievementComment $comment): JsonResponse
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
