<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\AchievementSetAuthor;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\User;
use App\Platform\Enums\AchievementSetAuthorTask;
use Carbon\Carbon;

class AddGameBadgeCreditAction
{
    public function execute(Game $game, User $user, ?Carbon $date = null): ?AchievementSetAuthor
    {
        if (!$date) {
            $date = now();
        }

        $coreAchievementSet = GameAchievementSet::where('game_id', $game->id)
            ->core()
            ->first();

        // This usually happens for hubs.
        if (!$coreAchievementSet?->achievementSet) {
            return null;
        }

        $achievementSet = $coreAchievementSet->achievementSet;

        $alreadyExists = AchievementSetAuthor::where('achievement_set_id', $achievementSet->id)
            ->whereUserId($user->id)
            ->whereTask(AchievementSetAuthorTask::Artwork)
            ->exists();

        if ($alreadyExists) {
            return null;
        }

        return AchievementSetAuthor::create([
            'user_id' => $user->id,
            'achievement_set_id' => $achievementSet->id,
            'task' => AchievementSetAuthorTask::Artwork,
            'created_at' => $date,
        ]);
    }
}
