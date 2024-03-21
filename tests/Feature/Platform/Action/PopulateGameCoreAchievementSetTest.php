<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\Game;
use App\Platform\Actions\PopulateCoreGameAchievementSet;
use App\Platform\Enums\GameAchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopulateGameCoreAchievementSetTest extends TestCase
{
    use RefreshDatabase;

    public function testItSuccessfullyCreatesNewCoreSet(): void
    {
        // Arrange
        $game = Game::factory()->create([
            'players_total' => 100,
            'players_hardcore' => 50,
        ]);
        Achievement::factory()->published()->count(6)->create([
            'GameID' => $game->id,
            'Points' => 10,
            'TrueRatio' => 10,
        ]);
        Achievement::factory()->count(3)->create([
            'GameID' => $game->id,
            'Points' => 5,
            'TrueRatio' => 2,
        ]);

        // Act
        (new PopulateCoreGameAchievementSet())->execute($game);

        $game->load('gameAchievementSets.achievementSet.achievementSetAchievements');
        $gameAchievementSet = $game->gameAchievementSets->get(0);
        $achievementSet = $gameAchievementSet->achievementSet;

        // Assert
        $this->assertEquals($gameAchievementSet->game_id, $game->id);
        $this->assertEquals($gameAchievementSet->type, GameAchievementSetType::Core);

        $this->assertEquals($achievementSet->players_total, $game->players_total);
        $this->assertEquals($achievementSet->players_hardcore, $game->players_hardcore);
        $this->assertEquals($achievementSet->achievements_published, 6);
        $this->assertEquals($achievementSet->achievements_unpublished, 3);
        $this->assertEquals($achievementSet->points_total, 60);
        $this->assertEquals($achievementSet->points_weighted, 60);

        $this->assertEquals($achievementSet->achievementSetAchievements->count(), 9);
    }

    public function testItDeletesExistingCoreSetsIfTheyExist(): void
    {
        // Arrange
        $game = Game::factory()->create([
            'players_total' => 100,
            'players_hardcore' => 50,
        ]);
        Achievement::factory()->published()->count(6)->create([
            'GameID' => $game->id,
            'Points' => 10,
            'TrueRatio' => 10,
        ]);
        Achievement::factory()->count(3)->create([
            'GameID' => $game->id,
            'Points' => 5,
            'TrueRatio' => 2,
        ]);

        // Act
        (new PopulateCoreGameAchievementSet())->execute($game);
        (new PopulateCoreGameAchievementSet())->execute($game);

        $game->load('gameAchievementSets.achievementSet.achievementSetAchievements');

        // Assert
        $this->assertEquals($game->gameAchievementSets->count(), 1);
        $this->assertEquals($game->gameAchievementSets->get(0)->achievementSet->count(), 1);
        $this->assertEquals($game->gameAchievementSets->get(0)->achievementSet->achievementSetAchievements->count(), 9);
    }
}
