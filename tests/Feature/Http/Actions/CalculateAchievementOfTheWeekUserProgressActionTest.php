<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Actions;

use App\Http\Actions\CalculateAchievementOfTheWeekUserProgressAction;
use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateAchievementOfTheWeekUserProgressActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Event $event;
    private Game $eventGame;
    private CalculateAchievementOfTheWeekUserProgressAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->eventGame = Game::factory()->create(['Title' => 'Achievement of the Week']);
        $this->event = Event::factory()->create(['legacy_game_id' => $this->eventGame->id]);
        $this->action = new CalculateAchievementOfTheWeekUserProgressAction();
    }

    public function testItReturnsZeroProgressWhenNoCurrentWeek(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create();

        EventAchievement::create([
            'achievement_id' => $achievement->id,
            'source_achievement_id' => $achievement->id,
            'active_from' => now()->subWeeks(2),
            'active_until' => now()->subWeeks(1),
        ]);

        // Act
        $result = $this->action->execute($this->user, $this->event);

        // Assert
        $this->assertEquals(0, $result->streakLength);
        $this->assertFalse($result->hasCurrentWeek);
        $this->assertFalse($result->hasActiveStreak);
    }

    public function testItReturnsZeroProgressWhenNoAchievementsUnlocked(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create(['GameID' => $this->eventGame->ID]);

        EventAchievement::create([
            'achievement_id' => $achievement->id,
            'source_achievement_id' => $achievement->id,
            'active_from' => now()->subDays(3),
            'active_until' => now()->addDays(4),
        ]);

        // Act
        $result = $this->action->execute($this->user, $this->event);

        // Assert
        $this->assertEquals(0, $result->streakLength);
        $this->assertFalse($result->hasCurrentWeek);
        $this->assertFalse($result->hasActiveStreak);
    }

    public function testItReturnsCorrectProgressForCurrentWeekOnly(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create(['GameID' => $this->eventGame->id]);

        EventAchievement::create([
            'achievement_id' => $achievement->id,
            'source_achievement_id' => $achievement->id,
            'active_from' => now()->subDays(3),
            'active_until' => now()->addDays(4),
        ]);

        PlayerAchievement::factory()->create([
            'user_id' => $this->user->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => now(),
        ]);

        // Act
        $result = $this->action->execute($this->user, $this->event);

        // Assert
        $this->assertEquals(1, $result->streakLength);
        $this->assertTrue($result->hasCurrentWeek);
        $this->assertFalse($result->hasActiveStreak);
    }

    public function testItCalculatesStreakCorrectly(): void
    {
        // Arrange
        $achievements = Achievement::factory()
            ->count(3)
            ->create(['GameID' => $this->eventGame->id]);

        // Create three consecutive weeks
        EventAchievement::create([
            'achievement_id' => $achievements[0]->id,
            'source_achievement_id' => $achievements[0]->id,
            'active_from' => now()->subWeeks(2),
            'active_until' => now()->subWeeks(1),
        ]);
        EventAchievement::create([
            'achievement_id' => $achievements[1]->id,
            'source_achievement_id' => $achievements[1]->id,
            'active_from' => now()->subWeeks(1),
            'active_until' => now(),
        ]);
        EventAchievement::create([
            'achievement_id' => $achievements[2]->id,
            'source_achievement_id' => $achievements[2]->id,
            'active_from' => now(),
            'active_until' => now()->addWeeks(1),
        ]);

        // ... the user completed all three weeks ...
        foreach ($achievements as $achievement) {
            PlayerAchievement::factory()->create([
                'user_id' => $this->user->id,
                'achievement_id' => $achievement->id,
                'unlocked_at' => now(),
            ]);
        }

        // Act
        $result = $this->action->execute($this->user, $this->event);

        // Assert
        $this->assertEquals(3, $result->streakLength);
        $this->assertTrue($result->hasCurrentWeek);
        $this->assertTrue($result->hasActiveStreak);
    }

    public function testItBreaksStreakOnMissedWeek(): void
    {
        // Arrange
        $achievements = Achievement::factory()
            ->count(3)
            ->create(['GameID' => $this->eventGame->id]);

        // ... create three consecutive weeks ...
        EventAchievement::create([
            'achievement_id' => $achievements[0]->id,
            'source_achievement_id' => $achievements[0]->id,
            'active_from' => now()->subWeeks(2),
            'active_until' => now()->subWeeks(1),
        ]);
        EventAchievement::create([
            'achievement_id' => $achievements[1]->id,
            'source_achievement_id' => $achievements[1]->id,
            'active_from' => now()->subWeeks(1),
            'active_until' => now(),
        ]);
        EventAchievement::create([
            'achievement_id' => $achievements[2]->id,
            'source_achievement_id' => $achievements[2]->id,
            'active_from' => now(),
            'active_until' => now()->addWeeks(1),
        ]);

        // ... the user completed the first and current week, but missed the middle week ...
        PlayerAchievement::factory()->create([
            'user_id' => $this->user->id,
            'achievement_id' => $achievements[0]->id,
            'unlocked_at' => now(),
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $this->user->id,
            'achievement_id' => $achievements[2]->id,
            'unlocked_at' => now(),
        ]);

        // Act
        $result = $this->action->execute($this->user, $this->event);

        // Assert
        $this->assertEquals(1, $result->streakLength);
        $this->assertTrue($result->hasCurrentWeek);
        $this->assertFalse($result->hasActiveStreak);
    }

    public function testItExcludesMonthlyAchievements(): void
    {
        // Arrange
        $weeklyAchievement = Achievement::factory()->create(['GameID' => $this->eventGame->id]);
        $monthlyAchievement = Achievement::factory()->create(['GameID' => $this->eventGame->id]);

        // ... create one weekly and one monthly achievement ...
        EventAchievement::create([
            'achievement_id' => $weeklyAchievement->id,
            'source_achievement_id' => $weeklyAchievement->id,
            'active_from' => now()->subDays(3),
            'active_until' => now()->addDays(4),
        ]);
        EventAchievement::create([
            'achievement_id' => $monthlyAchievement->id,
            'source_achievement_id' => $monthlyAchievement->id,
            'active_from' => now()->subDays(15),
            'active_until' => now()->addDays(15),
        ]);

        // ... the user completed both achievements ...
        PlayerAchievement::factory()->create([
            'user_id' => $this->user->id,
            'achievement_id' => $weeklyAchievement->id,
            'unlocked_at' => now(),
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $this->user->id,
            'achievement_id' => $monthlyAchievement->id,
            'unlocked_at' => now(),
        ]);

        // Act
        $result = $this->action->execute($this->user, $this->event);

        // Assert
        $this->assertEquals(1, $result->streakLength); // !! monthly is excluded
        $this->assertTrue($result->hasCurrentWeek);
        $this->assertFalse($result->hasActiveStreak);
    }
}
