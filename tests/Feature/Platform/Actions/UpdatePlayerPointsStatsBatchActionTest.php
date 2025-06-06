<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerStat;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpdatePlayerPointsStatsBatchAction;
use App\Platform\Enums\PlayerStatType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UpdatePlayerPointsStatsBatchActionTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testItProcessesMultipleUsersInBatch(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2023, 11, 18, 15, 0, 0));

        $users = User::factory()->count(3)->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        // ... user 1 has 2 hardcore achievements ...
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 20]);
        $this->addHardcoreUnlock($users[0], $achievement1, Carbon::now()->subHours(2));
        $this->addHardcoreUnlock($users[0], $achievement2, Carbon::now()->subHours(1));

        // ... user 2 has 1 softcore achievement ...
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 15]);
        $this->addSoftcoreUnlock($users[1], $achievement3, Carbon::now()->subMinutes(30));

        // ... user 3 has a mix of hardcore and softcore achievements ...
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 5]);
        $achievement5 = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 25]);
        $this->addHardcoreUnlock($users[2], $achievement4, Carbon::now()->subDays(2));
        $this->addSoftcoreUnlock($users[2], $achievement5, Carbon::now()->subHours(3));

        // Act
        (new UpdatePlayerPointsStatsBatchAction())->execute($users->pluck('id')->toArray());

        $user1Stats = PlayerStat::where('user_id', $users[0]->id)->get();
        $this->assertEquals(30, $user1Stats->where('type', PlayerStatType::PointsHardcoreDay)->first()->value);
        $this->assertEquals(30, $user1Stats->where('type', PlayerStatType::PointsHardcoreWeek)->first()->value);

        $user2Stats = PlayerStat::where('user_id', $users[1]->id)->get();
        $this->assertEquals(15, $user2Stats->where('type', PlayerStatType::PointsSoftcoreDay)->first()->value);
        $this->assertEquals(15, $user2Stats->where('type', PlayerStatType::PointsSoftcoreWeek)->first()->value);

        $user3Stats = PlayerStat::where('user_id', $users[2]->id)->get();
        $this->assertEquals(25, $user3Stats->where('type', PlayerStatType::PointsSoftcoreDay)->first()->value);
        $this->assertEquals(25, $user3Stats->where('type', PlayerStatType::PointsSoftcoreWeek)->first()->value);
        $this->assertNull($user3Stats->where('type', PlayerStatType::PointsHardcoreDay)->first()); // Outside day window.
        $this->assertEquals(5, $user3Stats->where('type', PlayerStatType::PointsHardcoreWeek)->first()->value);
    }

    public function testItHandlesUntrackedUsersInBatch(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2023, 11, 18, 15, 0, 0));

        $trackedUser = User::factory()->create();
        $untrackedUser = User::factory()->create(['Untracked' => true, 'unranked_at' => Carbon::now()]);

        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 100]);

        // ... both users have hardcore achievements ...
        $this->addHardcoreUnlock($trackedUser, $achievement);
        $this->addHardcoreUnlock($untrackedUser, $achievement);

        // ... simulate existing stats for an untracked user that should be cleared ...
        PlayerStat::create([
            'user_id' => $untrackedUser->id,
            'type' => PlayerStatType::PointsHardcoreDay,
            'value' => 100,
        ]);

        // Act
        (new UpdatePlayerPointsStatsBatchAction())->execute([$trackedUser->id, $untrackedUser->id]);

        // Assert
        $trackedStats = PlayerStat::where('user_id', $trackedUser->id)->get();
        $this->assertGreaterThan(0, $trackedStats->count());

        $untrackedStats = PlayerStat::where('user_id', $untrackedUser->id)->get();
        $this->assertCount(0, $untrackedStats);
    }

    public function testItSkipsUnchangedStatsInBatch(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2023, 11, 18, 15, 0, 0));

        $users = User::factory()->count(2)->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 10]);

        $this->addHardcoreUnlock($users[0], $achievement, Carbon::now()->subMinutes(10));
        $this->addHardcoreUnlock($users[1], $achievement, Carbon::now()->subMinutes(10));

        // Act
        // ... create some stats ...
        (new UpdatePlayerPointsStatsBatchAction())->execute($users->pluck('id')->toArray());

        // ... get the current updated_at timestamps ...
        $user1StatBefore = PlayerStat::where('user_id', $users[0]->id)
            ->where('type', PlayerStatType::PointsHardcoreDay)
            ->first();
        $user2StatBefore = PlayerStat::where('user_id', $users[1]->id)
            ->where('type', PlayerStatType::PointsHardcoreDay)
            ->first();

        // ... wait a second to ensure timestamps would change on an update ...
        Carbon::setTestNow(Carbon::now()->addSeconds(1));

        // ... second execution with same data should skip updates ...
        (new UpdatePlayerPointsStatsBatchAction())->execute($users->pluck('id')->toArray());

        // Assert
        // ... the updated_at values should remain the same ...
        $user1StatAfter = PlayerStat::where('user_id', $users[0]->id)
            ->where('type', PlayerStatType::PointsHardcoreDay)
            ->first();
        $user2StatAfter = PlayerStat::where('user_id', $users[1]->id)
            ->where('type', PlayerStatType::PointsHardcoreDay)
            ->first();

        $this->assertEquals($user1StatBefore->updated_at->toDateTimeString(), $user1StatAfter->updated_at->toDateTimeString());
        $this->assertEquals($user2StatBefore->updated_at->toDateTimeString(), $user2StatAfter->updated_at->toDateTimeString());
    }
}
