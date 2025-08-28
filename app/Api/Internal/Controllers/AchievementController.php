<?php

declare(strict_types=1);

namespace App\Api\Internal\Controllers;

use App\Api\Internal\Requests\DemoteAchievementRequest;
use App\Community\Enums\ArticleType;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Services\GameTopAchieversService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AchievementController extends Controller
{
    /**
     * Demote an achievement from Official/Core/Published to Unofficial/Unpublished status.
     *
     * This endpoint allows authorized service accounts to demote achievements,
     * optionally updating the achievement title in the process (eg: prefixing
     * with "DEMOTED AS UNWELCOME CONCEPT").
     *
     * @example Request payload:
     * ```json
     * {
     *   "data": {
     *     "type": "achievement-demotion",
     *     "attributes": {
     *       "achievementId": 123,
     *       "username": "DevCompliance",
     *       "title": "DEMOTED AS UNWELCOME CONCEPT - Original Title" // optional
     *     }
     *   }
     * }
     * ```
     *
     * @example Success response (200):
     * ```json
     * {
     *   "data": {
     *     "type": "achievement-demotion",
     *     "id": "123",
     *     "attributes": {
     *       "achievementId": 123,
     *       "status": "demoted",
     *       "demotedAt": "2024-01-01T12:00:00+00:00",
     *       "demotedBy": "DevCompliance",
     *       "wasTitleUpdated": true
     *     }
     *   }
     * }
     * ```
     */
    public function demote(DemoteAchievementRequest $request): JsonResponse
    {
        $achievementId = $request->getAchievementId();
        $username = $request->getUsername();
        $newTitle = $request->getTitle();

        $user = User::whereName($username)->first();
        $achievement = Achievement::find($achievementId);

        $wasTitleUpdated = false;

        DB::transaction(function () use ($achievement, $user, $newTitle, &$wasTitleUpdated) {
            updateAchievementFlag($achievement->id, AchievementFlag::Unofficial);

            if ($newTitle !== null && $newTitle !== $achievement->title) {
                $achievement->title = $newTitle;
                $achievement->save();
                $wasTitleUpdated = true;
            }

            addArticleComment(
                "Server",
                ArticleType::Achievement,
                $achievement->ID,
                "{$user->display_name} demoted this achievement to Unofficial.",
                $user->display_name
            );

            GameTopAchieversService::expireTopAchieversComponentData($achievement->GameID);
        });

        return response()->json([
            'data' => [
                'type' => 'achievement-demotion',
                'id' => (string) $achievementId,
                'attributes' => [
                    'achievementId' => $achievementId,
                    'status' => 'demoted',
                    'demotedAt' => now()->toIso8601String(),
                    'demotedBy' => $username,
                    'wasTitleUpdated' => $wasTitleUpdated,
                ],
            ],
        ]);
    }
}
