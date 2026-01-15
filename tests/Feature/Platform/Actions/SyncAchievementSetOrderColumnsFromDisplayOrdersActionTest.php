<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\AchievementSetAchievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\System;
use App\Platform\Actions\SyncAchievementSetOrderColumnsFromDisplayOrdersAction;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncAchievementSetOrderColumnsFromDisplayOrdersActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItSyncsOrderColumnsFromAchievementsToAchievementSetPivot(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievementSet = AchievementSet::factory()->create();

        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        // ... create achievements with specific order_column values ...
        $achievement1 = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'order_column' => 10,
        ]);
        $achievement2 = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'order_column' => 20,
        ]);
        $achievement3 = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'order_column' => 30,
        ]);

        // ... verify the pivot table has the initial order_column values (set by the event) ...
        $this->assertEquals(10, AchievementSetAchievement::where('achievement_id', $achievement1->id)->value('order_column'));
        $this->assertEquals(20, AchievementSetAchievement::where('achievement_id', $achievement2->id)->value('order_column'));
        $this->assertEquals(30, AchievementSetAchievement::where('achievement_id', $achievement3->id)->value('order_column'));

        // Act
        // ... change the order_column values on the achievements table ...
        $achievement1->update(['order_column' => 300]);
        $achievement2->update(['order_column' => 200]);
        $achievement3->update(['order_column' => 100]);

        (new SyncAchievementSetOrderColumnsFromDisplayOrdersAction())->execute($achievement1);

        // Assert
        $this->assertEquals(300, AchievementSetAchievement::where('achievement_id', $achievement1->id)->value('order_column'));
        $this->assertEquals(200, AchievementSetAchievement::where('achievement_id', $achievement2->id)->value('order_column'));
        $this->assertEquals(100, AchievementSetAchievement::where('achievement_id', $achievement3->id)->value('order_column'));
    }
}
