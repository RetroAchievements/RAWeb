<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpdateBeatenGamesLeaderboardAction;
use App\Platform\Actions\UpdatePlayerBeatenGamesStatsAction;
use App\Platform\Enums\PlayerStatRankingKind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class BeatenGamesLeaderboardTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerBadges;

    /**
     * Rebuilds the pre-computed leaderboard rankings from player_stats.
     * This just simulates what the recurring scheduled job does.
     */
    private function rebuildLeaderboardRankings(?int $systemId = null): void
    {
        $action = new UpdateBeatenGamesLeaderboardAction();

        // If a specific system is provided, only rebuild stats for that system.
        // Otherwise, rebuild stats for the overall metrics.
        $systemIds = $systemId !== null ? [$systemId] : [null];

        foreach ($systemIds as $sysId) {
            foreach (PlayerStatRankingKind::beatenCases() as $kind) {
                $action->execute($sysId, $kind);
            }
        }
    }

    public function testItRendersWithoutCrashing(): void
    {
        $this->get('/ranking/beaten-games')->assertStatus(200);
    }

    public function testItRendersEmptyState(): void
    {
        $this->get('/ranking/beaten-games')->assertSeeText("any rows matching your");
    }

    public function testItRendersRanks(): void
    {
        // Arrange
        $users = User::factory()->count(3)->create();
        $system = System::factory()->create();
        $games = Game::factory()->count(3)->create(['ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($users->get(0), $games->get(0));

        $this->addGameBeatenAward($users->get(1), $games->get(0));
        $this->addGameBeatenAward($users->get(1), $games->get(1));

        $this->addGameBeatenAward($users->get(2), $games->get(0));
        $this->addGameBeatenAward($users->get(2), $games->get(1));
        $this->addGameBeatenAward($users->get(2), $games->get(2));

        (new UpdatePlayerBeatenGamesStatsAction())->execute($users->get(0));
        (new UpdatePlayerBeatenGamesStatsAction())->execute($users->get(1));
        (new UpdatePlayerBeatenGamesStatsAction())->execute($users->get(2));

        $this->rebuildLeaderboardRankings();

        // Act
        $view = $this->get('/ranking/beaten-games');

        // Assert
        $view->assertSeeTextInOrder([
            '#1', $users->get(2)->username,
            '#2', $users->get(1)->username,
            '#3', $users->get(0)->username,
        ]);
    }

    public function testItRendersTies(): void
    {
        // Arrange
        $users = User::factory()->count(3)->create();
        $system = System::factory()->create();
        $games = Game::factory()->count(3)->create(['ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($users->get(0), $games->get(0));

        $this->addGameBeatenAward($users->get(1), $games->get(0), awardTime: Carbon::now()->subMinutes(30));
        $this->addGameBeatenAward($users->get(1), $games->get(1), awardTime: Carbon::now()->subMinutes(30));

        $this->addGameBeatenAward($users->get(2), $games->get(0));
        $this->addGameBeatenAward($users->get(2), $games->get(1));

        (new UpdatePlayerBeatenGamesStatsAction())->execute($users->get(0));
        (new UpdatePlayerBeatenGamesStatsAction())->execute($users->get(1));
        (new UpdatePlayerBeatenGamesStatsAction())->execute($users->get(2));

        $this->rebuildLeaderboardRankings();

        // Act
        $view = $this->get('/ranking/beaten-games');

        // Assert
        $view->assertSeeTextInOrder([
            '#1', $users->get(1)->username,
            '#1', $users->get(2)->username,
            '#3', $users->get(0)->username,
        ]);
    }

    public function testItAllowsFilteringBySystem(): void
    {
        /**
         * One game will go to System A. Two games will go to System B.
         */

        // Arrange
        $users = User::factory()->count(3)->create();
        $systems = System::factory()->count(2)->create();
        $gameOne = Game::factory()->create(['ConsoleID' => $systems->get(0)->ID]);
        $gameTwo = Game::factory()->create(['ConsoleID' => $systems->get(1)->ID]);
        $gameThree = Game::factory()->create(['ConsoleID' => $systems->get(1)->ID]);

        $this->addGameBeatenAward($users->get(0), $gameOne);

        $this->addGameBeatenAward($users->get(1), $gameOne);
        $this->addGameBeatenAward($users->get(1), $gameTwo);

        $this->addGameBeatenAward($users->get(2), $gameOne);
        $this->addGameBeatenAward($users->get(2), $gameTwo);
        $this->addGameBeatenAward($users->get(2), $gameThree);

        (new UpdatePlayerBeatenGamesStatsAction())->execute($users->get(0));
        (new UpdatePlayerBeatenGamesStatsAction())->execute($users->get(1));
        (new UpdatePlayerBeatenGamesStatsAction())->execute($users->get(2));

        $this->rebuildLeaderboardRankings($systems->get(1)->ID);

        // Act
        $view = $this->get('/ranking/beaten-games?filter[system]=' . $systems->get(1)->ID);

        // Assert
        $view->assertSeeTextInOrder([
            '#1', $users->get(2)->username,
            '#2', $users->get(1)->username,
        ]);
    }

    public function testItAllowsExcludingRetailGames(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();

        $hack = Game::factory()->create(['Title' => '~Hack~ Beat Super Mario Bros in 42 seconds', 'ConsoleID' => $system->ID]);
        $retail = Game::factory()->create(['Title' => 'Donkey Kong', 'ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($user, $hack);
        $this->addGameBeatenAward($user, $retail);

        (new UpdatePlayerBeatenGamesStatsAction())->execute($user);

        $this->rebuildLeaderboardRankings();

        // Act
        $view = $this->get('/ranking/beaten-games?filter[kind]=retail');

        // Assert
        $view->assertSee($user->username . '-count-1');
    }
}
