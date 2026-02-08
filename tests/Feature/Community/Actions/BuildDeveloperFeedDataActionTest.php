<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\BuildDeveloperFeedDataAction;
use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\AchievementMaintainer;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildDeveloperFeedDataActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsEmptyDataWhenDeveloperHasNoContent(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 100]);

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertEquals(0, $result->activePlayers->total);
        $this->assertEquals(0, $result->awardsContributed);
        $this->assertEquals(0, $result->leaderboardEntriesContributed);
        $this->assertCount(0, $result->recentUnlocks);
        $this->assertCount(0, $result->recentPlayerBadges);
        $this->assertCount(0, $result->recentLeaderboardEntries);
    }

    public function testItCountsAwardsAcrossAllGames(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 100]);
        $system = System::factory()->create();

        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);

        Achievement::factory()->count(5)->promoted()->create([
            'game_id' => $game1->id,
            'user_id' => $developer->id,
        ]);
        Achievement::factory()->count(3)->promoted()->create([
            'game_id' => $game2->id,
            'user_id' => $developer->id,
        ]);

        PlayerBadge::factory()->count(2)->create([
            'award_type' => AwardType::Mastery,
            'award_key' => $game1->id,
            'award_tier' => UnlockMode::Softcore,
        ]);
        PlayerBadge::factory()->create([
            'award_type' => AwardType::Mastery,
            'award_key' => $game1->id,
            'award_tier' => UnlockMode::Hardcore,
        ]);
        PlayerBadge::factory()->count(2)->create([
            'award_type' => AwardType::GameBeaten,
            'award_key' => $game2->id,
            'award_tier' => UnlockMode::Softcore,
        ]);

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertEquals(2, $result->awardsContributed);
    }

    public function testItCountsLeaderboardEntries(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 100]);
        $system = System::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
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
        $developer = User::factory()->create(['yield_unlocks' => 100]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
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
        $this->assertCount(1, $result->recentUnlocks);
    }

    public function testItFetchesAllRecentUnlocksForLargeContributors(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 25000]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
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
        $this->assertCount(5, $result->recentUnlocks);
    }

    public function testItExcludesUntrackedPlayersFromUnlocks(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 100]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        $untrackedUser = User::factory()->create(['unranked_at' => now()]);
        $trackedUser = User::factory()->create();

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
        $this->assertCount(1, $result->recentUnlocks);
        $this->assertEquals($trackedUser->id, $result->recentUnlocks[0]->user->id->resolve());
    }

    public function testItExcludesUntrackedPlayersFromLeaderboardEntries(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 100]);
        $system = System::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'author_id' => $developer->id,
        ]);

        $players = User::factory()->count(3)->create();
        foreach ($players as $player) {
            LeaderboardEntry::factory()->create([
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $player->id,
            ]);
        }

        $players[1]->unranked_at = now();
        $players[1]->save();

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertEquals(3, $result->leaderboardEntriesContributed); // countLeaderboardEntries doesn't currently join to users to filter out untracked users.
        $this->assertCount(2, $result->recentLeaderboardEntries);
        $this->assertEquals($players[2]->id, $result->recentLeaderboardEntries[0]->user->id->resolve());
        $this->assertEquals($players[0]->id, $result->recentLeaderboardEntries[1]->user->id->resolve());
    }

    public function testItExcludesDeletedLeaderboardsFromLeaderboardEntries(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 100]);
        $system = System::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $leaderboard1 = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'author_id' => $developer->id,
        ]);
        $leaderboard2 = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'author_id' => $developer->id,
        ]);

        $players = User::factory()->count(3)->create();
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard1->id,
            'user_id' => $players[0]->id,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard2->id,
            'user_id' => $players[1]->id,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard1->id,
            'user_id' => $players[2]->id,
        ]);

        $leaderboard2->delete();

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertEquals(3, $result->leaderboardEntriesContributed); // countLeaderboardEntries doesn't currently join to users to filter out untracked users.
        $this->assertCount(2, $result->recentLeaderboardEntries);
        $this->assertEquals($players[2]->id, $result->recentLeaderboardEntries[0]->user->id->resolve());
        $this->assertEquals($players[0]->id, $result->recentLeaderboardEntries[1]->user->id->resolve());
    }

    public function testItIncludesMaintainedAchievementsInRecentUnlocks(): void
    {
        // Arrange
        $author = User::factory()->create(['yield_unlocks' => 100]);
        $maintainer = User::factory()->create(['yield_unlocks' => 50]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $author->id,
        ]);
        AchievementMaintainer::create([
            'achievement_id' => $achievement->id,
            'user_id' => $maintainer->id,
            'effective_from' => now()->subDay(),
            'is_active' => true,
        ]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'achievement_id' => $achievement->id,
            'user_id' => $player->id,
            'unlocked_at' => now()->subDays(5),
        ]);

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($maintainer);

        // Assert
        $this->assertCount(1, $result->recentUnlocks);
        $this->assertEquals($achievement->id, $result->recentUnlocks[0]->achievement->id);
    }

    public function testItDoesNotCountMaintainedGamesInAwardsContributed(): void
    {
        // Arrange
        $author = User::factory()->create(['yield_unlocks' => 100]);
        $maintainer = User::factory()->create(['yield_unlocks' => 50]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievements = Achievement::factory()->count(3)->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $author->id,
        ]);
        AchievementMaintainer::create([
            'achievement_id' => $achievements->first()->id,
            'user_id' => $maintainer->id,
            'effective_from' => now()->subDay(),
            'is_active' => true,
        ]);

        PlayerBadge::factory()->create([
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
        ]);

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($maintainer);

        // Assert
        $this->assertEquals(0, $result->awardsContributed);
        $this->assertCount(0, $result->recentPlayerBadges);
    }

    public function testItDoesNotDuplicateAchievementsWhenUserIsAuthorAndMaintainer(): void
    {
        // Arrange
        $developer = User::factory()->create(['yield_unlocks' => 100]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);
        AchievementMaintainer::create([
            'achievement_id' => $achievement->id,
            'user_id' => $developer->id,
            'effective_from' => now()->subDay(),
            'is_active' => true,
        ]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'achievement_id' => $achievement->id,
            'user_id' => $player->id,
            'unlocked_at' => now()->subDays(5),
        ]);

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($developer);

        // Assert
        $this->assertCount(1, $result->recentUnlocks);
    }

    public function testItExcludesInactiveMaintainerAchievements(): void
    {
        // Arrange
        $author = User::factory()->create(['yield_unlocks' => 100]);
        $formerMaintainer = User::factory()->create(['yield_unlocks' => 50]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $author->id,
        ]);
        AchievementMaintainer::create([
            'achievement_id' => $achievement->id,
            'user_id' => $formerMaintainer->id,
            'effective_from' => now()->subDays(30),
            'effective_until' => now()->subDays(10),
            'is_active' => false,
        ]);

        $player = User::factory()->create();
        PlayerAchievement::factory()->create([
            'achievement_id' => $achievement->id,
            'user_id' => $player->id,
            'unlocked_at' => now()->subDays(5),
        ]);

        // Act
        $result = (new BuildDeveloperFeedDataAction())->execute($formerMaintainer);

        // Assert
        $this->assertCount(0, $result->recentUnlocks);
    }
}
