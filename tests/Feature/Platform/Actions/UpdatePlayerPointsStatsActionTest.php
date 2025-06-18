<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerStat;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpdatePlayerPointsStatsAction;
use App\Platform\Enums\PlayerStatType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UpdatePlayerPointsStatsActionTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testItUpsertsNothingIfNoPlayerAchievements(): void
    {
        $user = User::factory()->create();

        (new UpdatePlayerPointsStatsAction())->execute($user);

        $userStats = PlayerStat::where('user_id', $user->id)->get();
        $this->assertCount(0, $userStats);
    }

    public function testItDoesntAddStatsForUntrackedUsers(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2023, 11, 18, 15, 0, 0));

        $untrackedUser = User::factory()->create(['Untracked' => true, 'unranked_at' => Carbon::now()]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 100]);

        $this->addHardcoreUnlock($untrackedUser, $achievement);

        // Act
        (new UpdatePlayerPointsStatsAction())->execute($untrackedUser);

        // Assert
        $userStats = PlayerStat::where('user_id', $untrackedUser->id)->get();
        $this->assertCount(0, $userStats);
    }

    public function testItCreatesStatsCorrectly(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2023, 11, 18, 15, 0, 0));

        $user = User::factory()->create(); // Initially tracked
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $achievementOne = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);
        $achievementTwo = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);
        $achievementThree = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);
        $achievementFour = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);
        $achievementFive = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]); // deliberate no unlock

        $this->addHardcoreUnlock($user, $achievementOne, Carbon::now()->subMinutes(10));
        $this->addHardcoreUnlock($user, $achievementTwo, Carbon::now()->subMinutes(10));
        $this->addHardcoreUnlock($user, $achievementThree, Carbon::now()->subDays(3));
        $this->addSoftcoreUnlock($user, $achievementFour, Carbon::now()->subMinutes(5));

        // points_weighted will get updated, so we should refresh our instances of the achievements.
        $achievementOne->refresh();
        $achievementTwo->refresh();
        $achievementThree->refresh();
        $achievementFour->refresh();

        // Act
        (new UpdatePlayerPointsStatsAction())->execute($user);

        // Assert
        $userStats = PlayerStat::where('user_id', $user->id)->get();

        // The user should have daily and weekly stats for softcore, hardcore, and weighted points.
        $this->assertCount(6, $userStats);

        $dailyHardcorePoints = $userStats->where('type', PlayerStatType::PointsHardcoreDay)->first();
        $this->assertEquals(20, $dailyHardcorePoints->value);

        $weeklyHardcorePoints = $userStats->where('type', PlayerStatType::PointsHardcoreWeek)->first();
        $this->assertEquals(30, $weeklyHardcorePoints->value);

        $dailyWeightedPoints = $userStats->where('type', PlayerStatType::PointsWeightedDay)->first();
        $this->assertEquals($achievementOne->points_weighted + $achievementTwo->points_weighted, $dailyWeightedPoints->value);

        $weeklyWeightedPoints = $userStats->where('type', PlayerStatType::PointsWeightedWeek)->first();
        $this->assertEquals(
            $achievementOne->points_weighted + $achievementTwo->points_weighted + $achievementThree->points_weighted,
            $weeklyWeightedPoints->value
        );

        $dailySoftcorePoints = $userStats->where('type', PlayerStatType::PointsSoftcoreDay)->first();
        $this->assertEquals(10, $dailySoftcorePoints->value);

        $weeklySoftcorePoints = $userStats->where('type', PlayerStatType::PointsSoftcoreWeek)->first();
        $this->assertEquals(10, $weeklySoftcorePoints->value);
    }

    public function testItUpdatesStatsCorrectly(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2023, 11, 18, 15, 0, 0));

        $user = User::factory()->create(); // Initially tracked
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $achievementOne = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);
        $achievementTwo = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);
        $achievementThree = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);
        $achievementFour = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);
        $achievementFive = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);

        $this->addHardcoreUnlock($user, $achievementOne, Carbon::now()->subMinutes(10));
        $this->addHardcoreUnlock($user, $achievementTwo, Carbon::now()->subMinutes(10));
        $this->addHardcoreUnlock($user, $achievementThree, Carbon::now()->subDays(3));
        $this->addSoftcoreUnlock($user, $achievementFour, Carbon::now()->subMinutes(5));
        $this->addHardcoreUnlock($user, $achievementFive, Carbon::now()->subMinutes(5));

        // points_weighted will get updated, so we should refresh our instances of the achievements.
        $achievementOne->refresh();
        $achievementTwo->refresh();
        $achievementThree->refresh();
        $achievementFour->refresh();
        $achievementFive->refresh();

        // Act
        (new UpdatePlayerPointsStatsAction())->execute($user);

        // Assert
        $userStats = PlayerStat::where('user_id', $user->id)->get();

        // The user should have daily and weekly stats for softcore, hardcore, and weighted points.
        $this->assertCount(6, $userStats);

        $dailyHardcorePoints = $userStats->where('type', PlayerStatType::PointsHardcoreDay)->first();
        $this->assertEquals(30, $dailyHardcorePoints->value);

        $weeklyHardcorePoints = $userStats->where('type', PlayerStatType::PointsHardcoreWeek)->first();
        $this->assertEquals(40, $weeklyHardcorePoints->value);

        $dailyWeightedPoints = $userStats->where('type', PlayerStatType::PointsWeightedDay)->first();
        $this->assertEquals(
            $achievementOne->points_weighted + $achievementTwo->points_weighted + $achievementFive->points_weighted,
            $dailyWeightedPoints->value
        );

        $weeklyWeightedPoints = $userStats->where('type', PlayerStatType::PointsWeightedWeek)->first();
        $this->assertEquals(

                $achievementOne->points_weighted
                + $achievementTwo->points_weighted
                + $achievementThree->points_weighted
                + $achievementFive->points_weighted,
            $weeklyWeightedPoints->value
        );
    }

    public function testItPurgesUntrackedUserStats(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2023, 11, 18, 15, 0, 0));

        $user = User::factory()->create(); // Initially tracked
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 100]);

        $this->addHardcoreUnlock($user, $achievement);

        (new UpdatePlayerPointsStatsAction())->execute($user);

        $user->Untracked = true;
        $user->unranked_at = Carbon::now();
        $user->save();

        // Act
        (new UpdatePlayerPointsStatsAction())->execute($user);

        // Assert
        $userStats = PlayerStat::where('user_id', $user->id)->get();
        $this->assertCount(0, $userStats);
    }

    public function testItSkipsUpdateWhenValueIsUnchanged(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2023, 11, 18, 15, 0, 0));

        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);

        $this->addHardcoreUnlock($user, $achievement, Carbon::now()->subMinutes(10));

        // Act
        // ... create the stats ...
        (new UpdatePlayerPointsStatsAction())->execute($user);

        // ... get the current updated_at timestamp ...
        $dailyHardcorePointsBefore = PlayerStat::where('user_id', $user->id)
            ->where('type', PlayerStatType::PointsHardcoreDay)
            ->first();
        $updatedAtBefore = $dailyHardcorePointsBefore->updated_at;

        // ... wait a second to ensure timestamp would change on update ...
        Carbon::setTestNow(Carbon::now()->addSeconds(1));

        // ... now execute again with the same data (this should be skipped) ...
        (new UpdatePlayerPointsStatsAction())->execute($user);

        // Assert
        $dailyHardcorePointsAfter = PlayerStat::where('user_id', $user->id)
            ->where('type', PlayerStatType::PointsHardcoreDay)
            ->first();

        $this->assertEquals($updatedAtBefore->toDateTimeString(), $dailyHardcorePointsAfter->updated_at->toDateTimeString());
        $this->assertEquals(10, $dailyHardcorePointsAfter->value);
    }

    public function testItDeletesStatsWhenPointsReachZero(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2023, 11, 18, 15, 0, 0)); // !! saturday

        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 50]);

        $this->addHardcoreUnlock($user, $achievement, Carbon::now()->subHours(2)); // !! earlier today

        // ... create initial stats ...
        (new UpdatePlayerPointsStatsAction())->execute($user);

        // ... verify stats exist for both day and week ...
        $initialStats = PlayerStat::where('user_id', $user->id)->get();
        $this->assertCount(4, $initialStats); // !! hardcore day, hardcore week, weighted day, weighted week
        $this->assertEquals(50, $initialStats->where('type', PlayerStatType::PointsHardcoreDay)->first()->value);
        $this->assertEquals(50, $initialStats->where('type', PlayerStatType::PointsHardcoreWeek)->first()->value);
        $this->assertNotNull($initialStats->where('type', PlayerStatType::PointsWeightedDay)->first());
        $this->assertNotNull($initialStats->where('type', PlayerStatType::PointsWeightedWeek)->first());

        // Act
        // ... move forward to the next day (Sunday) - daily stats should reset ...
        Carbon::setTestNow(Carbon::create(2023, 11, 19, 10, 0, 0));
        (new UpdatePlayerPointsStatsAction())->execute($user);

        // Assert
        $afterDayRollover = PlayerStat::where('user_id', $user->id)->get();

        // ... daily stats should be deleted ...
        $this->assertNull($afterDayRollover->where('type', PlayerStatType::PointsHardcoreDay)->first());
        $this->assertNull($afterDayRollover->where('type', PlayerStatType::PointsWeightedDay)->first());

        // ... weekly stats should still exist ...
        $this->assertEquals(50, $afterDayRollover->where('type', PlayerStatType::PointsHardcoreWeek)->first()->value);

        // ... move forward to the next week (Monday) - weekly stats should also reset ...
        Carbon::setTestNow(Carbon::create(2023, 11, 20, 10, 0, 0));
        (new UpdatePlayerPointsStatsAction())->execute($user);

        // ... all stats should now be deleted ...
        $afterWeekRollover = PlayerStat::where('user_id', $user->id)->get();
        $this->assertCount(0, $afterWeekRollover);
    }
}
