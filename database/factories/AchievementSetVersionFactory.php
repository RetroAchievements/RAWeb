<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AchievementSet;
use App\Models\AchievementSetVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AchievementSetVersion>
 */
class AchievementSetVersionFactory extends Factory
{
    protected $model = AchievementSetVersion::class;

    public function definition(): array
    {
        $achievementSet = AchievementSet::factory()->create();
        $playersTotal = rand(0, $achievementSet->players_total);
        $playersHardcore = min(rand(0, $achievementSet->players_hardcore), $playersTotal);

        return [
            'version' => 1,
            'parent_id' => null,
            'achievement_set_id' => $achievementSet->id,
            'players_total' => $playersTotal,
            'players_hardcore' => $playersHardcore,
            'achievements_published' => $achievementSet->achievements_published,
            'achievements_unpublished' => $achievementSet->achievements_unpublished,
            'points_total' => $achievementSet->points_total,
        ];
    }
}
