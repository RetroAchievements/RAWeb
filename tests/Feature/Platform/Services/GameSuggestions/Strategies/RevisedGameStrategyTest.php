<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services\GameSuggestions\Strategies;

use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Enums\GameSuggestionReason;
use App\Platform\Services\GameSuggestions\Strategies\RevisedGameStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevisedGameStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function testItSelectsGameThatWasPreviouslyMasteredButWasRevised(): void
    {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create(['achievements_published' => 10]);

        // ... user has a mastery badge for the game ...
        PlayerBadge::factory()->create([
            'user_id' => $user->id,
            'AwardType' => AwardType::Mastery,
            'AwardData' => $game->id,
        ]);

        // ... but their completion is now less than 100% due to revisions! ...
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 8,
            'achievements_total' => 10,
            'completion_percentage' => 0.8,
        ]);

        // Act
        $strategy = new RevisedGameStrategy($user);
        $result = $strategy->select();

        // Assert
        $this->assertNotNull($result);

        $this->assertEquals($game->id, $result->id);
        $this->assertEquals(GameSuggestionReason::Revised, $strategy->reason());
        $this->assertNull($strategy->reasonContext());
    }

    public function testItIgnoresGamesThatAreMasteredWithFullCompletion(): void
    {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create(['achievements_published' => 10]);

        // ... user has mastery badge AND 100% completion ...
        PlayerBadge::factory()->create([
            'user_id' => $user->id,
            'AwardType' => AwardType::Mastery,
            'AwardData' => $game->id,
        ]);

        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 10,
            'achievements_total' => 10,
            'completion_percentage' => 1.0,
        ]);

        // Act
        $strategy = new RevisedGameStrategy($user);
        $result = $strategy->select();

        // Assert
        $this->assertNull($result);
    }

    public function testItIgnoresGamesWithoutMasteryBadge(): void
    {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create(['achievements_published' => 10]);

        // ... user has incomplete progress but no mastery badge ...
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 8,
            'achievements_total' => 10,
            'completion_percentage' => 0.8,
        ]);

        // Act
        $strategy = new RevisedGameStrategy($user);
        $result = $strategy->select();

        // Assert
        $this->assertNull($result);
    }
}
