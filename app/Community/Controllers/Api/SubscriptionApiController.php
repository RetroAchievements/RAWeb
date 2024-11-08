<?php

namespace App\Community\Controllers\Api;

use App\Community\Data\SubscriptionData;
use App\Community\Enums\SubscriptionSubjectType;
use App\Http\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SubscriptionApiController extends Controller
{
    public function index(): void
    {
    }

    public function store(string $subjectType, int $subjectId): JsonResponse
    {
        $this->authorize('create', [Subscription::class]);

        /** @var User $user */
        $user = Auth::user();

        if (!SubscriptionSubjectType::isValid($subjectType)) {
            return response()->json(['success' => false], 400);
        }

        $subscription = Subscription::updateOrCreate(
            [
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'user_id' => $user->id,
            ],
            ['state' => true]
        );

        $statusCode = $subscription->wasRecentlyCreated ? 201 : 200;

        return response()->json(
            ['data' => SubscriptionData::fromSubscription($subscription)],
            $statusCode
        );
    }

    public function show(): void
    {
    }

    public function update(): void
    {
    }

    public function destroy(string $subjectType, int $subjectId): JsonResponse|Response
    {
        /** @var User $user */
        $user = Auth::user();

        $subscription = Subscription::whereUserId($user->id)
            ->whereSubjectType($subjectType)
            ->whereSubjectId($subjectId)
            ->first();

        // It may be an implicit subscription. We'll store a new subscription
        // record with a state explicitly set to `false`.
        if (!$subscription) {
            $subscription = Subscription::updateOrCreate(
                [
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'user_id' => $user->id,
                ],
                ['state' => false]
            );

            return response()->noContent();
        }

        $this->authorize('delete', $subscription);

        $subscription->state = false;
        $subscription->save();

        return response()->noContent();
    }
}
