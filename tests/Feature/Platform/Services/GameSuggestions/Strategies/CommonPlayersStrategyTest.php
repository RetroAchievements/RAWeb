<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Enums\GameSuggestionReason;
use App\Platform\Services\GameSuggestions\Strategies\CommonPlayersStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommonPlayersStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function testItSelectsGameMasteredByCommonPlayers(): void
    {
        // Arrange
        $user = User::factory()->create();
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);
        $commonGame = Game::factory()->create(['achievements_published' => 10]);
        $unrelatedGame = Game::factory()->create(['achievements_published' => 10]);

        // ... create some users who mastered both the source game and the common game ...
        $masterUsers = User::factory()->count(5)->create();
        foreach ($masterUsers as $masterUser) {
            PlayerGame::factory()->create([
                'user_id' => $masterUser->id,
                'game_id' => $sourceGame->id,
                'achievements_unlocked' => 10,
                'achievements_total' => 10,
            ]);
            PlayerGame::factory()->create([
                'user_id' => $masterUser->id,
                'game_id' => $commonGame->id,
                'achievements_unlocked' => 10,
                'achievements_total' => 10,
            ]);
        }

        // ... create a user who only mastered the unrelated game ...
        $unrelatedUser = User::factory()->create();
        PlayerGame::factory()->create([
            'user_id' => $unrelatedUser->id,
            'game_id' => $unrelatedGame->id,
            'achievements_unlocked' => 10,
            'achievements_total' => 10,
        ]);

        // Act
        $strategy = new CommonPlayersStrategy($user, $sourceGame);
        $result = $strategy->select();

        // Assert
        $this->assertNotNull($result);

        $this->assertEquals($commonGame->id, $result->id);
        $this->assertEquals(GameSuggestionReason::CommonPlayers, $strategy->reason());
        $this->assertNull($strategy->reasonContext());
    }

    public function testItReturnsNullWhenNoCommonPlayersExist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);

        // ... create a player who only mastered the source game ...
        $masterUser = User::factory()->create();
        PlayerGame::factory()->create([
            'user_id' => $masterUser->id,
            'game_id' => $sourceGame->id,
            'achievements_unlocked' => 10,
            'achievements_total' => 10,
        ]);

        // Act
        $strategy = new CommonPlayersStrategy($user, $sourceGame);
        $result = $strategy->select();

        // Assert
        $this->assertNull($result);
    }

    public function testItExcludesTheRequestingUserFromCommonPlayers(): void
    {
        // Arrange
        $user = User::factory()->create();
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);
        $otherGame = Game::factory()->create(['achievements_published' => 10]);

        // ... the requesting user has mastered both games ...
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $sourceGame->id,
            'achievements_unlocked' => 10,
            'achievements_total' => 10,
        ]);
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $otherGame->id,
            'achievements_unlocked' => 10,
            'achievements_total' => 10,
        ]);

        // Act
        $strategy = new CommonPlayersStrategy($user, $sourceGame);
        $result = $strategy->select();

        // Assert
        $this->assertNull($result);
    }
}
