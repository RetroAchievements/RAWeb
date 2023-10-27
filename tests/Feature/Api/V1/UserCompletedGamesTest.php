<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievementLegacy;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UserCompletedGamesTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetUserCompletedGames'))
            ->assertJsonValidationErrors([
                'u',
            ]);
    }

    public function testGetUserCompletedGamesUnknownUser(): void
    {
        $this->get($this->apiUrl('GetUserCompletedGames', ['u' => 'nonExistant']))
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetUserCompletedGames(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/001234.png',
        ]);
        $publishedAchievements = Achievement::factory()->published()->count(10)->create(['GameID' => $game->ID]);

        /** @var Game $game2 */
        $game2 = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/002345.png',
        ]);
        $publishedAchievements2 = Achievement::factory()->published()->count(20)->create(['GameID' => $game2->ID]);

        /** @var Game $game3 */
        $game3 = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/003456.png',
        ]);
        Achievement::factory()->published()->count(3)->create(['GameID' => $game3->ID]);

        /** @var Game $game4 */
        $game4 = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/004567.png',
        ]);
        $publishedAchievements4 = Achievement::factory()->published()->count(8)->create(['GameID' => $game4->ID]);

        foreach ($publishedAchievements as $ach) {
            $this->addHardcoreUnlock($user, $ach);
        }

        $index = 0;
        foreach ($publishedAchievements2 as $ach) {
            if ($index % 3 != 0) {
                if ($index % 2 == 0) {
                    // 2,4,8,10,14,16 hardcore
                    $this->addHardcoreUnlock($user, $ach);
                } else {
                    // 1,5,7,11,13,17,19 softcore
                    $this->addSoftcoreUnlock($user, $ach);
                }
            }
            $index++;
        }

        for ($index = 0; $index < 3; $index++) {
            $ach = $publishedAchievements4->get($index);
            $this->addSoftcoreUnlock($user, $ach);
        }

        $this->get($this->apiUrl('GetUserCompletedGames', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'GameID' => $game->ID,
                    'Title' => $game->Title,
                    'ImageIcon' => $game->ImageIcon,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'MaxPossible' => 10,
                    'NumAwarded' => 10,
                    'PctWon' => '1.0000',
                    'HardcoreMode' => '0',
                ],
                [
                    'GameID' => $game->ID,
                    'Title' => $game->Title,
                    'ImageIcon' => $game->ImageIcon,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'MaxPossible' => 10,
                    'NumAwarded' => 10,
                    'PctWon' => '1.0000',
                    'HardcoreMode' => '1',
                ],
                [
                    'GameID' => $game2->ID,
                    'Title' => $game2->Title,
                    'ImageIcon' => $game2->ImageIcon,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'MaxPossible' => 20,
                    'NumAwarded' => 13,
                    'PctWon' => '0.6500',
                    'HardcoreMode' => '0',
                ],
                [
                    'GameID' => $game2->ID,
                    'Title' => $game2->Title,
                    'ImageIcon' => $game2->ImageIcon,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'MaxPossible' => 20,
                    'NumAwarded' => 6,
                    'PctWon' => '0.3000',
                    'HardcoreMode' => '1',
                ],
                [
                    'GameID' => $game4->ID,
                    'Title' => $game4->Title,
                    'ImageIcon' => $game4->ImageIcon,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'MaxPossible' => 8,
                    'NumAwarded' => 3,
                    'PctWon' => '0.3750',
                    'HardcoreMode' => '0',
                ],
            ]);
    }
}
