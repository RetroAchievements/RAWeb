<?php

declare(strict_types=1);

namespace App\Api\Internal\Controllers;

use App\Api\Internal\Requests\UpdateAchievementRequest;
use App\Community\Enums\ArticleType;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Role;
use App\Models\User;
use App\Platform\Services\GameTopAchieversService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use LaravelJsonApi\Core\Exceptions\JsonApiException;

class AchievementController extends Controller
{
    /**
     * This endpoint allows authorized service accounts to update achievements,
     * including demoting them to unpublished status and/or updating the title.
     *
     * @example Request payload:
     * ```json
     * {
     *   "data": {
     *     "type": "achievements",
     *     "id": "123",
     *     "attributes": {
     *       "published": false,
     *       "title": "DEMOTED AS UNWELCOME CONCEPT - Original Title" // optional
     *     },
     *     "meta": {
     *       "actingUser": "DevCompliance"
     *     }
     *   }
     * }
     * ```
     *
     * @example Success response (200):
     * ```json
     * {
     *   "data": {
     *     "type": "achievements",
     *     "id": "123",
     *     "attributes": {
     *       "title": "DEMOTED AS UNWELCOME CONCEPT - Original Title",
     *       "published": false,
     *       "points": 10,
     *       "gameId": 456
     *     },
     *     "meta": {
     *       "updatedAt": "2024-01-01T12:00:00+00:00",
     *       "updatedBy": "DevCompliance",
     *       "updatedFields": [
     *         "published",
     *         "title"
     *       ]
     *     }
     *   }
     * }
     * ```
     */
    public function update(UpdateAchievementRequest $request, Achievement $achievement): JsonResponse
    {
        $username = $request->getActingUser();
        $newTitle = $request->getTitle();
        $promoted = $request->getPromoted();

        $newIsPromoted = $promoted;

        $user = User::whereName($username)->first();

        if (!$user->hasRole(Role::TEAM_ACCOUNT)) {
            throw JsonApiException::error([
                'status' => 403,
                'title' => 'Forbidden',
                'code' => 'insufficient_role',
                'detail' => 'User does not have the required team account role.',
            ]);
        }

        // Validate that we're actually making changes.
        $hasChanges = false;
        if ($newIsPromoted !== null && $newIsPromoted !== $achievement->is_promoted) {
            $hasChanges = true;
        }
        if ($newTitle !== null && $newTitle !== $achievement->title) {
            $hasChanges = true;
        }
        if (!$hasChanges) {
            throw JsonApiException::error([
                'status' => 422,
                'title' => 'Unprocessable Entity',
                'code' => 'no_changes',
                'detail' => 'No changes to apply.',
            ]);
        }

        $updatedFields = [];

        DB::transaction(function () use ($achievement, $user, $newTitle, $newIsPromoted, &$updatedFields) {
            // Update is_promoted if `promoted` is provided.
            if ($newIsPromoted !== null && $newIsPromoted !== $achievement->is_promoted) {
                $wasPromoted = $achievement->is_promoted;
                updateAchievementPromotedStatus($achievement->id, $newIsPromoted);
                $updatedFields[] = 'promoted';

                // Add the appropriate comment based on the promotion state change.
                $comment = "{$user->display_name} demoted this achievement to Unofficial.";
                if ($newIsPromoted && !$wasPromoted) {
                    $comment = "{$user->display_name} promoted this achievement to the Core set.";
                }
                addArticleComment(
                    "Server",
                    ArticleType::Achievement,
                    $achievement->id,
                    $comment,
                    $user->display_name
                );
            }

            // Update title if provided.
            if ($newTitle !== null && $newTitle !== $achievement->title) {
                $achievement->title = $newTitle;
                $achievement->save();
                $updatedFields[] = 'title';

                addArticleComment(
                    "Server",
                    ArticleType::Achievement,
                    $achievement->id,
                    "{$user->display_name} edited this achievement's title.",
                    $user->display_name,
                );
            }
        });

        GameTopAchieversService::expireTopAchieversComponentData($achievement->game_id);

        $achievement->refresh();

        return response()->json([
            'data' => [
                'type' => 'achievements',
                'id' => (string) $achievement->id,
                'attributes' => [
                    'title' => $achievement->title,
                    'description' => $achievement->description,
                    'points' => $achievement->points,
                    'promoted' => $achievement->is_promoted,
                    'gameId' => $achievement->game_id,
                ],
                'meta' => [
                    'updatedAt' => now()->toIso8601String(),
                    'updatedBy' => $username,
                    'updatedFields' => $updatedFields,
                ],
            ],
        ]);
    }
}
