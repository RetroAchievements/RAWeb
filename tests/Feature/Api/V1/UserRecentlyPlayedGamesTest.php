<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
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
        $game = $this->seedGame(achievements: 3);
        $game->fill([
            'image_icon_asset_path' => '/Images/001234.png',
            'image_title_asset_path' => '/Images/000002.png',
            'image_ingame_asset_path' => '/Images/000003.png',
            'image_box_art_asset_path' => '/Images/000004.png',
        ]);
        $game->save();
        $publishedAchievements = $game->achievements;
        $game2 = $this->seedGame();
        $game2->fill([
            'image_title_asset_path' => '/Images/000005.png',
            'image_ingame_asset_path' => '/Images/000006.png',
            'image_box_art_asset_path' => '/Images/000007.png',
        ]);
        $game2->save();
        /** @var User $user */
        $user = User::factory()->create();

        $unlockTime = Carbon::now()->subDays(1);
        $hardcoreAchievement = $publishedAchievements->get(0);
        $this->addHardcoreUnlock($user, $hardcoreAchievement, $unlockTime);
        $softcoreAchievement = $publishedAchievements->get(1);
        $this->addSoftcoreUnlock($user, $softcoreAchievement, $unlockTime);

        $playerSession = $user->playerSessions()->where('game_id', $game->id)->first();
        $playerSession->rich_presence_updated_at = $unlockTime;
        $playerSession->save();

        $playerGame = $user->playerGame($game);
        $playerGame->last_played_at = $unlockTime;
        $playerGame->save();

        // addHardcoreUnlock will create a player_game for game. need to manually create one for game2
        $playerGame2 = new PlayerGame([
            'user_id' => $user->id,
            'game_id' => $game2->id,
            'created_at' => Carbon::now()->subHours(1),
            'last_played_at' => Carbon::now()->subMinutes(5),
        ]);
        $playerGame2->save();

        $this->get($this->apiUrl('GetUserRecentlyPlayedGames', ['u' => $user->username]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'GameID' => $game2->id,
                    'Title' => $game2->title,
                    'ConsoleID' => $game2->system->id,
                    'ConsoleName' => $game2->system->name,
                    'ImageIcon' => $game2->image_icon_asset_path,
                    'ImageTitle' => $game2->image_title_asset_path,
                    'ImageIngame' => $game2->image_ingame_asset_path,
                    'ImageBoxArt' => $game2->image_box_art_asset_path,
                    'LastPlayed' => $playerGame2->last_played_at->__toString(),
                    'NumPossibleAchievements' => 0,
                    'PossibleScore' => 0,
                    'NumAchieved' => 0,
                    'ScoreAchieved' => 0,
                    'NumAchievedHardcore' => 0,
                    'ScoreAchievedHardcore' => 0,
                ],
                [
                    'GameID' => $game->id,
                    'Title' => $game->title,
                    'ConsoleID' => $game->system->id,
                    'ConsoleName' => $game->system->name,
                    'ImageIcon' => $game->image_icon_asset_path,
                    'ImageTitle' => $game->image_title_asset_path,
                    'ImageIngame' => $game->image_ingame_asset_path,
                    'ImageBoxArt' => $game->image_box_art_asset_path,
                    'LastPlayed' => $playerGame->last_played_at->__toString(),
                    'NumPossibleAchievements' => 3,
                    'PossibleScore' => $publishedAchievements->get(0)->points +
                                       $publishedAchievements->get(1)->points +
                                       $publishedAchievements->get(2)->points,
                    'NumAchieved' => 2, // hardcore also unlocks softcore
                    'ScoreAchieved' => $softcoreAchievement->points + $hardcoreAchievement->points,
                    'NumAchievedHardcore' => 1,
                    'ScoreAchievedHardcore' => $hardcoreAchievement->points,
                ],
            ]);
    }
}
