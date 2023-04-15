<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Platform\Actions\ResetPlayerAchievementAction;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\TestsPlayerAchievements;
use Tests\Feature\Platform\TestsPlayerBadges;
use Tests\TestCase;

class ResetPlayerAchievementActionTest extends TestCase
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
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['Points' => 5, 'TrueRatio' => 7, 'Author' => $author->User]);

        $this->addSoftcoreUnlock($user, $achievement);

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertDoesNotHaveHardcoreUnlock($user, $achievement);
        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234, $user->RAPoints);
        $this->assertEquals(2345, $user->TrueRAPoints);
        $this->assertEquals(111, $author->ContribCount);
        $this->assertEquals(2222, $author->ContribYield);

        $action = new ResetPlayerAchievementAction();
        $this->assertTrue($action->execute($user, $achievement->ID));

        // unlock should have been deleted
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);

        // user points should have been adjusted
        $this->assertEquals(123 - 5, $user->RASoftcorePoints);
        $this->assertEquals(1234, $user->RAPoints);
        $this->assertEquals(2345, $user->TrueRAPoints);

        // author contibutions should have been adjusted
        $author = User::firstWhere('User', $author->User);
        $this->assertEquals(111 - 1, $author->ContribCount);
        $this->assertEquals(2222 - 5, $author->ContribYield);
    }

    public function testResetHardcore(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RASoftcorePoints' => 123, 'RAPoints' => 1234, 'TrueRAPoints' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['ContribCount' => 111, 'ContribYield' => 2222]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['Points' => 5, 'TrueRatio' => 7, 'Author' => $author->User]);

        $this->addHardcoreUnlock($user, $achievement);

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertHasHardcoreUnlock($user, $achievement);
        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234, $user->RAPoints);
        $this->assertEquals(2345, $user->TrueRAPoints);
        $this->assertEquals(111, $author->ContribCount);
        $this->assertEquals(2222, $author->ContribYield);

        $action = new ResetPlayerAchievementAction();
        $this->assertTrue($action->execute($user, $achievement->ID));

        // unlock should have been deleted
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);

        // user points should have been adjusted
        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234 - 5, $user->RAPoints);
        $this->assertEquals(2345 - 7, $user->TrueRAPoints);

        // author contibutions should have been adjusted
        $author = User::firstWhere('User', $author->User);
        $this->assertEquals(111 - 1, $author->ContribCount);
        $this->assertEquals(2222 - 5, $author->ContribYield);
    }

    public function testResetAuthoredAchievement(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RASoftcorePoints' => 123, 'RAPoints' => 1234, 'TrueRAPoints' => 2345,
                                         'ContribCount' => 111, 'ContribYield' => 2222]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['Points' => 5, 'TrueRatio' => 7, 'Author' => $user->User]);

        $this->addHardcoreUnlock($user, $achievement);

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertHasHardcoreUnlock($user, $achievement);
        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234, $user->RAPoints);
        $this->assertEquals(2345, $user->TrueRAPoints);
        $this->assertEquals(111, $user->ContribCount);
        $this->assertEquals(2222, $user->ContribYield);

        $action = new ResetPlayerAchievementAction();
        $this->assertTrue($action->execute($user, $achievement->ID));

        // unlock should have been deleted
        $this->assertDoesNotHaveAnyUnlock($user, $achievement);

        // user points should have been adjusted
        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234 - 5, $user->RAPoints);
        $this->assertEquals(2345 - 7, $user->TrueRAPoints);

        // contribution tallies do not include the author. expect to not be updated
        $author = User::firstWhere('User', $user->User);
        $this->assertEquals(111, $author->ContribCount);
        $this->assertEquals(2222, $author->ContribYield);
    }

    public function testResetOnlyAffectsTargetUser(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create();

        $this->addSoftcoreUnlock($user, $achievement);
        $this->addHardcoreUnlock($user2, $achievement);

        $this->assertHasSoftcoreUnlock($user, $achievement);
        $this->assertDoesNotHaveHardcoreUnlock($user, $achievement);
        $this->assertHasSoftcoreUnlock($user2, $achievement);
        $this->assertHasHardcoreUnlock($user2, $achievement);

        $action = new ResetPlayerAchievementAction();
        $this->assertTrue($action->execute($user, $achievement->ID));

        $this->assertDoesNotHaveAnyUnlock($user, $achievement);
        $this->assertHasSoftcoreUnlock($user2, $achievement);
        $this->assertHasHardcoreUnlock($user2, $achievement);
    }

    public function testResetCoreRemovesMasteryBadge(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create();
        $achievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $achievements->get(0));
        $this->addHardcoreUnlock($user, $achievements->get(1));
        $this->addHardcoreUnlock($user, $achievements->get(2));
        $this->addMasteryBadge($user, $game);
        $this->assertHasMasteryBadge($user, $game);

        $action = new ResetPlayerAchievementAction();
        $this->assertTrue($action->execute($user, $achievements->get(1)->ID));

        $this->assertDoesNotHaveMasteryBadge($user, $game);
    }

    public function testResetUnofficialDoesNotRemoveMasteryBadge(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create();
        $achievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);

        // normally, a user can only have an unofficial unlock if the achievement was demoted after it was unlocked
        /** @var Achievement $unofficialAchievement */
        $unofficialAchievement = Achievement::factory()->create(['GameID' => $game->ID]);
        $this->addHardcoreUnlock($user, $unofficialAchievement);

        $this->addHardcoreUnlock($user, $achievements->get(0));
        $this->addHardcoreUnlock($user, $achievements->get(1));
        $this->addHardcoreUnlock($user, $achievements->get(2));
        $this->addMasteryBadge($user, $game);
        $this->assertHasMasteryBadge($user, $game);

        $this->assertHasHardcoreUnlock($user, $unofficialAchievement);

        $action = new ResetPlayerAchievementAction();
        $this->assertTrue($action->execute($user, $unofficialAchievement->ID));

        $this->assertHasMasteryBadge($user, $game);
        $this->assertDoesNotHaveAnyUnlock($user, $unofficialAchievement);
    }

    public function testResetGame(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RASoftcorePoints' => 123, 'RAPoints' => 1234, 'TrueRAPoints' => 2345]);
        /** @var User $author */
        $author = User::factory()->create(['ContribCount' => 111, 'ContribYield' => 2222]);
        /** @var Game $game */
        $game = Game::factory()->create();
        $achievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $unofficialAchievement */
        $unofficialAchievement = Achievement::factory()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create();
        /** @var Achievement $game2Achievement */
        $game2Achievement = Achievement::factory()->published()->create(['GameID' => $game2->ID]);

        $this->addHardcoreUnlock($user, $achievements->get(0));
        $this->addHardcoreUnlock($user, $achievements->get(1));
        $this->addHardcoreUnlock($user, $achievements->get(2));
        $this->addHardcoreUnlock($user, $unofficialAchievement);
        $this->addHardcoreUnlock($user, $game2Achievement);
        $this->addMasteryBadge($user, $game);
        $this->assertHasMasteryBadge($user, $game);

        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234, $user->RAPoints);
        $this->assertEquals(2345, $user->TrueRAPoints);
        $this->assertEquals(111, $author->ContribCount);
        $this->assertEquals(2222, $author->ContribYield);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        $this->addHardcoreUnlock($user2, $achievements->get(0));
        $this->addHardcoreUnlock($user2, $achievements->get(1));
        $this->addHardcoreUnlock($user2, $achievements->get(2));
        $this->addMasteryBadge($user2, $game);
        $this->assertHasMasteryBadge($user2, $game);

        $action = new ResetPlayerAchievementAction();
        $this->assertTrue($action->execute($user, gameID: $game->ID));

        // unlocks and badge should have been revoked
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(0));
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(1));
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(2));
        $this->assertDoesNotHaveAnyUnlock($user, $unofficialAchievement);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        // secondary game should have been ignored
        $this->assertHasHardcoreUnlock($user, $game2Achievement);

        // points should have been updated
        $totalPoints = $achievements->get(0)->Points + $achievements->get(1)->Points + $achievements->get(2)->Points;
        $totalTruePoints = $achievements->get(0)->TruePoints + $achievements->get(1)->TruePoints + $achievements->get(2)->TruePoints;
        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234 - $totalPoints, $user->RAPoints);
        $this->assertEquals(2345 - $totalTruePoints, $user->TrueRAPoints);

        // author contributions should have been updated
        $author = User::firstWhere('User', $author->User);
        $this->assertEquals(111 - 3, $author->ContribCount);
        $this->assertEquals(2222 - $totalPoints, $author->ContribYield);

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
        /** @var Game $game */
        $game = Game::factory()->create();
        $achievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $unofficialAchievement */
        $unofficialAchievement = Achievement::factory()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create();
        /** @var Achievement $game2Achievement */
        $game2Achievement = Achievement::factory()->published()->create(['GameID' => $game2->ID, 'Author' => $author->User]);

        $this->addHardcoreUnlock($user, $achievements->get(0));
        $this->addHardcoreUnlock($user, $achievements->get(1));
        $this->addHardcoreUnlock($user, $achievements->get(2));
        $this->addHardcoreUnlock($user, $unofficialAchievement);
        $this->addHardcoreUnlock($user, $game2Achievement);
        $this->addMasteryBadge($user, $game);
        $this->assertHasMasteryBadge($user, $game);

        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234, $user->RAPoints);
        $this->assertEquals(2345, $user->TrueRAPoints);
        $this->assertEquals(111, $author->ContribCount);
        $this->assertEquals(2222, $author->ContribYield);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        $this->addHardcoreUnlock($user2, $achievements->get(0));
        $this->addHardcoreUnlock($user2, $achievements->get(1));
        $this->addHardcoreUnlock($user2, $achievements->get(2));
        $this->addMasteryBadge($user2, $game);
        $this->assertHasMasteryBadge($user2, $game);

        $action = new ResetPlayerAchievementAction();
        $this->assertTrue($action->execute($user));

        // unlocks and badge should have been revoked
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(0));
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(1));
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(2));
        $this->assertDoesNotHaveAnyUnlock($user, $unofficialAchievement);
        $this->assertDoesNotHaveMasteryBadge($user, $game);
        $this->assertDoesNotHaveAnyUnlock($user, $game2Achievement);

        // points should have been updated
        $totalPoints = $achievements->get(0)->Points + $achievements->get(1)->Points + $achievements->get(2)->Points + $game2Achievement->Points;
        $totalTruePoints = $achievements->get(0)->TruePoints + $achievements->get(1)->TruePoints + $achievements->get(2)->TruePoints + $game2Achievement->TruePoints;
        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234 - $totalPoints, $user->RAPoints);
        $this->assertEquals(2345 - $totalTruePoints, $user->TrueRAPoints);

        // author contributions should have been updated
        $author = User::firstWhere('User', $author->User);
        $this->assertEquals(111 - 4, $author->ContribCount);
        $this->assertEquals(2222 - $totalPoints, $author->ContribYield);

        // secondary user should not have been affected
        $this->assertHasHardcoreUnlock($user2, $achievements->get(0));
        $this->assertHasHardcoreUnlock($user2, $achievements->get(1));
        $this->assertHasHardcoreUnlock($user2, $achievements->get(2));
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
        /** @var Game $game */
        $game = Game::factory()->create();
        $achievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create();
        /** @var Achievement $game2Achievement */
        $game2Achievement = Achievement::factory()->published()->create(['GameID' => $game2->ID, 'Author' => $author2->User]);

        $this->addHardcoreUnlock($user, $achievements->get(0));
        $this->addHardcoreUnlock($user, $achievements->get(1));
        $this->addHardcoreUnlock($user, $achievements->get(2));
        $this->addHardcoreUnlock($user, $game2Achievement);

        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234, $user->RAPoints);
        $this->assertEquals(2345, $user->TrueRAPoints);
        $this->assertEquals(777, $author->ContribCount);
        $this->assertEquals(3333, $author->ContribYield);
        $this->assertEquals(111, $author2->ContribCount);
        $this->assertEquals(2222, $author2->ContribYield);

        $action = new ResetPlayerAchievementAction();
        $this->assertTrue($action->execute($user));

        // unlocks and badge should have been revoked
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(0));
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(1));
        $this->assertDoesNotHaveAnyUnlock($user, $achievements->get(2));
        $this->assertDoesNotHaveAnyUnlock($user, $game2Achievement);

        // points should have been updated
        $gamePoints = $achievements->get(0)->Points + $achievements->get(1)->Points + $achievements->get(2)->Points;
        $gameTruePoints = $achievements->get(0)->TruePoints + $achievements->get(1)->TruePoints + $achievements->get(2)->TruePoints;
        $totalPoints = $gamePoints + $game2Achievement->Points;
        $totalTruePoints = $gameTruePoints + $game2Achievement->TruePoints;
        $this->assertEquals(123, $user->RASoftcorePoints);
        $this->assertEquals(1234 - $totalPoints, $user->RAPoints);
        $this->assertEquals(2345 - $totalTruePoints, $user->TrueRAPoints);

        // author contributions should have been updated
        $author = User::firstWhere('User', $author->User);
        $this->assertEquals(777 - 3, $author->ContribCount);
        $this->assertEquals(3333 - $gamePoints, $author->ContribYield);

        // secondary author contributions should have been updated
        $author2 = User::firstWhere('User', $author2->User);
        $this->assertEquals(111 - 1, $author2->ContribCount);
        $this->assertEquals(2222 - $game2Achievement->Points, $author2->ContribYield);
    }
}
