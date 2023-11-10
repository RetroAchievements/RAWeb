<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Community\Enums\AwardType;
use App\Platform\Actions\UpdatePlayerGameMetrics;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\PlayerBadge;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class BeatenGameTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testNoProgressionAchievementsAvailable(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);
        $publishedAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $publishedAchievements->get(0), Carbon::now());

        $playerGame = $user->playerGame($game);

        $this->assertNull($playerGame->beaten_at);
        $this->assertNull($playerGame->beaten_hardcore_at);
    }

    public function testNoProgressionAchievementsUnlocked(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        Achievement::factory()->published()->progression()->count(6)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $achievement, Carbon::now());

        $playerGame = $user->playerGame($game);

        $this->assertNull($playerGame->beaten_at);
        $this->assertNull($playerGame->beaten_hardcore_at);
    }

    public function testSomeProgressionAchievementsUnlocked(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());

        $playerGame = $user->playerGame($game);

        $this->assertNull($playerGame->beaten_at);
        $this->assertNull($playerGame->beaten_hardcore_at);
    }

    public function testAllProgressionButNoWinConditionAchievementsUnlocked(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());

        $playerGame = $user->playerGame($game);

        $this->assertNull($playerGame->beaten_at);
        $this->assertNull($playerGame->beaten_hardcore_at);
    }

    public function testAllProgressionAchievementsUnlockedAndNoWinConditionExists(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());

        $playerGame = $user->playerGame($game);

        $this->assertNotNull($playerGame->beaten_at);
        $this->assertNull($playerGame->beaten_hardcore_at);
    }

    public function testAllProgressionAndOneWinConditionAchievementsUnlocked(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        $playerGame = $user->playerGame($game);

        $this->assertNotNull($playerGame->beaten_at);
        $this->assertNotNull($playerGame->beaten_hardcore_at);
    }

    public function testNoProgressionAndOneWinConditionAchievementUnlocked(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        $playerGame = $user->playerGame($game);

        $this->assertNull($playerGame->beaten_at);
        $this->assertNull($playerGame->beaten_hardcore_at);
    }

    public function testSomeHardcoreAndSomeSoftcoreUnlocks(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addSoftcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        $playerGame = $user->playerGame($game);

        $this->assertNotNull($playerGame->beaten_at);
        $this->assertNull($playerGame->beaten_hardcore_at);
    }

    public function testSomeHardcoreAndSomeSoftcoreUnlocks2(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievements = Achievement::factory()->published()->winCondition()->count(2)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());
        $this->addHardcoreUnlock($user, $winConditionAchievements->get(0), Carbon::now());
        $this->addSoftcoreUnlock($user, $winConditionAchievements->get(1), Carbon::now());

        $playerGame = $user->playerGame($game);

        $this->assertNotNull($playerGame->beaten_at);
        $this->assertNotNull($playerGame->beaten_hardcore_at);
    }

    public function testSoftcoreAwardAssignment(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addSoftcoreUnlock($user, $progressionAchievements->get(0), Carbon::now()->subMinutes(30));
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(1), Carbon::now()->subMinutes(30));
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(2), Carbon::now()->subMinutes(30));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now()->subMinutes(30));
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(4), Carbon::now()->subMinutes(15));
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now()->subMinutes(10));

        $this->assertEquals(1, PlayerBadge::where('User', $user->User)->where('AwardType', AwardType::GameBeaten)->count());
        $this->assertNotNull(
            $user->playerBadges()
                ->where('AwardType', AwardType::GameBeaten)
                ->where('AwardData', $game->ID)
                ->where('AwardDataExtra', UnlockMode::Softcore)
                ->where('AwardDate', Carbon::now()->subMinutes(10))
                ->first()
        );
    }

    public function testHardcoreAwardAssignment(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now()->subMinutes(45));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now()->subMinutes(40));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now()->subMinutes(35));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now()->subMinutes(30));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now()->subMinutes(25));
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now()->subMinutes(20));

        $this->assertEquals(1, PlayerBadge::where('User', $user->User)->where('AwardType', AwardType::GameBeaten)->count());
        $this->assertNotNull(PlayerBadge::where('User', $user->User)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->where('AwardDate', Carbon::now()->subMinutes(20))
            ->first()
        );
    }

    public function testBeatenAwardRevocation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now());
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now());
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now());

        Achievement::factory()->published()->progression()->create(['GameID' => $game->ID]);

        // TODO trigger achievement set update which will trigger UpdatePlayerGameMetrics
        (new UpdatePlayerGameMetrics())->execute($user->playerGame($game));

        $this->assertEquals(0, PlayerBadge::where('User', $user->User)->where('AwardType', AwardType::GameBeaten)->count());
    }

    public function testBeatenAwardRevocation2(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $progressionAchievements = Achievement::factory()->published()->progression()->count(5)->create(['GameID' => $game->ID]);
        $winConditionAchievement = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID]);

        // First, the user will get a softcore beaten award.
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(0), Carbon::now()->subMinutes(55));
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(1), Carbon::now()->subMinutes(50));
        $this->addSoftcoreUnlock($user, $progressionAchievements->get(2), Carbon::now()->subMinutes(45));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(3), Carbon::now()->subMinutes(40));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(4), Carbon::now()->subMinutes(35));
        $this->addHardcoreUnlock($user, $winConditionAchievement, Carbon::now()->subMinutes(30));

        // Now they'll upgrade it to hardcore by unlocking the remaining achievements in hardcore.
        $this->addHardcoreUnlock($user, $progressionAchievements->get(0), Carbon::now()->subMinutes(25));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(1), Carbon::now()->subMinutes(20));
        $this->addHardcoreUnlock($user, $progressionAchievements->get(2), Carbon::now()->subMinutes(15));

        // A new achievement gets added and marked as Progression.
        /** @var Achievement $newAchievement */
        $newAchievement = Achievement::factory()->published()->progression()->create(['GameID' => $game->ID]);

        // TODO trigger achievement set update which will trigger UpdatePlayerGameMetrics
        (new UpdatePlayerGameMetrics())->execute($user->playerGame($game));

        $this->assertEquals(0, PlayerBadge::where('User', $user->User)->where('AwardType', AwardType::GameBeaten)->count());

        // The user unlocks it in softcore.
        $this->addSoftcoreUnlock($user, $newAchievement, Carbon::now()->subMinutes(10));
        $this->assertEquals(1, PlayerBadge::where('User', $user->User)->where('AwardType', AwardType::GameBeaten)->count());
        $this->assertNotNull(PlayerBadge::where('User', $user->User)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Softcore)
            ->where('AwardDate', Carbon::now()->subMinutes(10))
            ->first()
        );

        // The user unlocks it in hardcore.
        $this->addHardcoreUnlock($user, $newAchievement, Carbon::now()->subMinutes(5));
        $this->assertEquals(1, PlayerBadge::where('User', $user->User)->where('AwardType', AwardType::GameBeaten)->count());
        $this->assertNotNull(PlayerBadge::where('User', $user->User)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->where('AwardDate', Carbon::now()->subMinutes(5))
            ->first()
        );
    }

    public function testBeatenAwardRevocation3(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID]);
        /** @var Achievement $progressionAchievement */
        $progressionAchievement = Achievement::factory()->published()->progression()->create(['GameID' => $game->ID]);

        // The user unlocks the one progression achievement. They should be given beaten game credit.
        $this->addHardcoreUnlock($user, $progressionAchievement);
        $this->assertEquals(1, PlayerBadge::where('User', $user->User)->where('AwardType', AwardType::GameBeaten)->count());

        // Now, pretend a dev removes the progression type from the achievement.
        $progressionAchievement->type = null;
        $progressionAchievement->save();
        $progressionAchievement->refresh();

        // TODO trigger achievement set update which will trigger UpdatePlayerGameMetrics
        (new UpdatePlayerGameMetrics())->execute($user->playerGame($game));

        // The beaten game award should be revoked.
        $this->assertEquals(0, PlayerBadge::where('User', $user->User)->where('AwardType', AwardType::GameBeaten)->count());
    }

    public function testRetroactiveAward(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $gameAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $gameAchievements->get(0), Carbon::now()->subHours(6));
        $this->addHardcoreUnlock($user, $gameAchievements->get(1), Carbon::now()->subHours(6));
        $this->addHardcoreUnlock($user, $gameAchievements->get(2), Carbon::now()->subHours(5));
        $this->addHardcoreUnlock($user, $gameAchievements->get(3), Carbon::now()->subHours(5));
        $this->addHardcoreUnlock($user, $gameAchievements->get(4), Carbon::now()->subHours(4));
        $this->addHardcoreUnlock($user, $gameAchievements->get(5), Carbon::now()->subHours(3));

        foreach ($gameAchievements as $achievement) {
            $achievement->type = AchievementType::Progression;
            $achievement->save();
        }

        // TODO trigger achievement set update which will trigger UpdatePlayerGameMetrics
        (new UpdatePlayerGameMetrics())->execute($user->playerGame($game));

        $this->assertEquals(1, PlayerBadge::where('User', $user->User)->where('AwardType', AwardType::GameBeaten)->count());
        $this->assertNotNull(PlayerBadge::where('User', $user->User)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->where('AwardDate', Carbon::now()->subHours(3))
            ->first()
        );
    }

    public function testRetroactiveAward2(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID]);
        $winConditionAchievements = Achievement::factory()->published()->winCondition()->count(2)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $winConditionAchievements->get(0), Carbon::now()->subHours(12));
        $this->addHardcoreUnlock($user, $winConditionAchievements->get(1), Carbon::now()->subHours(6));

        $this->assertEquals(1, PlayerBadge::where('User', $user->User)->where('AwardType', AwardType::GameBeaten)->count());
        $this->assertNotNull(PlayerBadge::where('User', $user->User)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->where('AwardDate', Carbon::now()->subHours(12))
            ->first()
        );
    }

    public function testRetroactiveAward3(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create();
        $game = $this->seedGame(withHash: false);

        $gameAchievements = Achievement::factory()->published()->count(7)->create(['GameID' => $game->ID]);

        $this->addHardcoreUnlock($user, $gameAchievements->get(0), Carbon::now()->subHours(6)); // progression
        $this->addHardcoreUnlock($user, $gameAchievements->get(1), Carbon::now()->subHours(6)); // progression
        $this->addHardcoreUnlock($user, $gameAchievements->get(2), Carbon::now()->subHours(5)); // progression
        $this->addHardcoreUnlock($user, $gameAchievements->get(3), Carbon::now()->subHours(5)); // progression
        $this->addHardcoreUnlock($user, $gameAchievements->get(4), Carbon::now()->subHours(4)); // progression
        $this->addHardcoreUnlock($user, $gameAchievements->get(6), Carbon::now()->subHours(3)); // win condition

        $this->addHardcoreUnlock($user, $gameAchievements->get(5), Carbon::now()->subHours(1)); // progression

        $gameAchievements->get(0)->type = AchievementType::Progression;
        $gameAchievements->get(1)->type = AchievementType::Progression;
        $gameAchievements->get(2)->type = AchievementType::Progression;
        $gameAchievements->get(3)->type = AchievementType::Progression;
        $gameAchievements->get(4)->type = AchievementType::Progression;
        $gameAchievements->get(5)->type = AchievementType::WinCondition;
        $gameAchievements->get(6)->type = AchievementType::Progression;
        foreach ($gameAchievements as $achievement) {
            $achievement->save();
        }

        // TODO trigger achievement set update which will trigger UpdatePlayerGameMetrics
        (new UpdatePlayerGameMetrics())->execute($user->playerGame($game));

        $this->assertNotNull(PlayerBadge::where('User', $user->User)
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->where('AwardDate', Carbon::now()->subHours(1))
            ->first()
        );
    }
}
