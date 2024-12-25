<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\BuildDeveloperFeedDataAction;
use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildDeveloperFeedDataActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsEmptyDataWhenDeveloperHasNoContent(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 100]);

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertEquals(0, $result->activePlayers->total);
        $this->assertEquals(0, $result->awardsContributed);
        $this->assertEquals(0, $result->leaderboardEntriesContributed);
        $this->assertEquals(0, count($result->recentUnlocks));
        $this->assertEquals(0, count($result->recentPlayerBadges));
        $this->assertEquals(0, count($result->recentLeaderboardEntries));
    }

    public function testItCountsAwardsAcrossAllGames(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 100]);
        $system = System::factory()->create();

        $game1 = Game::factory()->create(['ConsoleID' => $system->id]);
        $game2 = Game::factory()->create(['ConsoleID' => $system->id]);

        Achievement::factory()->count(5)->create([
            'GameID' => $game1->id,
            'user_id' => $developer->id,
            'Flags' => AchievementFlag::OfficialCore->value,
        ]);
        Achievement::factory()->count(3)->create([
            'GameID' => $game2->id,
            'user_id' => $developer->id,
            'Flags' => AchievementFlag::OfficialCore->value,
        ]);

        PlayerBadge::factory()->count(2)->create([
            'AwardType' => AwardType::Mastery,
            'AwardData' => $game1->id,
            'AwardDataExtra' => UnlockMode::Softcore,
        ]);
        PlayerBadge::factory()->create([
            'AwardType' => AwardType::Mastery,
            'AwardData' => $game1->id,
            'AwardDataExtra' => UnlockMode::Hardcore,
        ]);
        PlayerBadge::factory()->count(2)->create([
            'AwardType' => AwardType::GameBeaten,
            'AwardData' => $game2->id,
            'AwardDataExtra' => UnlockMode::Softcore,
        ]);

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertEquals(3, $result->awardsContributed);
    }

    public function testItCountsLeaderboardEntries(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 100]);
        $system = System::factory()->create();

        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'GameID' => $game->id,
            'author_id' => $developer->id,
        ]);

        $players = User::factory()->count(3)->create();
        foreach ($players as $player) {
            LeaderboardEntry::factory()->create([
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $player->id,
            ]);
        }

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertEquals(3, $result->leaderboardEntriesContributed);
    }

    public function testItFetchesRecentUnlocksWithinThirtyDaysForSmallContributors(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 100]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'user_id' => $developer->id,
            'Flags' => AchievementFlag::OfficialCore->value,
        ]);

        $players = User::factory()->count(2)->create();
        PlayerAchievement::factory()->create([
            'achievement_id' => $achievement->id,
            'user_id' => $players[0]->id,
            'unlocked_at' => now()->subDays(10),
        ]);
        PlayerAchievement::factory()->create([
            'achievement_id' => $achievement->id,
            'user_id' => $players[1]->id,
            'unlocked_at' => now()->subDays(40),
        ]);

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertEquals(1, count($result->recentUnlocks));
    }

    public function testItFetchesAllRecentUnlocksForLargeContributors(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 25000]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'user_id' => $developer->id,
            'Flags' => AchievementFlag::OfficialCore->value,
        ]);

        $players = User::factory()->count(5)->create();
        foreach ($players as $index => $player) {
            PlayerAchievement::factory()->create([
                'achievement_id' => $achievement->id,
                'user_id' => $player->id,
                'unlocked_at' => $index < 3
                    ? now()->subDays(10)
                    : now()->subDays(40),
            ]);
        }

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertEquals(5, count($result->recentUnlocks));
    }

    public function testItExcludesUntrackedPlayersFromUnlocks(): void
    {
        // Arrange
        $developer = User::factory()->create(['ContribCount' => 100]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'user_id' => $developer->id,
            'Flags' => AchievementFlag::OfficialCore->value,
        ]);

        $untrackedUser = User::factory()->create(['Untracked' => 1]);
        $trackedUser = User::factory()->create(['Untracked' => 0]);

        PlayerAchievement::factory()->create([
            'achievement_id' => $achievement->id,
            'user_id' => $untrackedUser->id,
            'unlocked_at' => now(),
        ]);
        PlayerAchievement::factory()->create([
            'achievement_id' => $achievement->id,
            'user_id' => $trackedUser->id,
            'unlocked_at' => now(),
        ]);

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertEquals(1, count($result->recentUnlocks));
        $this->assertEquals($trackedUser->id, $result->recentUnlocks[0]->user->id->resolve());
    }
}
