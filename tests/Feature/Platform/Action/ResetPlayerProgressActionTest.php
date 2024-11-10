<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Platform\Actions\ResetPlayerProgressAction;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $user = User::factory()->create(['RASoftcorePoints' => 123, 'RAPoints' => 1234, 'TrueRAPoints' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['ContribCount' => 111, 'ContribYield' => 2222]);
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 5, 'TrueRatio' => 7, 'user_id' => $author->id]);

        $this->addSoftcoreUnlock($user, $achievement);

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertDoesNotHaveHardcoreUnlock($user, $achievement);
        $this->assertEquals($achievement->points, $user->RASoftcorePoints);
        $this->assertEquals(0, $user->RAPoints);
        $this->assertEquals(0, $user->TrueRAPoints);

        $author->refresh();
        // $this->assertEquals(1, $author->ContribCount);
        // $this->assertEquals($achievement->points, $author->ContribYield);

        (new ResetPlayerProgressAction())->execute($user, $achievement->ID);
        $user->refresh();

        // unlock should have been deleted
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);

        // user points should have been adjusted
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals(0, $user->RAPoints);
        $this->assertEquals(0, $user->TrueRAPoints);

        // author contributions should have been adjusted
        $author->refresh();
        // $this->assertEquals(0, $author->ContribCount);
        // $this->assertEquals(0, $author->ContribYield);

        // repeated call should do nothing
        (new ResetPlayerProgressAction())->execute($user, $achievement->ID);
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals(0, $user->RAPoints);
        $this->assertEquals(0, $user->TrueRAPoints);
    }

    public function testResetHardcore(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RASoftcorePoints' => 123, 'RAPoints' => 1234, 'TrueRAPoints' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['ContribCount' => 111, 'ContribYield' => 2222]);
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 5, 'TrueRatio' => 7, 'user_id' => $author->id]);

        $this->addHardcoreUnlock($user, $achievement);
        $achievement->refresh();

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertHasHardcoreUnlock($user, $achievement);
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals($achievement->points, $user->RAPoints);
        $this->assertEquals($achievement->points_weighted, $user->TrueRAPoints);

        $author->refresh();
        // $this->assertEquals(1, $author->ContribCount);
        // $this->assertEquals($achievement->points, $author->ContribYield);

        (new ResetPlayerProgressAction())->execute($user, $achievement->ID);
        $user->refresh();

        // unlock should have been deleted
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);

        // user points should have been adjusted
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals(0, $user->RAPoints);
        $this->assertEquals(0, $user->TrueRAPoints);

        // author contributions should have been adjusted
        $author->refresh();
        // $this->assertEquals(0, $author->ContribCount);
        // $this->assertEquals(0, $author->ContribYield);
    }

    public function testResetAuthoredAchievement(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'RASoftcorePoints' => 123,
            'RAPoints' => 1234,
            'TrueRAPoints' => 2345,
            'ContribCount' => 111,
            'ContribYield' => 2222,
        ]);
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id, 'Points' => 5, 'TrueRatio' => 7, 'user_id' => $user->id]);

        $this->addHardcoreUnlock($user, $achievement);
        $achievement->refresh();

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertHasHardcoreUnlock($user, $achievement);
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals($achievement->points, $user->RAPoints);
        $this->assertEquals($achievement->points_weighted, $user->TrueRAPoints);

        // contribution tallies do not include the author. expect to not be updated
        // $this->assertEquals(0, $user->ContribCount);
        // $this->assertEquals(0, $user->ContribYield);

        (new ResetPlayerProgressAction())->execute($user, $achievement->ID);
        $user->refresh();

        // unlock should have been deleted
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);

        // user points should have been adjusted
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals(0, $user->RAPoints);
        $this->assertEquals(0, $user->TrueRAPoints);
        // $this->assertEquals(0, $user->ContribCount);
        // $this->assertEquals(0, $user->ContribYield);
    }

    public function testResetOnlyAffectsTargetUser(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        $this->addSoftcoreUnlock($user, $achievement);
        $this->addHardcoreUnlock($user2, $achievement);

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertDoesNotHaveHardcoreUnlock($user, $achievement);
        $this->assertHasSoftcoreUnlock($user2, $achievement);
        $this->assertHasHardcoreUnlock($user2, $achievement);

        (new ResetPlayerProgressAction())->execute($user, $achievement->ID);
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
        $achievements = Achievement::factory()->published()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['GameID' => $game->ID]);

        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user, $achievement);
        }
        $this->assertHasMasteryBadge($user, $game);

        (new ResetPlayerProgressAction())->execute($user, $achievements->get(1)->ID);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        // repeat for softcore unlock/reset
        $this->addSoftcoreUnlock($user, $achievements->get(1));
        $this->assertHasCompletionBadge($user, $game);

        (new ResetPlayerProgressAction())->execute($user, $achievements->get(1)->ID);
        $this->assertDoesNotHaveCompletionBadge($user, $game);
    }

    public function testResetUnofficialDoesNotRemoveMasteryBadge(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->published()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['GameID' => $game->ID]);

        // a user can only have an unofficial unlock if the achievement was demoted after it was unlocked
        /** @var Achievement $unofficialAchievement */
        $unofficialAchievement = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        $this->addHardcoreUnlock($user, $unofficialAchievement);
        $unofficialAchievement->Flags = AchievementFlag::Unofficial;
        $unofficialAchievement->save();

        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user, $achievement);
        }
        $this->assertHasMasteryBadge($user, $game);

        $this->assertHasHardcoreUnlock($user, $unofficialAchievement);

        (new ResetPlayerProgressAction())->execute($user, $unofficialAchievement->ID);

        $this->assertHasMasteryBadge($user, $game);
        $this->assertDoesNotHaveAnyUnlock($user, $unofficialAchievement);
    }

    public function testResetProgressionRemovesBeatenBadge(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->published()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['GameID' => $game->ID]);

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
        (new ResetPlayerProgressAction())->execute($user, $achievements->get(1)->ID);
        $this->assertDoesNotHaveBeatenBadge($user, $game);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        // restore beaten badge and test reset of win condition
        $this->addHardcoreUnlock($user, $achievements->get(1));
        $this->assertHasBeatenBadge($user, $game, UnlockMode::Hardcore);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        (new ResetPlayerProgressAction())->execute($user, $achievements->get(3)->ID);
        $this->assertDoesNotHaveBeatenBadge($user, $game);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        // restore beaten badge and test full game reset
        $this->addHardcoreUnlock($user, $achievements->get(3));
        $this->assertHasBeatenBadge($user, $game, UnlockMode::Hardcore);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        (new ResetPlayerProgressAction())->execute($user, gameID: $game->ID);
        $this->assertDoesNotHaveBeatenBadge($user, $game);
        $this->assertDoesNotHaveMasteryBadge($user, $game);
    }

    public function testResetGame(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RASoftcorePoints' => 123, 'RAPoints' => 1234, 'TrueRAPoints' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['ContribCount' => 111, 'ContribYield' => 2222]);
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->published()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['GameID' => $game->ID, 'user_id' => $author->id, 'TrueRatio' => 7]);
        /** @var Achievement $unofficialAchievement */
        $unofficialAchievement = Achievement::factory()->create(['GameID' => $game->ID, 'user_id' => $author->id, 'TrueRatio' => 7]);
        $game2 = $this->seedGame(withHash: false);
        /** @var Achievement $game2Achievement */
        $game2Achievement = Achievement::factory()->published()->create(['GameID' => $game2->ID, 'TrueRatio' => 7]);

        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user, $achievement);
        }
        $this->addHardcoreUnlock($user, $unofficialAchievement);
        $this->addHardcoreUnlock($user, $game2Achievement);

        $this->assertHasMasteryBadge($user, $game);
        $this->assertEquals(7, $user->achievements_unlocked);
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals($achievements->sum('Points') + $game2Achievement->points, $user->RAPoints);

        $author->refresh();
        // $this->assertEquals($achievements->count(), $author->ContribCount);
        // $this->assertEquals($achievements->sum('Points'), $author->ContribYield);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user2, $achievement);
        }
        $this->assertHasMasteryBadge($user2, $game);
        $this->assertEquals(6, $user2->achievements()->published()->count());

        (new ResetPlayerProgressAction())->execute($user, gameID: $game->ID);
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
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals($game2Achievement->points, $user->RAPoints);

        // author contributions should have been updated and only have user2's unlocks attributed
        $author->refresh();
        // $this->assertEquals($user2->achievements()->count(), $author->ContribCount);
        // $this->assertEquals($user2->achievements()->sum('Points'), $author->ContribYield);

        // secondary user should not have been affected
        $this->assertHasHardcoreUnlock($user2, $achievements->get(0));
        $this->assertHasHardcoreUnlock($user2, $achievements->get(1));
        $this->assertHasHardcoreUnlock($user2, $achievements->get(2));
        $this->assertHasMasteryBadge($user2, $game);
    }

    public function testResetAll(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RASoftcorePoints' => 123, 'RAPoints' => 1234, 'TrueRAPoints' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['ContribCount' => 111, 'ContribYield' => 2222]);
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->published()
            ->count(PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY)
            ->create(['GameID' => $game->ID, 'user_id' => $author->id, 'TrueRatio' => 0]);
        /** @var Achievement $unofficialAchievement */
        $unofficialAchievement = Achievement::factory()->create(['GameID' => $game->ID, 'user_id' => $author->id, 'TrueRatio' => 0]);
        $game2 = $this->seedGame(withHash: false);
        /** @var Achievement $game2Achievement */
        $game2Achievement = Achievement::factory()->published()->create(['GameID' => $game2->ID, 'user_id' => $author->id, 'TrueRatio' => 0]);

        foreach ($achievements as $achievement) {
            $this->addHardcoreUnlock($user, $achievement);
        }
        $this->addHardcoreUnlock($user, $unofficialAchievement);
        $this->addHardcoreUnlock($user, $game2Achievement);

        $this->assertHasMasteryBadge($user, $game);
        $this->assertEquals(7, $user->achievements_unlocked);
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals($achievements->sum('Points') + $game2Achievement->points, $user->RAPoints);

        $author->refresh();
        // $this->assertEquals($user->achievements_unlocked, $author->ContribCount);
        // $this->assertEquals($user->points, $author->ContribYield);

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
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals(0, $user->RAPoints);
        $this->assertEquals(0, $user->TrueRAPoints);

        // author contributions should have been updated and only have user2's unlocks attributed
        $author->refresh();
        // $this->assertEquals($user2->achievements()->count(), $author->ContribCount);
        // $this->assertEquals($user2->achievements()->sum('Points'), $author->ContribYield);

        // secondary user should not have been affected
        foreach ($achievements as $achievement) {
            $this->assertHasHardcoreUnlock($user2, $achievement);
        }
        $this->assertHasMasteryBadge($user2, $game);
    }

    public function testResetAllMultipleAuthors(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RASoftcorePoints' => 123, 'RAPoints' => 1234, 'TrueRAPoints' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['ContribCount' => 777, 'ContribYield' => 3333]);
        /** @var User $author2 */
        $author2 = User::factory()->create(['ContribCount' => 111, 'ContribYield' => 2222]);
        $game = $this->seedGame(withHash: false);
        $achievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID, 'user_id' => $author->id, 'TrueRatio' => 0]);
        $game2 = $this->seedGame(withHash: false);
        /** @var Achievement $game2Achievement */
        $game2Achievement = Achievement::factory()->published()->create(['GameID' => $game2->ID, 'user_id' => $author2->id, 'TrueRatio' => 0]);

        $this->addHardcoreUnlock($user, $achievements->get(0));
        $this->addHardcoreUnlock($user, $achievements->get(1));
        $this->addHardcoreUnlock($user, $achievements->get(2));
        $this->addHardcoreUnlock($user, $game2Achievement);

        $this->assertEquals(4, $user->achievements_unlocked);
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals($achievements->sum('Points') + $game2Achievement->points, $user->RAPoints);

        $author->refresh();
        // $this->assertEquals($achievements->count(), $author->ContribCount);
        // $this->assertEquals($achievements->sum('Points'), $author->ContribYield);

        $author2->refresh();
        // $this->assertEquals(1, $author2->ContribCount);
        // $this->assertEquals($game2Achievement->points, $author2->ContribYield);

        (new ResetPlayerProgressAction())->execute($user);
        $user->refresh();

        // unlocks and badge should have been revoked
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(0));
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(1));
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(2));
        $this->assertDoesNotHaveAnyUnlock($user, $game2Achievement);

        // points should have been updated
        $this->assertEquals(0, $user->RASoftcorePoints);
        $this->assertEquals(0, $user->RAPoints);
        $this->assertEquals(0, $user->TrueRAPoints);

        // author contributions should have been updated
        $author->refresh();
        // $this->assertEquals(0, $author->ContribCount);
        // $this->assertEquals(0, $author->ContribYield);

        // secondary author contributions should have been updated
        $author2->refresh();
        // $this->assertEquals(0, $author2->ContribCount);
        // $this->assertEquals(0, $author2->ContribYield);
    }
}
