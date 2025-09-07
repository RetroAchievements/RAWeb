<?php

namespace App\Community\Controllers\Api;

use App\Community\Data\SubscriptionData;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
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

        $convertedSubjectType = SubscriptionSubjectType::tryFrom($subjectType);
        if (!$convertedSubjectType) {
            return response()->json(['success' => false], 400);
        }

        $subscription = (new SubscriptionService())->updateSubscription($user, $convertedSubjectType, $subjectId, true);

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

        $convertedSubjectType = SubscriptionSubjectType::tryFrom($subjectType);
        if (!$convertedSubjectType) {
            return response()->json(['success' => false], 400);
        }

        $subscription = Subscription::whereUserId($user->id)
            ->whereSubjectType($subjectType)
            ->whereSubjectId($subjectId)
            ->first();

        // It may be an implicit subscription. We'll store a new subscription
        // record with a state explicitly set to `false`.
        if (!$subscription) {
            (new SubscriptionService())->updateSubscription($user, $convertedSubjectType, $subjectId, false);

            return response()->noContent();
        }

        $this->authorize('delete', $subscription);

        $subscription->state = false;
        $subscription->save();

        return response()->noContent();
    }
}
