<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Platform\Enums\GameSuggestionReason;
use App\Platform\Services\GameSuggestions\Strategies\RandomGameStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RandomGameStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function testItSelectsRandomGameWithAchievements(): void
    {
        // Arrange
        $eligibleGame1 = Game::factory()->create(['achievements_published' => 10]);
        $eligibleGame2 = Game::factory()->create(['achievements_published' => 10]);

        // ... this game should never be picked ...
        Game::factory()->create(['achievements_published' => 0]);

        // Act
        $strategy = new RandomGameStrategy();
        $result = $strategy->select();

        // Assert
        $this->assertNotNull($result);

        $this->assertTrue(in_array($result->id, [$eligibleGame1->id, $eligibleGame2->id]));
        $this->assertEquals(GameSuggestionReason::Random, $strategy->reason());
        $this->assertNull($strategy->reasonContext());
    }

    public function testItReturnsNullWhenNoGamesWithAchievementsExist(): void
    {
        // Arrange
        Game::factory()->create(['achievements_published' => 0]);

        // Act
        $strategy = new RandomGameStrategy();
        $result = $strategy->select();

        // Assert
        $this->assertNull($result);
    }
}
