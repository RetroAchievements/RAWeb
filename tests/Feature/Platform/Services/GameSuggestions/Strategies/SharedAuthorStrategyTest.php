<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services\GameSuggestions\Strategies;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\GameSuggestionReason;
use App\Platform\Services\GameSuggestions\Enums\SourceGameKind;
use App\Platform\Services\GameSuggestions\Strategies\SharedAuthorStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedAuthorStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function testItSelectsGameBySharedAuthor(): void
    {
        // Arrange
        $author = User::factory()->create(['User' => 'Scott']);

        $sourceGame = Game::factory()->create(['achievements_published' => 10]);
        $authoredGame = Game::factory()->create(['achievements_published' => 10]);
        $unrelatedGame = Game::factory()->create(['achievements_published' => 10]);

        // ... create achievements by the same author ...
        Achievement::factory()->create([
            'game_id' => $sourceGame->id,
            'user_id' => $author->id,
            'is_promoted' => true,
        ]);
        Achievement::factory()->create([
            'game_id' => $authoredGame->id,
            'user_id' => $author->id,
            'is_promoted' => true,
        ]);
        Achievement::factory()->create([
            'game_id' => $unrelatedGame->id,
            'user_id' => User::factory()->create()->id,
            'is_promoted' => true,
        ]);

        // Act
        $strategy = new SharedAuthorStrategy(
            $sourceGame,
            sourceGameKind: SourceGameKind::Mastered,
        );
        $result = $strategy->select();

        // Assert
        $this->assertNotNull($result);

        $this->assertEquals($authoredGame->id, $result->id);
        $this->assertEquals(GameSuggestionReason::SharedAuthor, $strategy->reason());

        $context = $strategy->reasonContext();
        $this->assertNotNull($context);
        $this->assertEquals($author->username, $context->relatedAuthor->displayName);
    }

    public function testItReturnsNullWhenNoOtherAuthoredGamesExist(): void
    {
        // Arrange
        $author = User::factory()->create();
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);

        Achievement::factory()->create([
            'game_id' => $sourceGame->id,
            'user_id' => $author->id,
            'is_promoted' => true,
        ]);

        // Act
        $strategy = new SharedAuthorStrategy($sourceGame);
        $result = $strategy->select();

        // Assert
        $this->assertNull($result);
    }

    public function testItOnlyConsidersOfficialCoreAchievements(): void
    {
        // Arrange
        $author = User::factory()->create();

        $sourceGame = Game::factory()->create(['achievements_published' => 10]);
        $unofficialGame = Game::factory()->create(['achievements_published' => 10]);

        Achievement::factory()->create([
            'game_id' => $sourceGame->id,
            'user_id' => $author->id,
            'is_promoted' => true,
        ]);
        Achievement::factory()->create([
            'game_id' => $unofficialGame->id,
            'user_id' => $author->id,
            'is_promoted' => false, // !!
        ]);

        // Act
        $strategy = new SharedAuthorStrategy($sourceGame);
        $result = $strategy->select();

        // Assert
        $this->assertNull($result);
    }
}
