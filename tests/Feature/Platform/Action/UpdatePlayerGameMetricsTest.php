<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Actions\UpdatePlayerGameMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UpdatePlayerGameMetricsTest extends TestCase
{
    use RefreshDatabase;

    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    public function testMetrics(): void
    {
        User::factory()->count(1)->create();

        $game = $this->seedGame(withHash: false);

        $achievements = Achievement::factory()->published()->count(10)->create(['GameID' => $game->id, 'Points' => 3]);

        $user = User::first();

        $this->addSoftcoreUnlock($user, $achievements->get(0));

        $playerGame = $user->playerGames()->where("game_id", $game->id)->first();

        (new UpdatePlayerGameMetrics())->execute($playerGame);

        $playerGames = PlayerGame::first();
        $this->assertEquals(
            [
                'achievements_unlocked' => 1,
                'achievements_unlocked_hardcore' => 0,
                'achievements_unlocked_softcore' => 1,
            ],
            $playerGames->only(
                [
                    'achievements_unlocked',
                    'achievements_unlocked_hardcore',
                    'achievements_unlocked_softcore',
                ]
            )
        );

        $this->addSoftcoreUnlock($user, $achievements->get(1));
        $this->addSoftcoreUnlock($user, $achievements->get(2));
        $this->addHardcoreUnlock($user, $achievements->get(3));

        (new UpdatePlayerGameMetrics())->execute($playerGame);

        $playerGames = PlayerGame::first();
        $this->assertEquals(
            [
                'achievements_unlocked' => 4,
                'achievements_unlocked_hardcore' => 1,
                'achievements_unlocked_softcore' => 3,
            ],
            $playerGames->only(
                [
                    'achievements_unlocked',
                    'achievements_unlocked_hardcore',
                    'achievements_unlocked_softcore',
                ]
            )
        );

        $this->addHardcoreUnlock($user, $achievements->get(1));

        (new UpdatePlayerGameMetrics())->execute($playerGame);

        $playerGames = PlayerGame::first();
        $this->assertEquals(
            [
                'achievements_unlocked' => 4,
                'achievements_unlocked_hardcore' => 2,
                'achievements_unlocked_softcore' => 2,
            ],
            $playerGames->only(
                [
                    'achievements_unlocked',
                    'achievements_unlocked_hardcore',
                    'achievements_unlocked_softcore',
                ]
            )
        );
    }
}
