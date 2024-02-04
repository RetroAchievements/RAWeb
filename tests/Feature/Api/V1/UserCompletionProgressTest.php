<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UserCompletionProgressTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetUserCompletionProgress'))
            ->assertJsonValidationErrors([
                'u',
            ]);
    }

    public function testGetUserCompletionProgressUnknownUser(): void
    {
        $this->get($this->apiUrl('GetUserCompletionProgress', ['u' => 'nonExistant']))
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetUserCompletionProgress(): void
    {
        Carbon::setTestNow(Carbon::now());

        /**
         * Set up a user with 10 games played:
         * - 2 unfinished
         * - 3 beaten
         * - 1 beaten (softcore)
         * - 3 completed (one of which is also beaten softcore)
         * - 4 mastered (two of which are also beaten hardcore)
         */

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
        $this->addHardcoreUnlock($me, $gameOneAchievements->get(0), Carbon::now()->subMinutes(5));
        $this->addHardcoreUnlock($me, $gameTwoAchievements->get(0), Carbon::now()->subMinutes(10));
        $this->addHardcoreUnlock($me, $gameThreeAchievements->get(0), Carbon::now()->subMinutes(15));
        $this->addHardcoreUnlock($me, $gameFourAchievements->get(0), Carbon::now()->subMinutes(20));
        $this->addHardcoreUnlock($me, $gameFiveAchievements->get(0), Carbon::now()->subMinutes(25));
        $this->addHardcoreUnlock($me, $gameSixAchievements->get(0), Carbon::now()->subMinutes(30));
        $this->addHardcoreUnlock($me, $gameSevenAchievements->get(0), Carbon::now()->subMinutes(35));
        $this->addHardcoreUnlock($me, $gameEightAchievements->get(0), Carbon::now()->subMinutes(40));
        $this->addHardcoreUnlock($me, $gameNineAchievements->get(0), Carbon::now()->subMinutes(45));
        $this->addHardcoreUnlock($me, $gameTenAchievements->get(0), Carbon::now()->subMinutes(50));

        $this->addSoftcoreUnlock($me, $gameOneAchievements->get(0), Carbon::now()->subMinutes(5));
        $this->addSoftcoreUnlock($me, $gameTwoAchievements->get(0), Carbon::now()->subMinutes(10));
        $this->addSoftcoreUnlock($me, $gameThreeAchievements->get(0), Carbon::now()->subMinutes(15));
        $this->addSoftcoreUnlock($me, $gameFourAchievements->get(0), Carbon::now()->subMinutes(20));
        $this->addSoftcoreUnlock($me, $gameFiveAchievements->get(0), Carbon::now()->subMinutes(25));
        $this->addSoftcoreUnlock($me, $gameSixAchievements->get(0), Carbon::now()->subMinutes(30));
        $this->addSoftcoreUnlock($me, $gameSevenAchievements->get(0), Carbon::now()->subMinutes(35));
        $this->addSoftcoreUnlock($me, $gameEightAchievements->get(0), Carbon::now()->subMinutes(40));
        $this->addSoftcoreUnlock($me, $gameNineAchievements->get(0), Carbon::now()->subMinutes(45));
        $this->addSoftcoreUnlock($me, $gameTenAchievements->get(0), Carbon::now()->subMinutes(50));

        // Now, grant the various awards.
        // 3 Beaten (hardcore)
        $this->addGameBeatenAward($me, $gameOne, awardTime: Carbon::now()->subMinutes(5));
        $this->addGameBeatenAward($me, $gameTwo, awardTime: Carbon::now()->subMinutes(10));
        $this->addGameBeatenAward($me, $gameThree, awardTime: Carbon::now()->subMinutes(12));

        // 1 Beaten (softcore)
        $this->addGameBeatenAward($me, $gameFour, UnlockMode::Softcore, Carbon::now()->subMinutes(20));

        // 3 Completed
        $this->addMasteryBadge($me, $gameFour, UnlockMode::Softcore, Carbon::now()->subMinutes(5));
        $this->addMasteryBadge($me, $gameFive, UnlockMode::Softcore, Carbon::now()->subMinutes(10));
        $this->addMasteryBadge($me, $gameSix, UnlockMode::Softcore, Carbon::now()->subMinutes(15));

        // 4 Mastered
        $this->addMasteryBadge($me, $gameOne, awardTime: Carbon::now()->subMinutes(1));
        $this->addMasteryBadge($me, $gameTwo, awardTime: Carbon::now()->subMinutes(2));
        $this->addMasteryBadge($me, $gameSeven, awardTime: Carbon::now()->subMinutes(3));
        $this->addMasteryBadge($me, $gameEight, awardTime: Carbon::now()->subMinutes(4));

        $this->get($this->apiUrl('GetUserCompletionProgress', ['u' => $me->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 10,
                'Total' => 10,
                'Results' => [
                    [
                        "GameID" => $gameOne->ID,
                        "Title" => $gameOne->Title,
                        "ImageIcon" => $gameOne->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "ConsoleName" => $system->Name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(5)->toIso8601String(),
                        "HighestAwardKind" => "mastered",
                        "HighestAwardDate" => Carbon::now()->subMinutes(1)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameTwo->ID,
                        "Title" => $gameTwo->Title,
                        "ImageIcon" => $gameTwo->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "ConsoleName" => $system->Name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(10)->toIso8601String(),
                        "HighestAwardKind" => "mastered",
                        "HighestAwardDate" => Carbon::now()->subMinutes(2)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameThree->ID,
                        "Title" => $gameThree->Title,
                        "ImageIcon" => $gameThree->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "ConsoleName" => $system->Name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(15)->toIso8601String(),
                        "HighestAwardKind" => "beaten-hardcore",
                        "HighestAwardDate" => Carbon::now()->subMinutes(12)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameFour->ID,
                        "Title" => $gameFour->Title,
                        "ImageIcon" => $gameFour->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "ConsoleName" => $system->Name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(20)->toIso8601String(),
                        "HighestAwardKind" => "completed",
                        "HighestAwardDate" => Carbon::now()->subMinutes(5)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameFive->ID,
                        "Title" => $gameFive->Title,
                        "ImageIcon" => $gameFive->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "ConsoleName" => $system->Name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(25)->toIso8601String(),
                        "HighestAwardKind" => "completed",
                        "HighestAwardDate" => Carbon::now()->subMinutes(10)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameSix->ID,
                        "Title" => $gameSix->Title,
                        "ImageIcon" => $gameSix->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "ConsoleName" => $system->Name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(30)->toIso8601String(),
                        "HighestAwardKind" => "completed",
                        "HighestAwardDate" => Carbon::now()->subMinutes(15)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameSeven->ID,
                        "Title" => $gameSeven->Title,
                        "ImageIcon" => $gameSeven->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "ConsoleName" => $system->Name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(35)->toIso8601String(),
                        "HighestAwardKind" => "mastered",
                        "HighestAwardDate" => Carbon::now()->subMinutes(3)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameEight->ID,
                        "Title" => $gameEight->Title,
                        "ImageIcon" => $gameEight->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "ConsoleName" => $system->Name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(40)->toIso8601String(),
                        "HighestAwardKind" => "mastered",
                        "HighestAwardDate" => Carbon::now()->subMinutes(4)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameNine->ID,
                        "Title" => $gameNine->Title,
                        "ImageIcon" => $gameNine->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "ConsoleName" => $system->Name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(45)->toIso8601String(),
                        "HighestAwardKind" => null,
                        "HighestAwardDate" => null,
                    ],
                    [
                        "GameID" => $gameTen->ID,
                        "Title" => $gameTen->Title,
                        "ImageIcon" => $gameTen->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "ConsoleName" => $system->Name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(50)->toIso8601String(),
                        "HighestAwardKind" => null,
                        "HighestAwardDate" => null,
                    ],
                ],
            ]);
    }
}
