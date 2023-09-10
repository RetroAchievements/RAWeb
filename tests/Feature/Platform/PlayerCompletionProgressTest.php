<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Community\Enums\AwardType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\System;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlayerCompletionProgressTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    public function testItRendersWithoutCrashing(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['User' => 'mockUser']);

        $this->actingAs($user)->get('/user/MockUser/progress')->assertStatus(200);
    }

    public function testItReturns401IfUnauthenticated(): void
    {
        User::factory()->create(['User' => 'mockUser']);

        $this->get('/user/MockUser/progress')->assertStatus(401);
    }

    public function testItReturns404IfTargetUserIsBanned(): void
    {
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);
        /** @var User $targetUser */
        $targetUser = User::factory()->create(['User' => 'targetUser', 'Permissions' => Permissions::Banned]);

        $this->actingAs($me)->get('/user/' . $targetUser->User . '/progress')->assertStatus(404);
    }

    public function testGivenUserHasNoGamesItRendersEmptyState(): void
    {
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);
        /** @var User $targetUser */
        $targetUser = User::factory()->create(['User' => 'targetUser']);

        $this
            ->actingAs($me)
            ->get('/user/' . $targetUser->User . '/progress')
            ->assertSeeTextInOrder([$targetUser->User . " doesn't", "games with achievement unlocks yet"]);
    }

    public function testEmptyStateMessageChangesIfViewingMyOwnProfile(): void
    {
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);

        $this
            ->actingAs($me)
            ->get('/user/' . $me->User . '/progress')
            ->assertSeeTextInOrder(["You don't", "games with achievement unlocks yet"]);
    }

    public function testCorrectHeadingIfViewingOwnProgress(): void
    {
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);

        $this
            ->actingAs($me)
            ->get('/user/' . $me->User . '/progress')
            ->assertSeeText("Your Completion Progress");
    }

    public function testCorrectHeadingIfViewingOtherPlayersProgress(): void
    {
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);
        /** @var User $targetUser */
        $targetUser = User::factory()->create(['User' => 'targetUser']);

        // "targetUser's Completion Progress"
        $this
            ->actingAs($me)
            ->get('/user/' . $targetUser->User . '/progress')
            ->assertSeeText($targetUser->User . "'s Completion Progress");
    }

    public function testCorrectHeadingIfViewingOtherPlayersProgress2(): void
    {
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);
        /** @var User $targetUser */
        $targetUser = User::factory()->create(['User' => 'luchaos']);

        // "luchaos' Completion Progress" (not "luchaos's")
        $this
            ->actingAs($me)
            ->get('/user/' . $targetUser->User . '/progress')
            ->assertSeeText($targetUser->User . "' Completion Progress");
    }

    public function testShowGamesWithNoFilteringApplied(): void
    {
        // Arrange
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);

        /** @var System $systemOne */
        $systemOne = System::factory()->create(['ID' => 1]);
        /** @var System $systemTwo */
        $systemTwo = System::factory()->create(['ID' => 2]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['ConsoleID' => $systemOne->ID]);
        $gameOneAchievements = Achievement::factory()->published()->count(10)->create(['GameID' => $gameOne->ID]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $systemTwo->ID]);
        $gameTwoAchievements = Achievement::factory()->published()->count(12)->create(['GameID' => $gameTwo->ID]);

        $this->addHardcoreUnlock($me, $gameOneAchievements->get(0), Carbon::now()->subMinutes(30));
        $this->addHardcoreUnlock($me, $gameOneAchievements->get(1), Carbon::now()->subMinutes(29));

        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(1));
        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(2));

        // Act
        $view = $this->actingAs($me)->get('/user/' . $me->User . '/progress');

        // Assert
        $view->assertSeeTextInOrder([$gameTwo->Title, $gameOne->Title]);
        $view->assertSeeTextInOrder(['3 of 12 achievements', '2 of 10 achievements']);
        $view->assertSeeTextInOrder([config('systems')[2]['name_short'], config('systems')[1]['name_short']]);
        $view->assertSeeTextInOrder(["2", "Played"]);
        $view->assertSeeTextInOrder(["2", "Unfinished"]);

        $view->assertDontSee("beaten-softcore-link"); // These two links shouldn't appear if the user has no softcore progress.
        $view->assertDontSee("completed-link");
    }

    public function testFilterBySystem(): void
    {
        // Arrange
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);

        /** @var System $systemOne */
        $systemOne = System::factory()->create(['ID' => 1]);
        /** @var System $systemTwo */
        $systemTwo = System::factory()->create(['ID' => 2]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['ConsoleID' => $systemOne->ID]);
        $gameOneAchievements = Achievement::factory()->published()->count(10)->create(['GameID' => $gameOne->ID]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $systemTwo->ID]);
        $gameTwoAchievements = Achievement::factory()->published()->count(12)->create(['GameID' => $gameTwo->ID]);

        $this->addHardcoreUnlock($me, $gameOneAchievements->get(0), Carbon::now()->subMinutes(30));
        $this->addHardcoreUnlock($me, $gameOneAchievements->get(1), Carbon::now()->subMinutes(29));

        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(1));
        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(2));

        // Act
        $view = $this->actingAs($me)->get('/user/' . $me->User . '/progress?filter[system]=1');

        // Assert
        $view->assertSeeText("1 Played");
        $view->assertDontSeeText("Viewing");
        $view->assertDontSeeText(config('systems')[2]['name_short']);
    }

    public function testFilterByAwardStatus(): void
    {
        // Arrange
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);

        /** @var System $systemOne */
        $systemOne = System::factory()->create(['ID' => 1]);
        /** @var System $systemTwo */
        $systemTwo = System::factory()->create(['ID' => 2]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['ConsoleID' => $systemOne->ID]);
        $gameOneAchievements = Achievement::factory()->published()->count(10)->create(['GameID' => $gameOne->ID]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $systemTwo->ID]);
        $gameTwoAchievements = Achievement::factory()->published()->count(12)->create(['GameID' => $gameTwo->ID]);

        $this->addHardcoreUnlock($me, $gameOneAchievements->get(0), Carbon::now()->subMinutes(30));
        $this->addHardcoreUnlock($me, $gameOneAchievements->get(1), Carbon::now()->subMinutes(29));

        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(1));
        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(2));

        PlayerBadge::factory()->create(['User' => $me->User, 'AwardData' => $gameOne->ID, 'AwardType' => AwardType::Mastery, 'AwardDataExtra' => UnlockMode::Hardcore, 'AwardDate' => Carbon::now()]);

        // Act
        $view = $this->actingAs($me)->get('/user/' . $me->User . '/progress?filter[status]=eq-mastered');

        // Assert
        $view->assertSeeText("2 Played");
        $view->assertSeeText("1 Mastered");
        $view->assertSeeTextInOrder(["Viewing", "1", "of", "2", "games"]);
        $view->assertSeeText(config('systems')[1]['name_short']);
        $view->assertDontSeeText(config('systems')[2]['name_short']);
    }

    public function testCorrectAwardsCountsDisplayed(): void
    {
        /**
         * Set up a user with 10 games played:
         * - 2 unfinished
         * - 3 beaten
         * - 1 beaten (softcore)
         * - 3 completed (one of which is also beaten softcore)
         * - 4 mastered (two of which are also beaten hardcore)
         */

        // Arrange
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);

        /** @var System $system */
        $system = System::factory()->create(['ID' => 1]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameOneAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameOne->ID]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameTwoAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameTwo->ID]);
        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameThreeAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameThree->ID]);
        /** @var Game $gameFour */
        $gameFour = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameFourAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameFour->ID]);
        /** @var Game $gameFive */
        $gameFive = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameFiveAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameFive->ID]);
        /** @var Game $gameSix */
        $gameSix = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameSixAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameSix->ID]);
        /** @var Game $gameSeven */
        $gameSeven = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameSevenAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameSeven->ID]);
        /** @var Game $gameEight */
        $gameEight = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameEightAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameEight->ID]);
        /** @var Game $gameNine */
        $gameNine = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameNineAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameNine->ID]);
        /** @var Game $gameTen */
        $gameTen = Game::factory()->create(['ConsoleID' => $system->ID]);
        $gameTenAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameTen->ID]);

        // Unlocks on every game to be sure we have some progress.
        $this->addHardcoreUnlock($me, $gameOneAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameThreeAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameFourAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameFiveAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameSixAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameSevenAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameEightAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameNineAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameTenAchievements->get(0));

        $this->addSoftcoreUnlock($me, $gameOneAchievements->get(0));
        $this->addSoftcoreUnlock($me, $gameTwoAchievements->get(0));
        $this->addSoftcoreUnlock($me, $gameThreeAchievements->get(0));
        $this->addSoftcoreUnlock($me, $gameFourAchievements->get(0));
        $this->addSoftcoreUnlock($me, $gameFiveAchievements->get(0));
        $this->addSoftcoreUnlock($me, $gameSixAchievements->get(0));
        $this->addSoftcoreUnlock($me, $gameSevenAchievements->get(0));
        $this->addSoftcoreUnlock($me, $gameEightAchievements->get(0));
        $this->addSoftcoreUnlock($me, $gameNineAchievements->get(0));
        $this->addSoftcoreUnlock($me, $gameTenAchievements->get(0));

        // Now, grant the various awards.
        // 3 Beaten (hardcore)
        $this->addGameBeatenAward($me, $gameOne, awardTime: Carbon::now()->subMinutes(30));
        $this->addGameBeatenAward($me, $gameTwo, awardTime: Carbon::now()->subMinutes(30));
        $this->addGameBeatenAward($me, $gameThree, awardTime: Carbon::now());

        // 1 Beaten (softcore)
        $this->addGameBeatenAward($me, $gameFour, UnlockMode::Softcore, Carbon::now()->subMinutes(30));

        // 3 Completed
        $this->addMasteryBadge($me, $gameFour, UnlockMode::Softcore, Carbon::now());
        $this->addMasteryBadge($me, $gameFive, UnlockMode::Softcore, Carbon::now());
        $this->addMasteryBadge($me, $gameSix, UnlockMode::Softcore, Carbon::now());

        // 4 Mastered
        $this->addMasteryBadge($me, $gameOne, awardTime: Carbon::now());
        $this->addMasteryBadge($me, $gameTwo, awardTime: Carbon::now());
        $this->addMasteryBadge($me, $gameSeven, awardTime: Carbon::now());
        $this->addMasteryBadge($me, $gameEight, awardTime: Carbon::now());

        // Act
        $view = $this->actingAs($me)->get('/user/' . $me->User . '/progress');

        // Assert
        $view->assertSeeText("10 Played");
        $view->assertSeeText("2 Unfinished");
        $view->assertDontSeeText("Beaten (softcore)");
        $view->assertSeeText("1 Beaten");
        $view->assertSeeText("3 Completed");
        $view->assertSeeText("4 Mastered");
    }

    public function testMilestones(): void
    {
        /**
         * Two beaten games, six masteries, all unique games.
         * We should see 1st beaten game, 1st mastery, 5th mastery, latest mastery, and latest beaten game.
         */

        // Arrange
        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);

        /** @var System $system */
        $system = System::factory()->create(['ID' => 1]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Game One']);
        $gameOneAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameOne->ID]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Game Two']);
        $gameTwoAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameTwo->ID]);
        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Game Three']);
        $gameThreeAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameThree->ID]);
        /** @var Game $gameFour */
        $gameFour = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Game Four']);
        $gameFourAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameFour->ID]);
        /** @var Game $gameFive */
        $gameFive = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Game Five']);
        $gameFiveAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameFive->ID]);
        /** @var Game $gameSix */
        $gameSix = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Game Six']);
        $gameSixAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameSix->ID]);
        /** @var Game $gameSeven */
        $gameSeven = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Game Seven']);
        $gameSevenAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameSeven->ID]);
        /** @var Game $gameEight */
        $gameEight = Game::factory()->create(['ConsoleID' => $system->ID, 'Title' => 'Game Eight']);
        $gameEightAchievements = Achievement::factory()->published()->count(6)->create(['GameID' => $gameEight->ID]);

        // Unlocks on every game to be sure we have some progress.
        $this->addHardcoreUnlock($me, $gameOneAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameThreeAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameFourAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameFiveAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameSixAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameSevenAchievements->get(0));
        $this->addHardcoreUnlock($me, $gameEightAchievements->get(0));

        $this->addGameBeatenAward($me, $gameOne, awardTime: Carbon::now()->subMinutes(60));
        $this->addGameBeatenAward($me, $gameTwo, awardTime: Carbon::now()->subMinutes(55));
        $this->addMasteryBadge($me, $gameThree, awardTime: Carbon::now()->subMinutes(50));
        $this->addMasteryBadge($me, $gameFour, awardTime: Carbon::now()->subMinutes(45));
        $this->addMasteryBadge($me, $gameFive, awardTime: Carbon::now()->subMinutes(40));
        $this->addMasteryBadge($me, $gameSix, awardTime: Carbon::now()->subMinutes(35));
        $this->addMasteryBadge($me, $gameSeven, awardTime: Carbon::now()->subMinutes(30));
        $this->addMasteryBadge($me, $gameEight, awardTime: Carbon::now()->subMinutes(25));

        // Act
        $view = $this->actingAs($me)->get('/user/' . $me->User . '/progress');

        // Assert
        $view->assertSeeTextInOrder([
            $gameEight->Title, 'Latest mastery',
            $gameSeven->Title, '5th mastery',
            $gameThree->Title, '1st mastery',
            $gameTwo->Title, 'Latest game beaten',
            $gameOne->Title, '1st game beaten',
        ]);
    }
}
