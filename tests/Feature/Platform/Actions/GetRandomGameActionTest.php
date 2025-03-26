<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\GetRandomGameAction;
use App\Platform\Enums\GameListType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetRandomGameActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsRandomGameFromList(): void
    {
        // Arrange
        $user = User::factory()->create();

        $system = System::factory()->create();
        $games = Game::factory()->count(5)->create(['ConsoleID' => $system->id]);

        $gameIds = $games->pluck('id')->toArray();

        // Act
        $randomGame = (new GetRandomGameAction())->execute(GameListType::AllGames, $user, []);

        // Assert
        $this->assertNotNull($randomGame->id);
        $this->assertContains($randomGame->id, $gameIds);
    }
}
