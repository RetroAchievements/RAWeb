<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\UnlockMode;
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
            ->assertJson([
                'Count' => 0,
                'Total' => 0,
                'Results' => [],
            ]);
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
        $me = User::factory()->create(['username' => 'myUser']);

        /** @var System $system */
        $system = System::factory()->create(['id' => 1]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['system_id' => $system->id]);
        $gameOneAchievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameOne->id]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['system_id' => $system->id]);
        $gameTwoAchievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameTwo->id]);
        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['system_id' => $system->id]);
        $gameThreeAchievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameThree->id]);
        /** @var Game $gameFour */
        $gameFour = Game::factory()->create(['system_id' => $system->id]);
        $gameFourAchievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameFour->id]);
        /** @var Game $gameFive */
        $gameFive = Game::factory()->create(['system_id' => $system->id]);
        $gameFiveAchievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameFive->id]);
        /** @var Game $gameSix */
        $gameSix = Game::factory()->create(['system_id' => $system->id]);
        $gameSixAchievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameSix->id]);
        /** @var Game $gameSeven */
        $gameSeven = Game::factory()->create(['system_id' => $system->id]);
        $gameSevenAchievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameSeven->id]);
        /** @var Game $gameEight */
        $gameEight = Game::factory()->create(['system_id' => $system->id]);
        $gameEightAchievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameEight->id]);
        /** @var Game $gameNine */
        $gameNine = Game::factory()->create(['system_id' => $system->id]);
        $gameNineAchievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameNine->id]);
        /** @var Game $gameTen */
        $gameTen = Game::factory()->create(['system_id' => $system->id]);
        $gameTenAchievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameTen->id]);

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

        $this->get($this->apiUrl('GetUserCompletionProgress', ['u' => $me->username]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 10,
                'Total' => 10,
                'Results' => [
                    [
                        "GameID" => $gameOne->id,
                        "Title" => $gameOne->title,
                        "ImageIcon" => $gameOne->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "ConsoleName" => $system->name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(5)->toIso8601String(),
                        "HighestAwardKind" => "mastered",
                        "HighestAwardDate" => Carbon::now()->subMinutes(1)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameTwo->id,
                        "Title" => $gameTwo->title,
                        "ImageIcon" => $gameTwo->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "ConsoleName" => $system->name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(10)->toIso8601String(),
                        "HighestAwardKind" => "mastered",
                        "HighestAwardDate" => Carbon::now()->subMinutes(2)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameThree->id,
                        "Title" => $gameThree->title,
                        "ImageIcon" => $gameThree->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "ConsoleName" => $system->name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(15)->toIso8601String(),
                        "HighestAwardKind" => "beaten-hardcore",
                        "HighestAwardDate" => Carbon::now()->subMinutes(12)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameFour->id,
                        "Title" => $gameFour->title,
                        "ImageIcon" => $gameFour->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "ConsoleName" => $system->name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(20)->toIso8601String(),
                        "HighestAwardKind" => "completed",
                        "HighestAwardDate" => Carbon::now()->subMinutes(5)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameFive->id,
                        "Title" => $gameFive->title,
                        "ImageIcon" => $gameFive->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "ConsoleName" => $system->name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(25)->toIso8601String(),
                        "HighestAwardKind" => "completed",
                        "HighestAwardDate" => Carbon::now()->subMinutes(10)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameSix->id,
                        "Title" => $gameSix->title,
                        "ImageIcon" => $gameSix->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "ConsoleName" => $system->name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(30)->toIso8601String(),
                        "HighestAwardKind" => "completed",
                        "HighestAwardDate" => Carbon::now()->subMinutes(15)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameSeven->id,
                        "Title" => $gameSeven->title,
                        "ImageIcon" => $gameSeven->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "ConsoleName" => $system->name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(35)->toIso8601String(),
                        "HighestAwardKind" => "mastered",
                        "HighestAwardDate" => Carbon::now()->subMinutes(3)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameEight->id,
                        "Title" => $gameEight->title,
                        "ImageIcon" => $gameEight->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "ConsoleName" => $system->name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(40)->toIso8601String(),
                        "HighestAwardKind" => "mastered",
                        "HighestAwardDate" => Carbon::now()->subMinutes(4)->toIso8601String(),
                    ],
                    [
                        "GameID" => $gameNine->id,
                        "Title" => $gameNine->title,
                        "ImageIcon" => $gameNine->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "ConsoleName" => $system->name,
                        "MaxPossible" => 6,
                        "NumAwarded" => 1,
                        "NumAwardedHardcore" => 1,
                        "MostRecentAwardedDate" => Carbon::now()->subMinutes(45)->toIso8601String(),
                        "HighestAwardKind" => null,
                        "HighestAwardDate" => null,
                    ],
                    [
                        "GameID" => $gameTen->id,
                        "Title" => $gameTen->title,
                        "ImageIcon" => $gameTen->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "ConsoleName" => $system->name,
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
