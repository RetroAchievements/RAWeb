<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Controllers;

use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementOfTheWeekControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createCurrentAchievementOfTheWeek(array $eventAchievementAttributes = [], array $eventAttributes = []): EventAchievement
    {
        System::factory()->create(['id' => System::Events]);
        $eventGame = Game::factory()->create([
            'system_id' => System::Events,
            'title' => 'Achievement of the Week',
        ]);
        $mirrorAchievement = Achievement::factory()->promoted()->create(['game_id' => $eventGame->id]);
        $sourceAchievement = Achievement::factory()->create();

        Event::factory()->create([
            'legacy_game_id' => $eventGame->id,
            'active_from' => now()->subDay(),
            ...$eventAttributes,
        ]);

        return EventAchievement::factory()->create([
            'achievement_id' => $mirrorAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
            'active_from' => now()->subDay(),
            'active_until' => now()->addDays(6),
            'decorator' => 'Week 29',
            ...$eventAchievementAttributes,
        ]);
    }

    public function testItRequiresAuthentication(): void
    {
        // Act
        $response = $this->getJson('/api/v2/event-achievements/achievement-of-the-week');

        // Assert
        $response->assertUnauthorized();
    }

    public function testItReturnsTheCurrentAchievementOfTheWeek(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);
        $eventAchievement = $this->createCurrentAchievementOfTheWeek();
        $eventAchievement->load('event');

        // Act
        $response = $this->getJson('/api/v2/event-achievements/achievement-of-the-week', ['X-API-Key' => 'test-api-key']);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('links.self', 'http://localhost/api/v2/event-achievements/achievement-of-the-week');
        $response->assertJsonPath('meta.eventAchievement.id', (string) $eventAchievement->id);
        $response->assertJsonPath('meta.eventAchievement.achievementId', (string) $eventAchievement->achievement_id);
        $response->assertJsonPath('meta.eventAchievement.sourceAchievementId', (string) $eventAchievement->source_achievement_id);
        $response->assertJsonPath('meta.eventAchievement.eventId', (string) $eventAchievement->event->id);
        $response->assertJsonPath('meta.eventAchievement.activeFrom', $eventAchievement->active_from->toISOString());
        $response->assertJsonPath('meta.eventAchievement.activeUntil', $eventAchievement->active_until->toISOString());
        $response->assertJsonPath('meta.eventAchievement.activeThrough', $eventAchievement->active_through->toISOString());
        $response->assertJsonPath('meta.eventAchievement.decorator', 'Week 29');
        $this->assertTrue($eventAchievement->active_through->isSameDay($eventAchievement->active_until->subDay()));
    }

    public function testItReturns404WhenNoAchievementOfTheWeekIsActive(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);

        // Act
        $response = $this->getJson('/api/v2/event-achievements/achievement-of-the-week', ['X-API-Key' => 'test-api-key']);

        // Assert
        $response
            ->assertNotFound()
            ->assertJsonPath('errors.0.status', '404')
            ->assertJsonPath('errors.0.title', 'Not Found')
            ->assertJsonPath('errors.0.detail', 'There is no active Achievement of the Week.');
    }

    public function testItReturns404WhenTheAchievementOfTheWeekIsInThePast(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);
        $this->createCurrentAchievementOfTheWeek([
            'active_from' => now()->subWeeks(2),
            'active_until' => now()->subWeek(),
        ]);

        // Act
        $response = $this->getJson('/api/v2/event-achievements/achievement-of-the-week', ['X-API-Key' => 'test-api-key']);

        // Assert
        $response->assertNotFound();
    }

    public function testItReturns404ForAnUnassignedPlaceholderWeek(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);
        $this->createCurrentAchievementOfTheWeek(['source_achievement_id' => null]);

        // Act
        $response = $this->getJson('/api/v2/event-achievements/achievement-of-the-week', ['X-API-Key' => 'test-api-key']);

        // Assert
        $response->assertNotFound();
    }

    public function testItIgnoresTheCurrentAchievementOfTheMonth(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);
        $weeklyAchievement = $this->createCurrentAchievementOfTheWeek();
        $monthlyMirror = Achievement::factory()->promoted()->create(['game_id' => $weeklyAchievement->achievement->game_id]);

        EventAchievement::factory()->create([
            'achievement_id' => $monthlyMirror->id,
            'source_achievement_id' => Achievement::factory()->create()->id,
            'active_from' => now()->subDay(),
            'active_until' => now()->addDays(29),
        ]);

        // Act
        $response = $this->getJson('/api/v2/event-achievements/achievement-of-the-week', ['X-API-Key' => 'test-api-key']);

        // Assert
        $response
            ->assertOk()
            ->assertJsonPath('meta.eventAchievement.id', (string) $weeklyAchievement->id);
    }

    public function testItReturns404WhenTheMirrorAchievementIsUnpromoted(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);
        $eventAchievement = $this->createCurrentAchievementOfTheWeek();
        $eventAchievement->achievement->update(['is_promoted' => false]);

        // Act
        $response = $this->getJson('/api/v2/event-achievements/achievement-of-the-week', ['X-API-Key' => 'test-api-key']);

        // Assert
        $response->assertNotFound();
    }

    public function testItReturns404WhenTheAchievementOfTheWeekEventIsNotPublished(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-api-key']);
        $this->createCurrentAchievementOfTheWeek([], ['active_from' => now()->addDay()]);

        // Act
        $response = $this->getJson('/api/v2/event-achievements/achievement-of-the-week', ['X-API-Key' => 'test-api-key']);

        // Assert
        $response->assertNotFound();
    }
}
