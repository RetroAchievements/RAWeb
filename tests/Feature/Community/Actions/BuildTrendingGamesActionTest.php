<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\BuildTrendingGamesAction;
use App\Community\Data\TrendingGameData;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\GameRecentPlayer;
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
        $game3 = Game::factory()->create(['system_id' => $system->id, 'title' => 'third_most_popular']);
        $game1 = Game::factory()->create(['system_id' => $system->id, 'title' => 'most_popular']);
        $game4 = Game::factory()->create(['system_id' => $system->id, 'title' => 'fourth_most_popular']);
        $game2 = Game::factory()->create(['system_id' => $system->id, 'title' => 'second_most_popular']);
        $game5 = Game::factory()->create(['system_id' => $system->id, 'title' => 'fifth_most_popular']);

        $game4User = User::factory()->create([
            'rich_presence_game_id' => $game4->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
        ]);
        $game3Users = User::factory()->count(2)->create([
            'rich_presence_game_id' => $game3->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
        ]);
        $game2Users = User::factory()->count(3)->create([
            'rich_presence_game_id' => $game2->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
        ]);
        $game1Users = User::factory()->count(4)->create([
            'rich_presence_game_id' => $game1->id,
            'rich_presence_updated_at' => now(),
            'Permissions' => Permissions::Registered,
        ]);

        GameRecentPlayer::factory()->create([
            'user_id' => $game4User->id,
            'game_id' => $game4->id,
            'rich_presence_updated_at' => now(),
        ]);
        foreach ($game3Users as $user) {
            GameRecentPlayer::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game3->id,
                'rich_presence_updated_at' => now(),
            ]);
        }
        foreach ($game2Users as $user) {
            GameRecentPlayer::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game2->id,
                'rich_presence_updated_at' => now(),
            ]);
        }
        foreach ($game1Users as $user) {
            GameRecentPlayer::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game1->id,
                'rich_presence_updated_at' => now(),
            ]);
        }

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
