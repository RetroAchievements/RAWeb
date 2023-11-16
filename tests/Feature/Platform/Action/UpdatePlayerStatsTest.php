<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Platform\Actions\UpdatePlayerStats;
use App\Platform\Enums\PlayerStatType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerStat;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UpdatePlayerStatsTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerBadges;

    public function testItUpsertsNothingIfNoPlayerGames(): void
    {
        $user = User::factory()->create();

        (new UpdatePlayerStats())->execute($user);

        $userStats = PlayerStat::where('user_id', $user->id)->get();
        $this->assertCount(0, $userStats);
    }

    public function testItUpsertsNothingIfOnlySoftcoreBeatenPlayerGames(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();
        $games = Game::factory()->count(3)->create(['ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($user, $games->get(0), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, $games->get(1), UnlockMode::Softcore);
        $this->addGameBeatenAward($user, $games->get(2), UnlockMode::Softcore);

        // Act
        (new UpdatePlayerStats())->execute($user);

        // Assert
        $userStats = PlayerStat::where('user_id', $user->id)->get();
        $this->assertCount(0, $userStats);
    }

    public function testItUpsertsStatsIfHardcoreBeatenPlayerGames(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Super Mario Bros.']);

        $this->addGameBeatenAward($user, $game, UnlockMode::Hardcore, Carbon::create(2023, 1, 1));

        // Act
        (new UpdatePlayerStats())->execute($user);

        // Assert
        $userStats = PlayerStat::where('user_id', $user->id)->get();

        // Two rows are added for overall retail and system-specific retail.
        $this->assertCount(2, $userStats);

        $overallStats = $userStats->whereNull('system_id')->first();
        $this->assertEquals($user->id, $overallStats->user_id);
        $this->assertEquals($game->id, $overallStats->last_game_id);
        $this->assertEquals(PlayerStatType::GamesBeatenHardcoreRetail, $overallStats->type);
        $this->assertEquals(1, $overallStats->value);
        $this->assertEquals(Carbon::create(2023, 1, 1), $overallStats->updated_at);

        $systemStats = $userStats->whereNotNull('system_id')->first();
        $this->assertEquals($system->ID, $systemStats->system_id);
        $this->assertEquals($user->id, $systemStats->user_id);
        $this->assertEquals($game->id, $systemStats->last_game_id);
        $this->assertEquals(PlayerStatType::GamesBeatenHardcoreRetail, $systemStats->type);
        $this->assertEquals(1, $systemStats->value);
        $this->assertEquals(Carbon::create(2023, 1, 1), $systemStats->updated_at);
    }

    public function testItUpsertsDifferentTypesOfStats(): void
    {
        // Arrange
        $users = User::factory()->count(3)->create();
        $systems = System::factory()->count(2)->create();

        $gameRetail = Game::factory()->create(['ConsoleID' => $systems->get(0)->ID, 'Title' => 'Super Mario Bros.']);
        $gameDemo = Game::factory()->create(['ConsoleID' => $systems->get(0)->ID, 'Title' => '~Demo~ Game']);
        $gameHack = Game::factory()->create(['ConsoleID' => $systems->get(1)->ID, 'Title' => '~Hack~ Game']);

        foreach ($users as $index => $user) {
            $this->addGameBeatenAward($user, $gameRetail, UnlockMode::Hardcore, Carbon::create(2022, $index + 1, 1));
            $this->addGameBeatenAward($user, $gameDemo, UnlockMode::Hardcore, Carbon::create(2022, $index + 1, 1)->addDays(10));
            $this->addGameBeatenAward($user, $gameHack, UnlockMode::Hardcore, Carbon::create(2022, $index + 1, 1)->addDays(20));
        }

        // Act
        foreach ($users as $user) {
            (new UpdatePlayerStats())->execute($user);
        }

        // Assert
        $userOneStats = PlayerStat::where('user_id', $users->get(0)->id)->get();
        $this->assertCount(6, $userOneStats);
        $this->assertPlayerStatDetails($userOneStats, $gameRetail->id, $systems->get(0)->ID, Carbon::create(2022, 1, 1));
        $this->assertPlayerStatDetails($userOneStats, $gameDemo->id, $systems->get(0)->ID, Carbon::create(2022, 1, 1)->addDays(10));
        $this->assertPlayerStatDetails($userOneStats, $gameHack->id, $systems->get(1)->ID, Carbon::create(2022, 1, 1)->addDays(20));
        $this->assertPlayerStatDetails($userOneStats, $gameRetail->id, null, Carbon::create(2022, 1, 1), true);
        $this->assertPlayerStatDetails($userOneStats, $gameDemo->id, null, Carbon::create(2022, 1, 1)->addDays(10), true);
        $this->assertPlayerStatDetails($userOneStats, $gameHack->id, null, Carbon::create(2022, 1, 1)->addDays(20), true);

        $userTwoStats = PlayerStat::where('user_id', $users->get(1)->id)->get();
        $this->assertCount(6, $userTwoStats);
        $this->assertPlayerStatDetails($userTwoStats, $gameRetail->id, $systems->get(0)->ID, Carbon::create(2022, 2, 1));
        $this->assertPlayerStatDetails($userTwoStats, $gameDemo->id, $systems->get(0)->ID, Carbon::create(2022, 2, 1)->addDays(10));
        $this->assertPlayerStatDetails($userTwoStats, $gameHack->id, $systems->get(1)->ID, Carbon::create(2022, 2, 1)->addDays(20));
        $this->assertPlayerStatDetails($userTwoStats, $gameRetail->id, null, Carbon::create(2022, 2, 1), true);
        $this->assertPlayerStatDetails($userTwoStats, $gameDemo->id, null, Carbon::create(2022, 2, 1)->addDays(10), true);
        $this->assertPlayerStatDetails($userTwoStats, $gameHack->id, null, Carbon::create(2022, 2, 1)->addDays(20), true);

        $userThreeStats = PlayerStat::where('user_id', $users->get(2)->id)->get();
        $this->assertCount(6, $userThreeStats);
        $this->assertPlayerStatDetails($userThreeStats, $gameRetail->id, $systems->get(0)->ID, Carbon::create(2022, 3, 1));
        $this->assertPlayerStatDetails($userThreeStats, $gameDemo->id, $systems->get(0)->ID, Carbon::create(2022, 3, 1)->addDays(10));
        $this->assertPlayerStatDetails($userThreeStats, $gameHack->id, $systems->get(1)->ID, Carbon::create(2022, 3, 1)->addDays(20));
        $this->assertPlayerStatDetails($userThreeStats, $gameRetail->id, null, Carbon::create(2022, 3, 1), true);
        $this->assertPlayerStatDetails($userThreeStats, $gameDemo->id, null, Carbon::create(2022, 3, 1)->addDays(10), true);
        $this->assertPlayerStatDetails($userThreeStats, $gameHack->id, null, Carbon::create(2022, 3, 1)->addDays(20), true);
    }

    public function testItDoesntAddStatsForUntrackedUsers(): void
    {
        // Arrange
        $untrackedUser = User::factory()->create(['Untracked' => true]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Super Mario Bros.']);

        $this->addGameBeatenAward($untrackedUser, $game, UnlockMode::Hardcore, Carbon::create(2023, 1, 1));

        // Act
        (new UpdatePlayerStats())->execute($untrackedUser);

        // Assert
        $userStats = PlayerStat::where('user_id', $untrackedUser->id)->get();
        $this->assertCount(0, $userStats);
    }

    public function testItPurgesUntrackedUserStats(): void
    {
        // Arrange
        $user = User::factory()->create(); // Initially tracked
        $system = System::factory()->create();
        Game::factory()->create(['ConsoleID' => $system->ID]);

        (new UpdatePlayerStats())->execute($user);

        $user->Untracked = true;
        $user->save();

        // Act
        (new UpdatePlayerStats())->execute($user);

        // Assert
        $userStats = PlayerStat::where('user_id', $user->id)->get();
        $this->assertCount(0, $userStats);
    }

    protected function assertPlayerStatDetails(
        mixed $playerStats,
        int $gameId,
        ?int $systemId,
        Carbon $expectedDate,
        bool $isOverall = false
    ): void {
        $query = $playerStats->where('last_game_id', $gameId);
        $query = $isOverall ? $query->whereNull('system_id') : $query->where('system_id', $systemId);

        $stat = $query->first();
        $this->assertNotNull($stat);
        $this->assertEquals($expectedDate->toDateTimeString(), $stat->updated_at->toDateTimeString());
    }
}
