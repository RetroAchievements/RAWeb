<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services\GameSuggestions\Strategies;

use App\Community\Actions\AddGameToListAction;
use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\GameSuggestionReason;
use App\Platform\Services\GameSuggestions\Strategies\WantToPlayStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WantToPlayStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function testItSelectsGameFromWantToPlayList(): void
    {
        // Arrange
        $user = User::factory()->create();
        $game1 = Game::factory()->create(['Title' => 'Game 1', 'achievements_published' => 6]);
        $game2 = Game::factory()->create(['Title' => 'Game 2', 'achievements_published' => 6]);

        $addGameToListAction = new AddGameToListAction();
        $addGameToListAction->execute($user, $game1, UserGameListType::Play);
        $addGameToListAction->execute($user, $game2, UserGameListType::Play);

        // Act
        $strategy = new WantToPlayStrategy($user);
        $result = $strategy->select();

        // Assert
        $this->assertTrue(in_array($result->id, [$game1->id, $game2->id]));
        $this->assertEquals(GameSuggestionReason::WantToPlay, $strategy->reason());
        $this->assertNull($strategy->reasonContext());
    }

    public function testItReturnsNullWhenWantToPlayListIsEmpty(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $strategy = new WantToPlayStrategy($user);
        $result = $strategy->select();

        // Assert
        $this->assertNull($result);
    }
}
