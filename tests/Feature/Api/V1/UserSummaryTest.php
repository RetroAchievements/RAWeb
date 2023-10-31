<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\Rank;
use App\Platform\Actions\UpdateGameMetrics;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UserSummaryTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetUserSummary', [
            'g' => 'nope',
            'a' => -1,
        ]))
            ->assertJsonValidationErrors([
                'u',
                'g',
                'a',
            ]);
    }

    public function testGetUserSummaryUnknownUser(): void
    {
        $this->get($this->apiUrl('GetUserSummary', ['u' => 'nonExistant']))
            ->assertNotFound()
            ->assertJson(['ID' => null, 'User' => 'nonExistant']);
    }

    public function testGetUserSummaryNoGameHistory(): void
    {
        // user with no game history should have no points
        $this->user->RAPoints = 0;
        $this->user->RASoftcorePoints = 0;
        $this->user->save();

        $this->get($this->apiUrl('GetUserSummary', ['u' => $this->user->User]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $this->user->ID,
                'TotalPoints' => $this->user->RAPoints,
                'TotalSoftcorePoints' => $this->user->RASoftcorePoints,
                'TotalTruePoints' => $this->user->TrueRAPoints,
                'Permissions' => $this->user->Permissions,
                'MemberSince' => $this->user->Created->__toString(),
                'Untracked' => $this->user->Untracked,
                'UserPic' => '/UserPic/' . $this->user->User . '.png',
                'Motto' => $this->user->Motto,
                'UserWallActive' => $this->user->UserWallActive,
                'ContribCount' => $this->user->ContribCount,
                'ContribYield' => $this->user->ContribYield,
                'Rank' => null,
                'TotalRanked' => 0,
                'LastGameID' => null,
                'RichPresenceMsg' => null,
                'RecentlyPlayedCount' => 0,
                'RecentlyPlayed' => [],
            ]);
    }

    public function testGetUserSummary(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'Released' => 'Jan 2000',
            'ForumTopicID' => 222334,
            'Publisher' => 'WeSellGames',
            'Developer' => 'WeMakeGames',
            'Genre' => 'Simulation',
            'ImageIcon' => '/Images/001234.png',
            'ImageTitle' => '/Images/001235.png',
            'ImageIngame' => '/Images/001236.png',
            'ImageBoxArt' => '/Images/001237.png',
        ]);
        $publishedAchievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game->ID]);
        (new UpdateGameMetrics())->execute($game);

        /** @var User $user */
        $user = User::factory()->create([
            'TrueRAPoints' => random_int(10000, 20000),
            'Motto' => 'I play games.',
            'ContribCount' => random_int(10, 500),
            'ContribYield' => random_int(50, 1000),
            'Created' => Carbon::now()->subMonths(2),
            'LastLogin' => Carbon::now()->subDays(5),
        ]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'Released' => '07 Jun 1994',
            'ForumTopicID' => 23543,
            'Publisher' => 'WeAlsoSellGames',
            'Developer' => 'WeAlsoMakeGames',
            'Genre' => 'Platformer',
            'ImageIcon' => '/Images/002345.png',
            'ImageTitle' => '/Images/002346.png',
            'ImageIngame' => '/Images/002347.png',
            'ImageBoxArt' => '/Images/002348.png',
        ]);
        (new UpdateGameMetrics())->execute($game2);

        $earnedAchievement = $publishedAchievements->get(0);
        $unlockTime = Carbon::now()->subDays(5);
        $this->addHardcoreUnlock($user, $earnedAchievement, $unlockTime);

        $playerGame = $user->playerGame($game);

        // addHardcoreUnlock will create a player_game for game. need to manually create one for game2
        $playerGame2 = new PlayerGame([
            'user_id' => $user->ID,
            'game_id' => $game2->ID,
            'created_at' => Carbon::now()->subDays(1),
            'last_played_at' => Carbon::now()->subMinutes(5),
        ]);
        $playerGame2->save();

        // ensure $user has enough points to be ranked
        $user->refresh();
        $user['RAPoints'] = random_int(Rank::MIN_POINTS, 10000);
        $user->save();

        // make sure $this->user is ranked higher than $user
        $this->user->RAPoints = 1_234_567;
        $this->user->save();

        $this->get($this->apiUrl('GetUserSummary', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $user->ID,
                'TotalPoints' => $user->RAPoints,
                'TotalSoftcorePoints' => $user->RASoftcorePoints,
                'TotalTruePoints' => $user->TrueRAPoints,
                'Permissions' => $user->Permissions,
                'MemberSince' => $user->Created->__toString(),
                'Untracked' => $user->Untracked,
                'UserPic' => '/UserPic/' . $user->User . '.png',
                'Motto' => $user->Motto,
                'UserWallActive' => $user->UserWallActive,
                'ContribCount' => $user->ContribCount,
                'ContribYield' => $user->ContribYield,
                'Rank' => 2,
                'TotalRanked' => 2, // $this->user and $user
                'LastGameID' => $game->id,
                'LastGame' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'ForumTopicID' => $game->ForumTopicID,
                    'Flags' => 0,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageTitle' => $game->ImageTitle,
                    'ImageIngame' => $game->ImageIngame,
                    'ImageBoxArt' => $game->ImageBoxArt,
                    'Publisher' => $game->Publisher,
                    'Developer' => $game->Developer,
                    'Genre' => $game->Genre,
                    'Released' => $game->Released,
                    'IsFinal' => 0,
                ],
                'RichPresenceMsg' => 'Playing ' . $game->title,
                'RecentlyPlayedCount' => 2,
                'RecentlyPlayed' => [
                    [
                        'GameID' => $game2->ID,
                        'Title' => $game2->Title,
                        'ConsoleID' => $system->ID,
                        'ConsoleName' => $system->Name,
                        'ImageIcon' => $game2->ImageIcon,
                        'LastPlayed' => $playerGame2->last_played_at->__toString(),
                    ],
                    [
                        'GameID' => $game->ID,
                        'Title' => $game->Title,
                        'ConsoleID' => $system->ID,
                        'ConsoleName' => $system->Name,
                        'ImageIcon' => $game->ImageIcon,
                        'LastPlayed' => $playerGame->last_played_at->__toString(),
                    ],
                ],
                'LastActivity' => [
                    'ID' => 0,
                    'timestamp' => null,
                    'lastupdate' => null,
                    'activitytype' => null,
                    'User' => $user->User,
                    'data' => null,
                    'data2' => null,
                ],
                'Status' => 'Offline',
                'Awarded' => [
                    $game->ID => [
                        'NumPossibleAchievements' => 3,
                        'PossibleScore' => $publishedAchievements->get(0)->Points +
                                           $publishedAchievements->get(1)->Points +
                                           $publishedAchievements->get(2)->Points,
                        'NumAchievedHardcore' => 1,
                        'ScoreAchievedHardcore' => $earnedAchievement->Points,
                        'NumAchieved' => 1,
                        'ScoreAchieved' => $earnedAchievement->Points,
                    ],
                    $game2->ID => [
                        'NumPossibleAchievements' => 0,
                        'PossibleScore' => 0,
                        'NumAchievedHardcore' => 0,
                        'ScoreAchievedHardcore' => 0,
                        'NumAchieved' => 0,
                        'ScoreAchieved' => 0,
                    ],
                ],
                'RecentAchievements' => [
                    $game->ID => [
                        $earnedAchievement->ID => [
                            'ID' => $earnedAchievement->ID,
                            'Title' => $earnedAchievement->Title,
                            'Description' => $earnedAchievement->Description,
                            'Points' => $earnedAchievement->Points,
                            'BadgeName' => $earnedAchievement->BadgeName,
                            'GameID' => $game->ID,
                            'GameTitle' => $game->Title,
                            'IsAwarded' => '1',
                            'DateAwarded' => $unlockTime->__toString(),
                            'HardcoreAchieved' => 1,
                        ],
                    ],
                ],
            ]);

        // repeat the call, but only ask for one game
        $this->get($this->apiUrl('GetUserSummary', ['u' => $user->User, 'g' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $user->ID,
                'TotalPoints' => $user->RAPoints,
                'TotalSoftcorePoints' => $user->RASoftcorePoints,
                'TotalTruePoints' => $user->TrueRAPoints,
                'Permissions' => $user->Permissions,
                'MemberSince' => $user->Created->__toString(),
                'Untracked' => $user->Untracked,
                'UserPic' => '/UserPic/' . $user->User . '.png',
                'Motto' => $user->Motto,
                'UserWallActive' => $user->UserWallActive,
                'ContribCount' => $user->ContribCount,
                'ContribYield' => $user->ContribYield,
                'Rank' => 2,
                'TotalRanked' => 2, // $this->user and $user
                'LastGameID' => $game->id,
                'LastGame' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $system->ID,
                    'ConsoleName' => $system->Name,
                    'ForumTopicID' => $game->ForumTopicID,
                    'Flags' => 0,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageTitle' => $game->ImageTitle,
                    'ImageIngame' => $game->ImageIngame,
                    'ImageBoxArt' => $game->ImageBoxArt,
                    'Publisher' => $game->Publisher,
                    'Developer' => $game->Developer,
                    'Genre' => $game->Genre,
                    'Released' => $game->Released,
                    'IsFinal' => 0,
                ],
                'RichPresenceMsg' => 'Playing ' . $game->title,
                'RecentlyPlayedCount' => 1,
                'RecentlyPlayed' => [
                    [
                        'GameID' => $game2->ID,
                        'Title' => $game2->Title,
                        'ConsoleID' => $system->ID,
                        'ConsoleName' => $system->Name,
                        'ImageIcon' => $game2->ImageIcon,
                        'LastPlayed' => $playerGame2->last_played_at->__toString(),
                    ],
                ],
                'LastActivity' => [
                    'ID' => 0,
                    'timestamp' => null,
                    'lastupdate' => null,
                    'activitytype' => null,
                    'User' => $user->User,
                    'data' => null,
                    'data2' => null,
                ],
                'Status' => 'Offline',
                'Awarded' => [
                    $game->ID => [
                        'NumPossibleAchievements' => 3,
                        'PossibleScore' => $publishedAchievements->get(0)->Points +
                                           $publishedAchievements->get(1)->Points +
                                           $publishedAchievements->get(2)->Points,
                        'NumAchievedHardcore' => 1,
                        'ScoreAchievedHardcore' => $earnedAchievement->Points,
                        'NumAchieved' => 1,
                        'ScoreAchieved' => $earnedAchievement->Points,
                    ],
                ],
                'RecentAchievements' => [
                    $game->ID => [
                        $earnedAchievement->ID => [
                            'ID' => $earnedAchievement->ID,
                            'Title' => $earnedAchievement->Title,
                            'Description' => $earnedAchievement->Description,
                            'Points' => $earnedAchievement->Points,
                            'BadgeName' => $earnedAchievement->BadgeName,
                            'GameID' => $game->ID,
                            'GameTitle' => $game->Title,
                            'IsAwarded' => '1',
                            'DateAwarded' => $unlockTime->__toString(),
                            'HardcoreAchieved' => 1,
                        ],
                    ],
                ],
            ]);
    }

    public function testGetUserSummaryLimitRecentAchievements(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $publishedAchievements = Achievement::factory()->published()->count(7)->create(['GameID' => $game->ID]);

        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        $publishedAchievements2 = Achievement::factory()->published()->count(4)->create(['GameID' => $game2->ID]);

        $now = Carbon::now();

        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(1), $now->clone()->subMinutes(3));
        $this->addSoftcoreUnlock($this->user, $publishedAchievements->get(4), $now->clone()->subMinutes(6));
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(6), $now->clone()->subMinutes(10));
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(0), $now->clone()->subMinutes(20));
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(2), $now->clone()->subMinutes(30));

        $this->addHardcoreUnlock($this->user, $publishedAchievements2->get(2), $now->clone()->subMinutes(90));

        $this->get($this->apiUrl('GetUserSummary', ['u' => $this->user->User, 'a' => 2]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $this->user->ID,
                'Awarded' => [
                    $game->ID => [
                        'NumPossibleAchievements' => 7,
                        'NumAchieved' => 5,
                        'NumAchievedHardcore' => 4,
                    ],
                    $game2->ID => [
                        'NumPossibleAchievements' => 4,
                        'NumAchieved' => 1,
                        'NumAchievedHardcore' => 1,
                    ],
                ],
                'RecentAchievements' => [
                    $game->ID => [
                        $publishedAchievements->get(1)->ID => [
                            'HardcoreAchieved' => 1,
                        ],
                        $publishedAchievements->get(4)->ID => [
                            'HardcoreAchieved' => 0,
                        ],
                    ],
                ],
            ])
            // only two recent achievements were requested - both should be in game 1
            // and nothing should be returned for game 2
            ->assertJsonCount(1, "RecentAchievements")
            ->assertJsonCount(2, "RecentAchievements.{$game->ID}");

        // user only has 6 unlocks, so return all of them, and nothing more
        $this->get($this->apiUrl('GetUserSummary', ['u' => $this->user->User, 'a' => 7]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $this->user->ID,
                'Awarded' => [
                    $game->ID => [
                        'NumPossibleAchievements' => 7,
                        'NumAchieved' => 5,
                        'NumAchievedHardcore' => 4,
                    ],
                    $game2->ID => [
                        'NumPossibleAchievements' => 4,
                        'NumAchieved' => 1,
                        'NumAchievedHardcore' => 1,
                    ],
                ],
                'RecentAchievements' => [
                    $game->ID => [
                        $publishedAchievements->get(1)->ID => [
                            'HardcoreAchieved' => 1,
                        ],
                        $publishedAchievements->get(4)->ID => [
                            'HardcoreAchieved' => 0,
                        ],
                        $publishedAchievements->get(6)->ID => [
                            'HardcoreAchieved' => 1,
                        ],
                        $publishedAchievements->get(0)->ID => [
                            'HardcoreAchieved' => 1,
                        ],
                        $publishedAchievements->get(2)->ID => [
                            'HardcoreAchieved' => 1,
                        ],
                    ],
                    $game2->ID => [
                        $publishedAchievements2->get(2)->ID => [
                            'HardcoreAchieved' => 1,
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(2, "RecentAchievements")
            ->assertJsonCount(5, "RecentAchievements.{$game->ID}")
            ->assertJsonCount(1, "RecentAchievements.{$game2->ID}");
    }
}
