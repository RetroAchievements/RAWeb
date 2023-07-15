<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Platform\TestsPlayerAchievements;
use Tests\TestCase;

class AwardAchievementTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    private function buildValidationHash(Achievement $achievement, User $user, int $hardcore)
    {
        return md5(str($achievement->ID) . $user->User . str($hardcore));
    }

    public function testHardcoreUnlock(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var User $author */
        $author = User::factory()->create(['ContribCount' => 1234, 'ContribYield' => 5678]);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameHash = '0123456789abcdeffedcba9876543210';
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        // cache the unlocks for the game - verify singular unlock captured
        $unlocks = getUserAchievementUnlocksForGame($this->user->User, $game->ID);
        $this->assertEquals([$achievement1->ID], array_keys($unlocks));

        // do the hardcore unlock
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;
        $truePointsBefore = $this->user->TrueRAPoints;
        $authorContribCountBefore = $author->ContribCount;
        $authorContribYieldBefore = $author->ContribYield;

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->Points,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        // player score should have increased
        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($scoreBefore + $achievement3->Points, $user1->RAPoints);
        $this->assertEquals($softcoreScoreBefore, $user1->RASoftcorePoints);
        $this->assertEquals($truePointsBefore + $achievement3->TruePoints, $user1->TrueRAPoints);

        // author contribution should have increased
        $author1 = User::firstWhere('User', $achievement3->Author);
        $this->assertEquals($authorContribYieldBefore + $achievement3->Points, $author1->ContribYield);
        $this->assertEquals($authorContribCountBefore + 1, $author1->ContribCount);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->User, $game->ID);
        $this->assertEquals([$achievement1->ID, $achievement3->ID], array_keys($unlocks));
        $this->assertEquals($now, $unlocks[$achievement3->ID]['DateEarnedHardcore']);
        $this->assertEquals($now, $unlocks[$achievement3->ID]['DateEarned']);

        // repeat the hardcore unlock
        $scoreBefore = $user1->RAPoints;
        $softcoreScoreBefore = $user1->RASoftcorePoints;
        $truePointsBefore = $user1->TrueRAPoints;
        $authorContribCountBefore = $author1->ContribCount;
        $authorContribYieldBefore = $author1->ContribYield;

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => false,
                // client detects the "User already has" and does not report this as an error.
                'Error' => 'User already has this achievement unlocked in hardcore mode.',
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        // player score should not have increased
        $user2 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($scoreBefore, $user2->RAPoints);
        $this->assertEquals($softcoreScoreBefore, $user2->RASoftcorePoints);
        $this->assertEquals($truePointsBefore, $user2->TrueRAPoints);

        // author contribution should not have increased
        $author2 = User::firstWhere('User', $achievement3->Author);
        $this->assertEquals($authorContribYieldBefore, $author2->ContribYield);
        $this->assertEquals($authorContribCountBefore, $author2->ContribCount);

        // make sure the unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement3, UnlockMode::Hardcore);
        $this->assertEquals($now, $unlockTime);
        $unlockTime = $this->getUnlockTime($user2, $achievement3, UnlockMode::Softcore);
        $this->assertEquals($now, $unlockTime);
    }

    public function testSoftcoreUnlockPromotedToHardcore(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var User $author */
        $author = User::factory()->create(['ContribCount' => 1234, 'ContribYield' => 5678]);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameHash = '0123456789abcdeffedcba9876543210';
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        // cache the unlocks for the game - verify singular unlock captured
        $unlocks = getUserAchievementUnlocksForGame($this->user->User, $game->ID);
        $this->assertEquals([$achievement1->ID], array_keys($unlocks));

        // do the softcore unlock
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 0);
        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;
        $truePointsBefore = $this->user->TrueRAPoints;
        $authorContribCountBefore = $author->ContribCount;
        $authorContribYieldBefore = $author->ContribYield;

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 0, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore + $achievement3->Points,
            ]);

        // player score should have increased
        $user1 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($scoreBefore, $user1->RAPoints);
        $this->assertEquals($softcoreScoreBefore + $achievement3->Points, $user1->RASoftcorePoints);
        $this->assertEquals($truePointsBefore, $user1->TrueRAPoints);

        // author contribution should have increased
        $author1 = User::firstWhere('User', $achievement3->Author);
        $this->assertEquals($authorContribYieldBefore + $achievement3->Points, $author1->ContribYield);
        $this->assertEquals($authorContribCountBefore + 1, $author1->ContribCount);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->User, $game->ID);
        $this->assertEquals([$achievement1->ID, $achievement3->ID], array_keys($unlocks));
        $this->assertEquals($now, $unlocks[$achievement3->ID]['DateEarned']);
        $this->assertArrayNotHasKey('DateEarnedHardcore', $unlocks[$achievement3->ID]);

        // repeat the softcore unlock
        $scoreBefore = $user1->RAPoints;
        $softcoreScoreBefore = $user1->RASoftcorePoints;
        $authorContribCountBefore = $author1->ContribCount;
        $authorContribYieldBefore = $author1->ContribYield;

        $newNow = $now->clone()->addMinutes(5);
        Carbon::setTestNow($newNow);
        $this->assertNotEquals($now, Carbon::now());

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 0, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => false,
                // client detects the "User already has" and does not report this as an error.
                'Error' => 'User already has this achievement unlocked.',
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        // player score should not have increased
        $user2 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($scoreBefore, $user2->RAPoints);
        $this->assertEquals($softcoreScoreBefore, $user2->RASoftcorePoints);
        $this->assertEquals($truePointsBefore, $user2->TrueRAPoints);

        // author contribution should not have increased
        $author2 = User::firstWhere('User', $achievement3->Author);
        $this->assertEquals($authorContribYieldBefore, $author2->ContribYield);
        $this->assertEquals($authorContribCountBefore, $author2->ContribCount);

        // make sure the unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement3, UnlockMode::Softcore);
        $this->assertEquals($now, $unlockTime);
        $this->assertNull($this->getUnlockTime($user2, $achievement3, UnlockMode::Hardcore));

        // do the hardcore unlock
        $validationHash = $this->buildValidationHash($achievement3, $this->user, 1);
        $scoreBefore = $user1->RAPoints;
        $softcoreScoreBefore = $user1->RASoftcorePoints;
        $authorContribCountBefore = $author1->ContribCount;
        $authorContribYieldBefore = $author1->ContribYield;

        $this->get($this->apiUrl('awardachievement', ['a' => $achievement3->ID, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement3->ID,
                'AchievementsRemaining' => 2,
                'Score' => $scoreBefore + $achievement3->Points,
                'SoftcoreScore' => $softcoreScoreBefore - $achievement3->Points,
            ]);

        // player score should have adjusted
        $user2 = User::firstWhere('User', $this->user->User);
        $this->assertEquals($scoreBefore + $achievement3->Points, $user2->RAPoints);
        $this->assertEquals($softcoreScoreBefore - $achievement3->Points, $user2->RASoftcorePoints);
        $this->assertEquals($truePointsBefore + $achievement3->TruePoints, $user2->TrueRAPoints);

        // author contribution should not have increased
        $author2 = User::firstWhere('User', $achievement3->Author);
        $this->assertEquals($authorContribYieldBefore, $author2->ContribYield);
        $this->assertEquals($authorContribCountBefore, $author2->ContribCount);

        // make sure the unlock cache was updated
        $unlocks = getUserAchievementUnlocksForGame($this->user->User, $game->ID);
        $this->assertEquals([$achievement1->ID, $achievement3->ID], array_keys($unlocks));
        $this->assertEquals($now, $unlocks[$achievement3->ID]['DateEarned']);
        $this->assertEquals($newNow, $unlocks[$achievement3->ID]['DateEarnedHardcore']);

        // make sure the softcore unlock time didn't change
        $unlockTime = $this->getUnlockTime($user2, $achievement3, UnlockMode::Softcore);
        $this->assertEquals($now, $unlockTime);
        $unlockTime = $this->getUnlockTime($user2, $achievement3, UnlockMode::Hardcore);
        $this->assertEquals($newNow, $unlockTime);
    }

    public function testErrors(): void
    {
        $now = Carbon::now()->clone()->subMinutes(5)->startOfSecond();
        Carbon::setTestNow($now);

        /** @var User $author */
        $author = User::factory()->create(['ContribCount' => 1234, 'ContribYield' => 5678]);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameHash = '0123456789abcdeffedcba9876543210';
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $author->User]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->create(['GameID' => $game->ID, 'Author' => $author->User]);

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);

        $scoreBefore = $this->user->RAPoints;
        $softcoreScoreBefore = $this->user->RASoftcorePoints;

        $validationHash = md5('999999' . $this->user->User . '1');
        $this->get($this->apiUrl('awardachievement', ['a' => 999999, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Data not found for achievement 999999',
                'AchievementID' => 999999,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

        $validationHash = $this->buildValidationHash($achievement4, $this->user, 1);
        $this->get($this->apiUrl('awardachievement', ['a' => $achievement4->ID, 'h' => 1, 'm' => $gameHash, 'v' => $validationHash]))
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unofficial achievements cannot be unlocked',
                'AchievementID' => $achievement4->ID,
                'Score' => $scoreBefore,
                'SoftcoreScore' => $softcoreScoreBefore,
            ]);

       // player score should not have adjusted
       $user1 = User::firstWhere('User', $this->user->User);
       $this->assertEquals($scoreBefore, $user1->RAPoints);
       $this->assertEquals($softcoreScoreBefore, $user1->RASoftcorePoints);
    }
}
