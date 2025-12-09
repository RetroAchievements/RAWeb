<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers;

use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
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

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);
        $gameAchievementSet = GameAchievementSet::first();

        /** @var User $user */
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.user.game-achievement-set.preferences.update'), [
                'preferences' => [
                    [
                        'gameAchievementSetId' => $gameAchievementSet->id,
                        'optedIn' => false,
                    ],
                ],
            ]);

        // Assert
        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_game_achievement_set_preferences', [
            'user_id' => $user->id,
            'game_achievement_set_id' => $gameAchievementSet->id,
            'opted_in' => false,
        ]);
    }

    public function testItAllowsSettingMultiplePreferencesInBatch(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        // ... create a core set ...
        Achievement::factory()->published()->count(5)->create(['GameID' => $game->id]);
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);
        $coreSet = GameAchievementSet::where('type', AchievementSetType::Core)->first();

        // ... create a bonus set ...
        $bonusAchievementSet = AchievementSet::factory()->create();
        $bonusGameAchievementSet = GameAchievementSet::create([
            'game_id' => $game->id,
            'achievement_set_id' => $bonusAchievementSet->id,
            'type' => AchievementSetType::Bonus,
            'title' => 'Bonus Achievements',
            'order_column' => 1,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.user.game-achievement-set.preferences.update'), [
                'preferences' => [
                    [
                        'gameAchievementSetId' => $coreSet->id,
                        'optedIn' => false,
                    ],
                    [
                        'gameAchievementSetId' => $bonusGameAchievementSet->id,
                        'optedIn' => true,
                    ],
                ],
            ]);

        // Assert
        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_game_achievement_set_preferences', [
            'user_id' => $user->id,
            'game_achievement_set_id' => $coreSet->id,
            'opted_in' => false,
        ]);

        $this->assertDatabaseHas('user_game_achievement_set_preferences', [
            'user_id' => $user->id,
            'game_achievement_set_id' => $bonusGameAchievementSet->id,
            'opted_in' => true,
        ]);
    }
}
