<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\GameAchievementSet;
use App\Models\User;
use App\Models\UserGameAchievementSetPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function update(Request $request, GameAchievementSet $gameAchievementSet): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $optedIn = $request->input('optedIn');

        $preference = UserGameAchievementSetPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'game_achievement_set_id' => $gameAchievementSet->id,
            ],
            ['opted_in' => $optedIn]
        );

        return response()->json(['optedIn' => $preference->opted_in]);
    }

    public function destroy(): void
    {
    }
}
