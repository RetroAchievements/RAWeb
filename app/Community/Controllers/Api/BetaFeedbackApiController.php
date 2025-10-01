<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Requests\StoreBetaFeedbackRequest;
use App\Http\Controller;
use App\Models\User;
use App\Models\UserBetaFeedbackSubmission;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BetaFeedbackApiController extends Controller
{
    public function store(StoreBetaFeedbackRequest $request): JsonResponse
    {
        $this->authorize('create', UserBetaFeedbackSubmission::class);

        /** @var User $user */
        $user = Auth::user();

        $data = $request->validated();

        $pageUrl = $request->header('Referer');
        $userAgent = $request->userAgent();

        $cacheKey = CacheKey::buildUserBetaVisitsCacheKey($user->username, $data['betaName']);
        $visitData = Cache::get($cacheKey);
        $visitCount = $visitData['visit_count'] ?? null;
        $firstVisitedAt = isset($visitData['first_visited_at'])
            ? Carbon::createFromTimestamp($visitData['first_visited_at'])
            : null;
        $lastVisitedAt = isset($visitData['last_visited_at'])
            ? Carbon::createFromTimestamp($visitData['last_visited_at'])
            : null;

        UserBetaFeedbackSubmission::create([
            'user_id' => $user->id,
            'beta_name' => $data['betaName'],
            'rating' => $data['rating'],
            'positive_feedback' => $data['positiveFeedback'],
            'negative_feedback' => $data['negativeFeedback'],
            'page_url' => $pageUrl,
            'user_agent' => $userAgent,
            'app_version' => config('app.version'),
            'visit_count' => $visitCount,
            'first_visited_at' => $firstVisitedAt,
            'last_visited_at' => $lastVisitedAt,
        ]);

        return response()->json(['success' => true]);
    }
}
