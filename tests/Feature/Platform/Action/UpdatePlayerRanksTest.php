<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Platform\Actions\UpdatePlayerRanks;
use App\Platform\Enums\RankingType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Game;
use App\Platform\Models\Ranking;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UpdatePlayerRanksTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerBadges;

    public function testItUpsertsNothingIfNoPlayerGames(): void
    {
        $user = User::factory()->create();

        (new UpdatePlayerRanks())->execute($user);

        $userRankings = Ranking::where('user_id', $user->id)->get();
        $this->assertCount(0, $userRankings);
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
        (new UpdatePlayerRanks())->execute($user);

        // Assert
        $userRankings = Ranking::where('user_id', $user->id)->get();
        $this->assertCount(0, $userRankings);
    }

    public function testItUpsertsRanksIfHardcoreBeatenPlayerGames(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Super Mario Bros.']);

        $this->addGameBeatenAward($user, $game, UnlockMode::Hardcore, Carbon::create(2023, 1, 1));

        // Act
        (new UpdatePlayerRanks())->execute($user);

        // Assert
        $userRankings = Ranking::where('user_id', $user->id)->get();

        // Two rows are added for overall retail and system-specific retail.
        $this->assertCount(2, $userRankings);

        $overallRankings = $userRankings->whereNull('system_id')->first();
        $this->assertEquals($user->id, $overallRankings->user_id);
        $this->assertEquals($game->id, $overallRankings->game_id);
        $this->assertEquals(RankingType::GamesBeatenHardcoreRetail, $overallRankings->type);
        $this->assertEquals(1, $overallRankings->value);
        $this->assertEquals(Carbon::create(2023, 1, 1), $overallRankings->updated_at);

        $systemRankings = $userRankings->whereNotNull('system_id')->first();
        $this->assertEquals($system->ID, $systemRankings->system_id);
        $this->assertEquals($user->id, $systemRankings->user_id);
        $this->assertEquals($game->id, $systemRankings->game_id);
        $this->assertEquals(RankingType::GamesBeatenHardcoreRetail, $systemRankings->type);
        $this->assertEquals(1, $systemRankings->value);
        $this->assertEquals(Carbon::create(2023, 1, 1), $systemRankings->updated_at);
    }

    public function testItUpsertsDifferentTypesOfRankings(): void
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
            (new UpdatePlayerRanks())->execute($user);
        }

        // Assert
        $userOneRankings = Ranking::where('user_id', $users->get(0)->id)->get();
        $this->assertCount(6, $userOneRankings);
        $this->assertRankingDetails($userOneRankings, $gameRetail->id, $systems->get(0)->ID, Carbon::create(2022, 1, 1));
        $this->assertRankingDetails($userOneRankings, $gameDemo->id, $systems->get(0)->ID, Carbon::create(2022, 1, 1)->addDays(10));
        $this->assertRankingDetails($userOneRankings, $gameHack->id, $systems->get(1)->ID, Carbon::create(2022, 1, 1)->addDays(20));
        $this->assertRankingDetails($userOneRankings, $gameRetail->id, null, Carbon::create(2022, 1, 1), true);
        $this->assertRankingDetails($userOneRankings, $gameDemo->id, null, Carbon::create(2022, 1, 1)->addDays(10), true);
        $this->assertRankingDetails($userOneRankings, $gameHack->id, null, Carbon::create(2022, 1, 1)->addDays(20), true);

        $userTwoRankings = Ranking::where('user_id', $users->get(1)->id)->get();
        $this->assertCount(6, $userTwoRankings);
        $this->assertRankingDetails($userTwoRankings, $gameRetail->id, $systems->get(0)->ID, Carbon::create(2022, 2, 1));
        $this->assertRankingDetails($userTwoRankings, $gameDemo->id, $systems->get(0)->ID, Carbon::create(2022, 2, 1)->addDays(10));
        $this->assertRankingDetails($userTwoRankings, $gameHack->id, $systems->get(1)->ID, Carbon::create(2022, 2, 1)->addDays(20));
        $this->assertRankingDetails($userTwoRankings, $gameRetail->id, null, Carbon::create(2022, 2, 1), true);
        $this->assertRankingDetails($userTwoRankings, $gameDemo->id, null, Carbon::create(2022, 2, 1)->addDays(10), true);
        $this->assertRankingDetails($userTwoRankings, $gameHack->id, null, Carbon::create(2022, 2, 1)->addDays(20), true);

        $userThreeRankings = Ranking::where('user_id', $users->get(2)->id)->get();
        $this->assertCount(6, $userThreeRankings);
        $this->assertRankingDetails($userThreeRankings, $gameRetail->id, $systems->get(0)->ID, Carbon::create(2022, 3, 1));
        $this->assertRankingDetails($userThreeRankings, $gameDemo->id, $systems->get(0)->ID, Carbon::create(2022, 3, 1)->addDays(10));
        $this->assertRankingDetails($userThreeRankings, $gameHack->id, $systems->get(1)->ID, Carbon::create(2022, 3, 1)->addDays(20));
        $this->assertRankingDetails($userThreeRankings, $gameRetail->id, null, Carbon::create(2022, 3, 1), true);
        $this->assertRankingDetails($userThreeRankings, $gameDemo->id, null, Carbon::create(2022, 3, 1)->addDays(10), true);
        $this->assertRankingDetails($userThreeRankings, $gameHack->id, null, Carbon::create(2022, 3, 1)->addDays(20), true);
    }

    public function testItDoesntRankUntrackedUsers(): void
    {
        // Arrange
        $untrackedUser = User::factory()->create(['Untracked' => true]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Super Mario Bros.']);

        $this->addGameBeatenAward($untrackedUser, $game, UnlockMode::Hardcore, Carbon::create(2023, 1, 1));

        // Act
        (new UpdatePlayerRanks())->execute($untrackedUser);

        // Assert
        $userRankings = Ranking::where('user_id', $untrackedUser->id)->get();
        $this->assertCount(0, $userRankings, 'No rankings should be created for an untracked user');
    }

    public function testItPurgesUntrackedUserRanks(): void
    {
        // Arrange
        $user = User::factory()->create(); // Initially tracked
        $system = System::factory()->create();
        Game::factory()->create(['ConsoleID' => $system->ID]);

        (new UpdatePlayerRanks())->execute($user);

        $user->Untracked = true;
        $user->save();

        // Act
        (new UpdatePlayerRanks())->execute($user);

        // Assert
        $userRankings = Ranking::where('user_id', $user->id)->get();
        $this->assertCount(0, $userRankings, 'Rankings should be removed for an untracked user');
    }

    protected function assertRankingDetails(
        mixed $rankings,
        int $gameId,
        ?int $systemId,
        Carbon $expectedDate,
        bool $isOverall = false
    ): void {
        $query = $rankings->where('game_id', $gameId);
        $query = $isOverall ? $query->whereNull('system_id') : $query->where('system_id', $systemId);

        $ranking = $query->first();
        $this->assertNotNull($ranking);
        $this->assertEquals($expectedDate->toDateTimeString(), $ranking->updated_at->toDateTimeString());
    }
}
