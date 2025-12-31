<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\PlayerBadge;
use App\Models\PlayerProgressReset;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\ResetPlayerProgressAction;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\PlayerProgressResetType;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class ResetPlayerProgressActionTest extends TestCase
{
    use RefreshDatabase;

    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    public function testResetSoftcore(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points' => 123, 'points_hardcore' => 1234, 'points_weighted' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['yield_unlocks' => 111, 'yield_points' => 2222]);
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 5, 'points_weighted' => 7, 'user_id' => $author->id]);

        $this->addSoftcoreUnlock($user, $achievement);

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertDoesNotHaveHardcoreUnlock($user, $achievement);
        $this->assertEquals($achievement->points, $user->points);
        $this->assertEquals(0, $user->points_hardcore);
        $this->assertEquals(0, $user->points_weighted);

        $author->refresh();
        // $this->assertEquals(1, $author->yield_unlocks);
        // $this->assertEquals($achievement->points, $author->yield_points);

        (new ResetPlayerProgressAction())->execute($user, $achievement->id);
        $user->refresh();

        // unlock should have been deleted
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);

        // user points should have been adjusted
        $this->assertEquals(0, $user->points);
        $this->assertEquals(0, $user->points_hardcore);
        $this->assertEquals(0, $user->points_weighted);

        // author contributions should have been adjusted
        $author->refresh();
        // $this->assertEquals(0, $author->yield_unlocks);
        // $this->assertEquals(0, $author->yield_points);

        // repeated call should do nothing
        (new ResetPlayerProgressAction())->execute($user, $achievement->id);
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);
        $this->assertEquals(0, $user->points);
        $this->assertEquals(0, $user->points_hardcore);
        $this->assertEquals(0, $user->points_weighted);
    }

    public function testResetHardcore(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points' => 123, 'points_hardcore' => 1234, 'points_weighted' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['yield_unlocks' => 111, 'yield_points' => 2222]);
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 5, 'points_weighted' => 7, 'user_id' => $author->id]);

        $this->addHardcoreUnlock($user, $achievement);
        $achievement->refresh();

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertHasHardcoreUnlock($user, $achievement);
        $this->assertEquals(0, $user->points);
        $this->assertEquals($achievement->points, $user->points_hardcore);
        $this->assertEquals($achievement->points_weighted, $user->points_weighted);

        $author->refresh();
        // $this->assertEquals(1, $author->yield_unlocks);
        // $this->assertEquals($achievement->points, $author->yield_points);

        (new ResetPlayerProgressAction())->execute($user, $achievement->id);
        $user->refresh();

        // unlock should have been deleted
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);

        // user points should have been adjusted
        $this->assertEquals(0, $user->points);
        $this->assertEquals(0, $user->points_hardcore);
        $this->assertEquals(0, $user->points_weighted);

        // author contributions should have been adjusted
        $author->refresh();
        // $this->assertEquals(0, $author->yield_unlocks);
        // $this->assertEquals(0, $author->yield_points);
    }

    public function testResetAuthoredAchievement(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'points' => 123,
            'points_hardcore' => 1234,
            'points_weighted' => 2345,
            'yield_unlocks' => 111,
            'yield_points' => 2222,
        ]);
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 5, 'points_weighted' => 7, 'user_id' => $user->id]);

        $this->addHardcoreUnlock($user, $achievement);
        $achievement->refresh(); // achievement unlock may adjust weighted points

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertHasHardcoreUnlock($user, $achievement);
        $this->assertEquals(0, $user->points);
        $this->assertEquals($achievement->points, $user->points_hardcore);
        $this->assertEquals($achievement->points_weighted, $user->points_weighted);

        // contribution tallies do not include the author. expect to not be updated
        // $this->assertEquals(0, $user->yield_unlocks);
        // $this->assertEquals(0, $user->yield_points);

        (new ResetPlayerProgressAction())->execute($user, $achievement->id);
        $user->refresh();

        // unlock should have been deleted
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);

        // user points should have been adjusted
        $this->assertEquals(0, $user->points);
        $this->assertEquals(0, $user->points_hardcore);
        $this->assertEquals(0, $user->points_weighted);
        // $this->assertEquals(0, $user->yield_unlocks);
        // $this->assertEquals(0, $user->yield_points);
    }

    public function testResetOnlyAffectsTargetUser(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

        $this->addSoftcoreUnlock($user, $achievement);
        $this->addHardcoreUnlock($user2, $achievement);

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertDoesNotHaveHardcoreUnlock($user, $achievement);
        $this->assertHasSoftcoreUnlock($user2, $achievement);
        $this->assertHasHardcoreUnlock($user2, $achievement);

        (new ResetPlayerProgressAction())->execute($user, $achievement->id);
        $user->refresh();

        $this->assertDoesNotHaveAnyUnlock($user, $achievement);
        $this->assertHasSoftcoreUnlock($user2, $achievement);
        $this->assertHasHardcoreUnlock($user2, $achievement);
    }

    public function testResetCoreRemovesMasteryBadge(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->promoted()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['game_id' => $game->id]);

        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user, $achievement);
        }
        $this->assertHasMasteryBadge($user, $game);

        (new ResetPlayerProgressAction())->execute($user, $achievements->get(1)->id);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        // repeat for softcore unlock/reset
        $this->addSoftcoreUnlock($user, $achievements->get(1));
        $this->assertHasCompletionBadge($user, $game);

        (new ResetPlayerProgressAction())->execute($user, $achievements->get(1)->id);
        $this->assertDoesNotHaveCompletionBadge($user, $game);
    }

    public function testResetUnofficialDoesNotRemoveMasteryBadge(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->promoted()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['game_id' => $game->id]);

        // a user can only have an unofficial unlock if the achievement was demoted after it was unlocked
        /** @var Achievement $unofficialAchievement */
        $unofficialAchievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $this->addHardcoreUnlock($user, $unofficialAchievement);
        $unofficialAchievement->is_promoted = false;
        $unofficialAchievement->save();

        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user, $achievement);
        }
        $this->assertHasMasteryBadge($user, $game);

        $this->assertHasHardcoreUnlock($user, $unofficialAchievement);

        (new ResetPlayerProgressAction())->execute($user, $unofficialAchievement->id);

        $this->assertHasMasteryBadge($user, $game);
        $this->assertDoesNotHaveAnyUnlock($user, $unofficialAchievement);
    }

    public function testResetProgressionRemovesBeatenBadge(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->promoted()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['game_id' => $game->id]);

        for ($i = 0; $i < 3; $i++) {
            $achievements->get($i)->type = AchievementType::Progression;
            $achievements->get($i)->save();
        }
        $achievements->get(3)->type = AchievementType::WinCondition;
        $achievements->get(3)->save();

        for ($i = 0; $i < 5; $i++) {
            $this->addHardcoreUnlock($user, $achievements->get($i));
        }
        $this->assertHasBeatenBadge($user, $game, UnlockMode::Hardcore);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        // test reset progression achievement
        (new ResetPlayerProgressAction())->execute($user, $achievements->get(1)->id);
        $this->assertDoesNotHaveBeatenBadge($user, $game);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        // restore beaten badge and test reset of win condition
        $this->addHardcoreUnlock($user, $achievements->get(1));
        $this->assertHasBeatenBadge($user, $game, UnlockMode::Hardcore);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        (new ResetPlayerProgressAction())->execute($user, $achievements->get(3)->id);
        $this->assertDoesNotHaveBeatenBadge($user, $game);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        // restore beaten badge and test full game reset
        $this->addHardcoreUnlock($user, $achievements->get(3));
        $this->assertHasBeatenBadge($user, $game, UnlockMode::Hardcore);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        (new ResetPlayerProgressAction())->execute($user, gameID: $game->id);
        $this->assertDoesNotHaveBeatenBadge($user, $game);
        $this->assertDoesNotHaveMasteryBadge($user, $game);
    }

    public function testResetGame(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points' => 123, 'points_hardcore' => 1234, 'points_weighted' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['yield_unlocks' => 111, 'yield_points' => 2222]);
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->promoted()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['game_id' => $game->id, 'user_id' => $author->id, 'points_weighted' => 7]);
        /** @var Achievement $unofficialAchievement */
        $unofficialAchievement = Achievement::factory()->create(['game_id' => $game->id, 'user_id' => $author->id, 'points_weighted' => 7]);
        $game2 = $this->seedGame(withHash: false);
        /** @var Achievement $game2Achievement */
        $game2Achievement = Achievement::factory()->promoted()->create(['game_id' => $game2->id, 'points_weighted' => 7]);

        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user, $achievement);
        }
        $this->addHardcoreUnlock($user, $unofficialAchievement);
        $this->addHardcoreUnlock($user, $game2Achievement);

        $this->assertHasMasteryBadge($user, $game);
        $this->assertEquals(7, $user->achievements_unlocked);
        $this->assertEquals(0, $user->points);
        $this->assertEquals($achievements->sum('points') + $game2Achievement->points, $user->points_hardcore);

        $author->refresh();
        // $this->assertEquals($achievements->count(), $author->yield_unlocks);
        // $this->assertEquals($achievements->sum('points'), $author->yield_points);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user2, $achievement);
        }
        $this->assertHasMasteryBadge($user2, $game);
        $this->assertEquals(6, $user2->achievements()->promoted()->count());

        (new ResetPlayerProgressAction())->execute($user, gameID: $game->id);
        $user->refresh();

        // unlocks and badge should have been revoked
        foreach ($achievements as $achievement) {
            $this->assertDoesNotHaveAnyUnlock($user, $achievement);
        }
        $this->assertDoesNotHaveAnyUnlock($user, $unofficialAchievement);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        // secondary game should have been ignored
        $this->assertHasHardcoreUnlock($user, $game2Achievement);

        // points should have been updated
        $this->assertEquals(0, $user->points);
        $this->assertEquals($game2Achievement->points, $user->points_hardcore);

        // author contributions should have been updated and only have user2's unlocks attributed
        $author->refresh();
        // $this->assertEquals($user2->achievements()->count(), $author->yield_unlocks);
        // $this->assertEquals($user2->achievements()->sum('Points'), $author->yield_points);

        // secondary user should not have been affected
        $this->assertHasHardcoreUnlock($user2, $achievements->get(0));
        $this->assertHasHardcoreUnlock($user2, $achievements->get(1));
        $this->assertHasHardcoreUnlock($user2, $achievements->get(2));
        $this->assertHasMasteryBadge($user2, $game);
    }

    public function testResetEventGame(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points' => 123, 'points_hardcore' => 1234, 'points_weighted' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['yield_unlocks' => 111, 'yield_points' => 2222]);
        $eventSystem = System::factory()->create(['id' => System::Events]);
        $game = $this->seedGame(system: $eventSystem, withHash: false);
        $achievements = Achievement::factory()->promoted()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['game_id' => $game->id, 'user_id' => $author->id, 'points' => 0]);

        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user, $achievement);
        }

        $user->refresh();
        $this->assertHasMasteryBadge($user, $game);

        (new ResetPlayerProgressAction())->execute($user, gameID: $game->id);
        $user->refresh();

        // unlocks and badge should have been revoked
        foreach ($achievements as $achievement) {
            $this->assertDoesNotHaveAnyUnlock($user, $achievement);
        }
        $this->assertDoesNotHaveMasteryBadge($user, $game);
    }

    public function testResetAll(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points' => 123, 'points_hardcore' => 1234, 'points_weighted' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['yield_unlocks' => 111, 'yield_points' => 2222]);
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->promoted()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['game_id' => $game->id, 'user_id' => $author->id, 'points_weighted' => 0]);
        /** @var Achievement $unofficialAchievement */
        $unofficialAchievement = Achievement::factory()->create(['game_id' => $game->id, 'user_id' => $author->id, 'points_weighted' => 0]);
        $game2 = $this->seedGame(withHash: false);
        /** @var Achievement $game2Achievement */
        $game2Achievement = Achievement::factory()->promoted()->create(['game_id' => $game2->id, 'user_id' => $author->id, 'points_weighted' => 0]);

        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user, $achievement);
        }
        $this->addHardcoreUnlock($user, $unofficialAchievement);
        $this->addHardcoreUnlock($user, $game2Achievement);

        $this->assertHasMasteryBadge($user, $game);
        $this->assertEquals(7, $user->achievements_unlocked);
        $this->assertEquals(0, $user->points);
        $this->assertEquals($achievements->sum('points') + $game2Achievement->points, $user->points_hardcore);

        $author->refresh();
        // $this->assertEquals($user->achievements_unlocked, $author->yield_unlocks);
        // $this->assertEquals($user->points_hardcore, $author->yield_points);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user2, $achievement);
        }
        $this->assertHasMasteryBadge($user2, $game);

        (new ResetPlayerProgressAction())->execute($user);
        $user->refresh();

        // unlocks and badge should have been revoked
        foreach ($achievements as $achievement) {
            $this->assertDoesNotHaveAnyUnlock($user, $achievement);
        }
        $this->assertDoesNotHaveAnyUnlock($user, $unofficialAchievement);
        $this->assertDoesNotHaveMasteryBadge($user, $game);
        $this->assertDoesNotHaveAnyUnlock($user, $game2Achievement);

        // points should have been updated
        $this->assertEquals(0, $user->points);
        $this->assertEquals(0, $user->points_hardcore);
        $this->assertEquals(0, $user->points_weighted);

        // author contributions should have been updated and only have user2's unlocks attributed
        $author->refresh();
        // $this->assertEquals($user2->achievements()->count(), $author->yield_unlocks);
        // $this->assertEquals($user2->achievements()->sum('Points'), $author->yield_points);

        // secondary user should not have been affected
        foreach ($achievements as $achievement) {
            $this->assertHasHardcoreUnlock($user2, $achievement);
        }
        $this->assertHasMasteryBadge($user2, $game);
    }

    public function testResetAllMultipleAuthors(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points' => 123, 'points_hardcore' => 1234, 'points_weighted' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['yield_unlocks' => 777, 'yield_points' => 3333]);
        /** @var User $author2 */
        $author2 = User::factory()->create(['yield_unlocks' => 111, 'yield_points' => 2222]);
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->promoted()->count(3)->create(['game_id' => $game->id, 'user_id' => $author->id, 'points_weighted' => 0]);
        $game2 = $this->seedGame(withHash: false);
        /** @var Achievement $game2Achievement */
        $game2Achievement = Achievement::factory()->promoted()->create(['game_id' => $game2->id, 'user_id' => $author2->id, 'points_weighted' => 0]);

        $this->addHardcoreUnlock($user, $achievements->get(0));
        $this->addHardcoreUnlock($user, $achievements->get(1));
        $this->addHardcoreUnlock($user, $achievements->get(2));
        $this->addHardcoreUnlock($user, $game2Achievement);

        $this->assertEquals(4, $user->achievements_unlocked);
        $this->assertEquals(0, $user->points);
        $this->assertEquals($achievements->sum('points') + $game2Achievement->points, $user->points_hardcore);

        $author->refresh();
        // $this->assertEquals($achievements->count(), $author->yield_unlocks);
        // $this->assertEquals($achievements->sum('points'), $author->yield_points);

        $author2->refresh();
        // $this->assertEquals(1, $author2->yield_unlocks);
        // $this->assertEquals($game2Achievement->points, $author2->yield_points);

        (new ResetPlayerProgressAction())->execute($user);
        $user->refresh();

        // unlocks and badge should have been revoked
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(0));
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(1));
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(2));
        $this->assertDoesNotHaveAnyUnlock($user, $game2Achievement);

        // points should have been updated
        $this->assertEquals(0, $user->points);
        $this->assertEquals(0, $user->points_hardcore);
        $this->assertEquals(0, $user->points_weighted);

        // author contributions should have been updated
        $author->refresh();
        // $this->assertEquals(0, $author->yield_unlocks);
        // $this->assertEquals(0, $author->yield_points);

        // secondary author contributions should have been updated
        $author2->refresh();
        // $this->assertEquals(0, $author2->yield_unlocks);
        // $this->assertEquals(0, $author2->yield_points);
    }

    public function testAccountResetCreatesAccountResetRecord(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->promoted()->count(2)->create(['game_id' => $game->id]);

        $this->addHardcoreUnlock($user, $achievements->get(0));
        $this->addHardcoreUnlock($user, $achievements->get(1));

        (new ResetPlayerProgressAction())->execute($user);

        $accountReset = PlayerProgressReset::where('user_id', $user->id)
            ->where('type', PlayerProgressResetType::Account)
            ->first();

        $this->assertNotNull($accountReset);
        $this->assertEquals(PlayerProgressResetType::Account, $accountReset->type);
        $this->assertNull($accountReset->type_id);

        $resetForGame = PlayerProgressReset::forUserAndGame($user, $game)->first();
        $this->assertNotNull($resetForGame);
        $this->assertEquals(PlayerProgressResetType::Account, $resetForGame->type);
    }

    public function testResetLastAchievementCreatesGameReset(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->promoted()->count(3)->create(['game_id' => $game->id]);

        // ... give the user all 3 achievements ...
        $this->addHardcoreUnlock($user, $achievements->get(0));
        $this->addHardcoreUnlock($user, $achievements->get(1));
        $this->addHardcoreUnlock($user, $achievements->get(2));

        // ... verify the user has 3 achievements for this game ...
        $playerAchievementCount = $user->playerAchievements()
            ->join('achievements', 'player_achievements.achievement_id', '=', 'achievements.id')
            ->where(DB::raw('achievements.game_id'), $game->id)
            ->where(DB::raw('achievements.is_promoted'), true)
            ->count();
        $this->assertEquals(3, $playerAchievementCount);

        // ... reset the first achievement ...
        (new ResetPlayerProgressAction())->execute($user, $achievements->get(0)->id);
        $lastReset = PlayerProgressReset::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        $this->assertNotNull($lastReset);
        $this->assertEquals(PlayerProgressResetType::Achievement, $lastReset->type); // !! Not the last achievement, so it isn't a game reset
        $this->assertEquals($achievements->get(0)->id, $lastReset->type_id);

        // ... verify the user now has 2 achievements left ...
        $playerAchievementCount = $user->playerAchievements()
            ->join('achievements', 'player_achievements.achievement_id', '=', 'achievements.id')
            ->where(DB::raw('achievements.game_id'), $game->id)
            ->where(DB::raw('achievements.is_promoted'), true)
            ->count();
        $this->assertEquals(2, $playerAchievementCount);

        // ... reset the second achievement ...
        (new ResetPlayerProgressAction())->execute($user, $achievements->get(1)->id);

        $resets = PlayerProgressReset::where('user_id', $user->id)
            ->where('type', PlayerProgressResetType::Achievement)
            ->where('type_id', $achievements->get(1)->id)
            ->get();
        $this->assertCount(1, $resets);
        $lastReset = $resets->first();

        $this->assertNotNull($lastReset);
        $this->assertEquals(PlayerProgressResetType::Achievement, $lastReset->type); // !! Still not the last achievement, so it's not a game reset
        $this->assertEquals($achievements->get(1)->id, $lastReset->type_id);

        // ... verify the user now has 1 achievement left ...
        $playerAchievementCount = $user->playerAchievements()
            ->join('achievements', 'player_achievements.achievement_id', '=', 'achievements.id')
            ->where(DB::raw('achievements.game_id'), $game->id)
            ->where(DB::raw('achievements.is_promoted'), true)
            ->count();
        $this->assertEquals(1, $playerAchievementCount);

        // ... reset the third (last) achievement. should have 0 left, this should create a game reset ...
        (new ResetPlayerProgressAction())->execute($user, $achievements->get(2)->id);

        $gameReset = PlayerProgressReset::where('user_id', $user->id)
            ->where('type', PlayerProgressResetType::Game)
            ->where('type_id', $game->id)
            ->first();

        $this->assertNotNull($gameReset);
        $this->assertEquals(PlayerProgressResetType::Game, $gameReset->type); // !!
        $this->assertEquals($game->id, $gameReset->type_id);
    }
}
