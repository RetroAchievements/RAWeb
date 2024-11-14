<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UpdateGameAchievementsMetricsActionTest extends TestCase
{
    use RefreshDatabase;

    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    public function testMetrics(): void
    {
        User::factory()->count(10)->create();
        $game = $this->seedGame(withHash: false);
        Achievement::factory()->published()->count(10)->create(['GameID' => $game->id, 'Points' => 3]);

        foreach (User::all() as $index => $user) {
            for ($i = 0; $i <= $index; $i++) {
                $this->addHardcoreUnlock($user, Achievement::find($i + 1));
            }
        }

        $achievements = Achievement::all();
        $this->assertEquals(
            [10, 9, 8, 7, 6, 5, 4, 3, 2, 1],
            $achievements->pluck('unlocks_total')->toArray()
        );
        $this->assertEquals(
            [1, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4, 0.3, 0.2, 0.1],
            $achievements->pluck('unlock_percentage')->toArray()
        );
        $this->assertEquals(
            [3, 3, 3, 3, 3, 4, 4, 5, 7, 13],
            $achievements->pluck('points_weighted')->toArray()
        );
    }
}
