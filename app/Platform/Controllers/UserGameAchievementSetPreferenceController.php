<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\User;
use App\Models\UserGameAchievementSetPreference;
use App\Platform\Requests\UpdateGameAchievementSetPreferencesRequest;
use Illuminate\Http\JsonResponse;

class UserGameAchievementSetPreferenceController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(): void
    {
    }

    public function show(): void
    {
    }

    public function update(UpdateGameAchievementSetPreferencesRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        foreach ($request->validated()['preferences'] as $pref) {
            UserGameAchievementSetPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'game_achievement_set_id' => $pref['gameAchievementSetId'],
                ],
                [
                    'opted_in' => $pref['optedIn'],
                ]
            );
        }

        return response()->json(['success' => true]);
    }

    public function destroy(): void
    {
    }
}
