<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\BuildPlayerGameActivityDataAction;
use App\Platform\Data\PlayerGameActivityData;
use App\Platform\Services\PlayerGameActivityService;
use App\Platform\Services\UserAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildPlayerGameActivityDataActionTest extends TestCase
{
    use RefreshDatabase;

    private System $system;
    private Game $game;
    private User $user;
    private PlayerGame $playerGame;
    private BuildPlayerGameActivityDataAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system = System::factory()->create();
        $this->game = Game::factory()->create(['ConsoleID' => $this->system->id]);
        $this->user = User::factory()->create();
        $this->playerGame = PlayerGame::factory()->create([
            'user_id' => $this->user->id,
            'game_id' => $this->game->id,
        ]);

        $this->action = new BuildPlayerGameActivityDataAction(
            new PlayerGameActivityService(),
            new UserAgentService(),
        );
    }

    public function testExecuteReturnsCorrectDataStructure(): void
    {
        // Act
        $result = $this->action->execute($this->user, $this->game);

        // Assert
        $this->assertInstanceOf(PlayerGameActivityData::class, $result);
        $this->assertIsArray($result->sessions);
        $this->assertIsArray($result->clientBreakdown);
        $this->assertNotNull($result->summarizedActivity);
    }

    public function testExecuteIncludesAchievementData(): void
    {
        // Arrange
        Achievement::factory()->published()->count(3)->create([
            'GameID' => $this->game->id,
        ]);

        // ... create some unlocks ...
        foreach ($this->game->achievements as $index => $achievement) {
            // Create unlocks within the same hour to ensure they're grouped into one reconstructed session.
            $this->user->playerAchievements()->create([
                'achievement_id' => $achievement->id,
                'unlocked_at' => now()->subHour()->addMinutes($index * 10),
                'unlocked_hardcore_at' => now()->subHour()->addMinutes($index * 10),
            ]);
        }

        // Act
        $result = $this->action->execute($this->user, $this->game);

        // Assert
        $this->assertCount(1, $result->sessions); // !! one session for all achievements
        $session = $result->sessions[0];
        $this->assertCount(3, $session->events);

        foreach ($session->events as $event) {
            $this->assertNotNull($event->achievement);
            $this->assertNotNull($event->when);
            $this->assertNotNull($event->hardcore);
        }
    }

    public function testExecuteIncludesClientBreakdownData(): void
    {
        // Arrange
        $userAgent = 'RALibRetro/1.3.11 (WindowsNT 10.0) Integration/1.0.4.0';
        $this->user->playerSessions()->create([
            'game_id' => $this->game->id,
            'user_agent' => $userAgent,
            'duration' => 60, // 60 minutes
        ]);

        // Act
        $result = $this->action->execute($this->user, $this->game);

        // Assert
        $this->assertNotEmpty($result->clientBreakdown);

        $breakdown = $result->clientBreakdown[0];
        $this->assertEquals('RALibRetro (1.3.11)', $breakdown->clientIdentifier);
        $this->assertEquals([$userAgent], $breakdown->agents);
        $this->assertEquals(3600, $breakdown->duration); // 60 minutes in seconds
        $this->assertEquals(100.0, $breakdown->durationPercentage);
    }

    public function testExecuteCalculatesSummaryDataCorrectly(): void
    {
        // Arrange
        Achievement::factory()->published()->count(2)->create([
            'GameID' => $this->game->id,
        ]);

        $baseTime = now()->subHours(2);
        $timeBetweenUnlocks = 3600; // 1 hour in seconds

        // ... create some sequential unlocks ...
        foreach ($this->game->achievements as $index => $achievement) {
            $unlockTime = $baseTime->clone()->addSeconds($index * $timeBetweenUnlocks);
            $this->user->playerAchievements()->create([
                'achievement_id' => $achievement->id,
                'unlocked_at' => $unlockTime,
                'unlocked_hardcore_at' => $unlockTime,
            ]);
        }

        // Act
        $result = $this->action->execute($this->user, $this->game);

        // Assert
        $summary = $result->summarizedActivity;
        $this->assertEquals($timeBetweenUnlocks, $summary->totalUnlockTime);
        $this->assertEquals(1, $summary->achievementSessionCount);

        // Per achievement adjustment: 3600 seconds / 2 achievements = 1800 seconds per achievement
        // Total achievement playtime: 3600 (session duration) + (1800 * 1) (adjustment for reconstructed session)
        $expectedAchievementPlaytime = $timeBetweenUnlocks + ($timeBetweenUnlocks / 2);
        $this->assertEquals($expectedAchievementPlaytime, $summary->achievementPlaytime);
    }

    public function testExecuteCreatesMultipleSessionsForSpreadOutUnlocks(): void
    {
        // Arrange
        Achievement::factory()->published()->count(3)->create([
            'GameID' => $this->game->id,
        ]);

        $achievements = $this->game->achievements;

        // ... space out unlocks by 6 hours ...
        $this->user->playerAchievements()->create([
            'achievement_id' => $achievements[0]->id,
            'unlocked_at' => now()->subHours(12),
            'unlocked_hardcore_at' => now()->subHours(12),
        ]);
        $this->user->playerAchievements()->create([
            'achievement_id' => $achievements[1]->id,
            'unlocked_at' => now()->subHours(6),
            'unlocked_hardcore_at' => now()->subHours(6),
        ]);
        $this->user->playerAchievements()->create([
            'achievement_id' => $achievements[2]->id,
            'unlocked_at' => now()->subHour(),
            'unlocked_hardcore_at' => now()->subHour(),
        ]);

        // Act
        $result = $this->action->execute($this->user, $this->game);

        // Assert
        $this->assertCount(3, $result->sessions); // ... one session per achievement due to >4 hour time gaps

        foreach ($result->sessions as $session) {
            $this->assertCount(1, $session->events); // !! each session should have exactly one unlock.
            $event = $session->events[0];
            $this->assertNotNull($event->achievement);
            $this->assertNotNull($event->when);
            $this->assertTrue($event->hardcore);
        }
    }

    public function testExecuteHandlesEmptyActivityCorrectly(): void
    {
        // Act
        $result = $this->action->execute($this->user, $this->game);

        // Assert
        $this->assertEmpty($result->sessions);
        $this->assertEmpty($result->clientBreakdown);
        $this->assertEquals(0, $result->summarizedActivity->totalPlaytime);
        $this->assertEquals(0, $result->summarizedActivity->achievementPlaytime);
    }
}
