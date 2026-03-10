<?php

declare(strict_types=1);

namespace App\Platform\Controllers\Api;

use App\Http\Controller;
use App\Models\Achievement;
use App\Models\User;
use App\Platform\Enums\AchievementType;
use App\Platform\Requests\UpdateAchievementQuickEditRequest;
use Illuminate\Http\JsonResponse;

class AchievementApiController extends Controller
{
    public function update(UpdateAchievementQuickEditRequest $request, Achievement $achievement): JsonResponse
    {
        $this->authorize('update', $achievement);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        if (
            array_key_exists('type', $validated)
            && $validated['type'] !== null
            && AchievementType::isProgression($validated['type'])
            && $achievement->game->parentGame() !== null
        ) {
            abort(422, 'Subset achievements cannot have progression or win_condition types.');
        }

        // Map camelCase request keys to snake_case model attributes.
        $fieldMap = ['isPromoted' => 'is_promoted'];

        foreach ($validated as $field => $value) {
            $column = $fieldMap[$field] ?? $field;

            // Silently skip fields the user isn't authorized to edit.
            if (!$user->can('updateField', [$achievement, $column])) {
                continue;
            }

            $achievement->{$column} = $value;
        }

        if ($achievement->isDirty()) {
            $achievement->save();
        }

        return response()->json(['success' => true]);
    }
}
