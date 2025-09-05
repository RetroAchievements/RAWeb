<?php

declare(strict_types=1);

namespace App\Api\Internal\Controllers;

use App\Api\Internal\Requests\UpdateAchievementRequest;
use App\Community\Enums\ArticleType;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Role;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Services\GameTopAchieversService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use LaravelJsonApi\Core\Exceptions\JsonApiException;

class AchievementController extends Controller
{
    /**
     * This endpoint allows authorized service accounts to update achievements,
     * including demoting them to Unofficial status and/or updating the title.
     *
     * @example Request payload:
     * ```json
     * {
     *   "data": {
     *     "type": "achievement",
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
     *     "type": "achievement",
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
        $published = $request->getPublished();

        // Map the published boolean to flag values.
        $newFlags = null;
        if ($published !== null) {
            $newFlags = $published ? AchievementFlag::OfficialCore->value : AchievementFlag::Unofficial->value;
        }

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
        if ($newFlags !== null && $newFlags !== $achievement->Flags) {
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

        DB::transaction(function () use ($achievement, $user, $newTitle, $newFlags, &$updatedFields) {
            // Update flags if `published` is provided.
            if ($newFlags !== null && $newFlags !== $achievement->Flags) {
                $oldFlags = $achievement->Flags;
                updateAchievementFlag($achievement->id, AchievementFlag::from($newFlags));
                $updatedFields[] = 'published';

                // Add the appropriate comment based on the flag change.
                $comment = "{$user->display_name} demoted this achievement to Unofficial.";
                if ($newFlags === AchievementFlag::OfficialCore->value && $oldFlags === AchievementFlag::Unofficial->value) {
                    $comment = "{$user->display_name} promoted this achievement to the Core set.";
                }
                addArticleComment(
                    "Server",
                    ArticleType::Achievement,
                    $achievement->ID,
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
                    $achievement->ID,
                    "{$user->display_name} edited this achievement's title.",
                    $user->display_name,
                );
            }
        });

        GameTopAchieversService::expireTopAchieversComponentData($achievement->GameID);

        $achievement->refresh();

        return response()->json([
            'data' => [
                'type' => 'achievement',
                'id' => (string) $achievement->ID,
                'attributes' => [
                    'title' => $achievement->title,
                    'description' => $achievement->description,
                    'points' => $achievement->points,
                    'published' => $achievement->Flags === AchievementFlag::OfficialCore->value,
                    'gameId' => $achievement->GameID,
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
