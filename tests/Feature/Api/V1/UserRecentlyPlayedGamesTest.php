<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UserRecentlyPlayedGamesTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetUserRecentlyPlayedGames', [
            'c' => 'nope',
            'o' => -1,
        ]))
            ->assertJsonValidationErrors([
                'u',
                'c',
                'o',
            ]);
    }

    public function testGetUserRecentlyPlayedGamesUnknownUser(): void
    {
        $this->get($this->apiUrl('GetUserRecentlyPlayedGames', ['u' => 'nonExistant']))
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetUserRecentlyPlayedGames(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/001234.png',
            'ImageTitle' => '/Images/000002.png',
            'ImageIngame' => '/Images/000003.png',
            'ImageBoxArt' => '/Images/000004.png',
        ]);
        $publishedAchievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageTitle' => '/Images/000005.png',
            'ImageIngame' => '/Images/000006.png',
            'ImageBoxArt' => '/Images/000007.png',
        ]);
        /** @var User $user */
        $user = User::factory()->create();

        $hardcoreAchievement = $publishedAchievements->get(0);
        $this->addHardcoreUnlock($user, $hardcoreAchievement, Carbon::now()->subDays(1));
        $softcoreAchievement = $publishedAchievements->get(1);
        $this->addSoftcoreUnlock($user, $softcoreAchievement, Carbon::now()->subDays(1));

        $playerGame = $user->playerGame($game);

        // addHardcoreUnlock will create a player_game for game. need to manually create one for game2
        $playerGame2 = new PlayerGame([
            'user_id' => $user->ID,
            'game_id' => $game2->ID,
            'created_at' => Carbon::now()->subHours(1),
            'last_played_at' => Carbon::now()->subMinutes(5),
        ]);
        $playerGame2->save();

        $this->get($this->apiUrl('GetUserRecentlyPlayedGames', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'GameID' => $game2->ID,
                    'Title' => $game2->Title,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'ImageIcon' => $game2->ImageIcon,
                    'ImageTitle' => $game2->ImageTitle,
                    'ImageIngame' => $game2->ImageIngame,
                    'ImageBoxArt' => $game2->ImageBoxArt,
                    'LastPlayed' => $playerGame2->last_played_at->__toString(),
                    'NumPossibleAchievements' => 0,
                    'PossibleScore' => 0,
                    'NumAchieved' => 0,
                    'ScoreAchieved' => 0,
                    'NumAchievedHardcore' => 0,
                    'ScoreAchievedHardcore' => 0,
                ],
                [
                    'GameID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageTitle' => $game->ImageTitle,
                    'ImageIngame' => $game->ImageIngame,
                    'ImageBoxArt' => $game->ImageBoxArt,
                    'LastPlayed' => $playerGame->last_played_at->__toString(),
                    'NumPossibleAchievements' => 3,
                    'PossibleScore' => $publishedAchievements->get(0)->Points +
                                       $publishedAchievements->get(1)->Points +
                                       $publishedAchievements->get(2)->Points,
                    'NumAchieved' => 2, // hardcore also unlocks softcore
                    'ScoreAchieved' => $softcoreAchievement->Points + $hardcoreAchievement->Points,
                    'NumAchievedHardcore' => 1,
                    'ScoreAchievedHardcore' => $hardcoreAchievement->Points,
                ],
            ]);
    }
}
