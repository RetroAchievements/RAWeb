<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlags;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGameAchievementSetPreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testItAllowsSettingPreference(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $achievements = Achievement::factory()->published()->count(10)->create(['GameID' => $game->id]);

        (new UpsertGameCoreAchievementSetFromLegacyFlags())->execute($game);
        $gameAchievementSet = GameAchievementSet::first();

        /** @var User $user */
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->putJson(route(
                'api.user.game-achievement-set.preference.update',
                ['gameAchievementSet' => $gameAchievementSet->id]
            ), [
                'optedIn' => false,
            ]);

        // Assert
        $response->assertStatus(200)->assertJson(['optedIn' => false]);

        $this->assertDatabaseHas('user_game_achievement_set_preferences', [
            'user_id' => $user->id,
            'game_achievement_set_id' => $gameAchievementSet->id,
            'opted_in' => false,
        ]);
    }
}
