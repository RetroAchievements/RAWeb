<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Models\GameSet;
use App\Platform\Enums\GameSetType;
use App\Platform\Enums\GameSuggestionReason;
use App\Platform\Services\GameSuggestions\Strategies\SimilarGameStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimilarGameStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function testItSelectsSimilarGame(): void
    {
        // Arrange
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);
        $similarGame1 = Game::factory()->create(['achievements_published' => 10]);
        $similarGame2 = Game::factory()->create(['achievements_published' => 10]);
        $unrelatedGame = Game::factory()->create(['achievements_published' => 10]);

        // ... create a similar games set for the source game ...
        $similarGamesSet = GameSet::factory()->create([
            'type' => GameSetType::SimilarGames,
        ]);
        $similarGamesSet->games()->attach([
            $sourceGame->id,
            $similarGame1->id,
            $similarGame2->id,
        ]);

        // Act
        $strategy = new SimilarGameStrategy($sourceGame);
        $result = $strategy->select();

        // Assert
        $this->assertNotNull($result);

        $this->assertTrue(in_array($result->id, [$similarGame1->id, $similarGame2->id]));
        $this->assertNotEquals($sourceGame->id, $result->id);
        $this->assertNotEquals($unrelatedGame->id, $result->id);

        $this->assertEquals(GameSuggestionReason::SimilarGame, $strategy->reason());

        $context = $strategy->reasonContext();
        $this->assertNotNull($context);
        $this->assertEquals($sourceGame->id, $context->relatedGame->id);
    }

    public function testItReturnsNullWhenNoSimilarGamesExist(): void
    {
        // Arrange
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);

        // ... create a similar games set for the source game ...
        GameSet::factory()->create([
            'type' => GameSetType::SimilarGames,
        ]);
        // !! but nothing is attached

        // Act
        $strategy = new SimilarGameStrategy($sourceGame);
        $result = $strategy->select();

        // Assert
        $this->assertNull($result);
    }
}
