<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\BuildTrendingGamesAction;
use App\Community\Data\TrendingGameData;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildTrendingGamesActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsEmptyDataWhenNoActivePlayers(): void
    {
        // Act
        $result = (new BuildTrendingGamesAction())->execute();

        // Assert
        $this->assertCount(0, $result);
    }

    public function testItReturnsTrendingGames(): void
    {
        // Arrange
        $system = System::factory()->create();

        // deliberately inserted out-of-order to verify sorting
        $game3 = Game::factory()->create(['ConsoleID' => $system->id, 'Title' => 'third_most_popular']);
        $game1 = Game::factory()->create(['ConsoleID' => $system->id, 'Title' => 'most_popular']);
        $game4 = Game::factory()->create(['ConsoleID' => $system->id, 'Title' => 'fourth_most_popular']);
        $game2 = Game::factory()->create(['ConsoleID' => $system->id, 'Title' => 'second_most_popular']);
        $game5 = Game::factory()->create(['ConsoleID' => $system->id, 'Title' => 'fifth_most_popular']);

        User::factory()->create([
            'LastGameID' => $game4->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
        ]);
        User::factory()->count(2)->create([
            'LastGameID' => $game3->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
        ]);
        User::factory()->count(3)->create([
            'LastGameID' => $game2->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
        ]);
        User::factory()->count(4)->create([
            'LastGameID' => $game1->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
        ]);

        // Act
        $result = (new BuildTrendingGamesAction())->execute();

        // Assert
        $this->assertCount(4, $result);
        $this->assertContainsOnlyInstancesOf(TrendingGameData::class, $result);

        $this->assertEquals('most_popular', $result[0]->game->title);
        $this->assertEquals('second_most_popular', $result[1]->game->title);
        $this->assertEquals('third_most_popular', $result[2]->game->title);
        $this->assertEquals('fourth_most_popular', $result[3]->game->title);

        $this->assertEquals(4, $result[0]->playerCount);
        $this->assertEquals(3, $result[1]->playerCount);
        $this->assertEquals(2, $result[2]->playerCount);
        $this->assertEquals(1, $result[3]->playerCount);
    }
}
