<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services\GameSuggestions;

use App\Community\Actions\AddGameToListAction;
use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Enums\GameSetType;
use App\Platform\Services\GameSuggestions\GameSuggestionEngine;
use App\Platform\Services\GameSuggestions\Strategies\SimilarGameStrategy;
use App\Platform\Services\GameSuggestions\Strategies\WantToPlayStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSuggestionEngineTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsUserSpecificSuggestionsWithinLimit(): void
    {
        // Arrange
        $user = User::factory()->create();
        $games = Game::factory()->count(5)->create([
            'achievements_published' => 10,
        ]);

        $addGameToListAction = new AddGameToListAction();
        foreach ($games as $game) {
            $addGameToListAction->execute($user, $game, UserGameListType::Play);
        }

        // Act
        $engine = new GameSuggestionEngine($user);
        $engine->dangerouslySetFixedStrategyForTesting(
            new WantToPlayStrategy($user)
        );
        $suggestions = $engine->selectSuggestions(limit: 3);

        // Assert
        $this->assertCount(3, $suggestions);

        foreach ($suggestions as $suggestion) {
            $this->assertTrue(in_array($suggestion->gameId, $games->pluck('id')->all()));
        }
    }

    public function testItReturnsGameSpecificSuggestions(): void
    {
        // Arrange
        $user = User::factory()->create();
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);
        $similarGames = Game::factory()->count(3)->create(['achievements_published' => 10]);
        $unrelatedGame = Game::factory()->create(['achievements_published' => 10]);

        // ... attach similar games to the source game ...
        $similarGamesSet = GameSet::factory()->create([
            'type' => GameSetType::SimilarGames,
            'game_id' => $sourceGame->id,
        ]);
        $similarGamesSet->games()->attach($similarGames->pluck('id')->all());

        // Act
        $engine = new GameSuggestionEngine($user, $sourceGame);
        $engine->dangerouslySetFixedStrategyForTesting(
            new SimilarGameStrategy($sourceGame, attachContext: false)
        );
        $suggestions = $engine->selectSuggestions(limit: 2);

        // Assert
        $this->assertNotEmpty($suggestions);
        $this->assertLessThanOrEqual(2, count($suggestions));

        foreach ($suggestions as $suggestion) {
            $this->assertTrue(in_array($suggestion->gameId, $similarGames->pluck('id')->all()));

            $this->assertNotEquals($sourceGame->id, $suggestion->gameId);
            $this->assertNotEquals($unrelatedGame->id, $suggestion->gameId);
        }
    }

    public function testItEnforcesUniqueSuggestions(): void
    {
        // Arrange
        $user = User::factory()->create();
        $games = Game::factory()->count(3)->create([
            'achievements_published' => 10,
        ]);

        // ... add some games to the user's Want to Play Games list ...
        $addGameToListAction = new AddGameToListAction();
        foreach ($games as $game) {
            $addGameToListAction->execute($user, $game, UserGameListType::Play);
        }

        // ... create a mastered game that should be excluded ...
        $masteredGame = Game::factory()->create(['achievements_published' => 10]);
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $masteredGame->id,
            'achievements_unlocked' => 10,
            'achievements_total' => 10,
        ]);

        // Act
        $engine = new GameSuggestionEngine($user);
        $suggestions = $engine->selectSuggestions(limit: 5); // we'll ask for 5 but expect fewer

        // Assert
        $this->assertNotEmpty($suggestions);
        $this->assertLessThanOrEqual(3, count($suggestions));

        $gameIds = array_map(fn ($suggestion) => $suggestion->gameId, $suggestions);
        $this->assertEquals(count($suggestions), count(array_unique($gameIds)));  // !! all suggestions are unique
        $this->assertNotContains($masteredGame->id, $gameIds); // !! mastered game is excluded
    }
}
