<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\Rank;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Enums\GameReleaseRegion;
use App\Platform\Enums\ReleasedAtGranularity;
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

    public function testGetUserSummaryNoGameHistoryByName(): void
    {
        // user with no game history should have no points
        $this->user->points_hardcore = 0;
        $this->user->points = 0;
        $this->user->save();

        $this->get($this->apiUrl('GetUserSummary', ['u' => $this->user->username])) // !!
            ->assertSuccessful()
            ->assertJson([
                'ID' => $this->user->id,
                'TotalPoints' => $this->user->points_hardcore,
                'TotalSoftcorePoints' => $this->user->points,
                'TotalTruePoints' => $this->user->points_weighted,
                'Permissions' => $this->user->Permissions,
                'MemberSince' => $this->user->created_at->__toString(),
                'Untracked' => $this->user->unranked_at !== null,
                'ULID' => $this->user->ulid,
                'UserPic' => '/UserPic/' . $this->user->username . '.png',
                'Motto' => $this->user->motto,
                'UserWallActive' => $this->user->is_user_wall_active,
                'ContribCount' => $this->user->yield_unlocks,
                'ContribYield' => $this->user->yield_points,
                'Rank' => null,
                'TotalRanked' => 0,
                'LastGameID' => null,
                'RichPresenceMsg' => null,
                'RichPresenceMsgDate' => null,
                'RecentlyPlayedCount' => 0,
                'RecentlyPlayed' => [],
            ]);
    }

    public function testGetUserSummaryNoGameHistoryByUlid(): void
    {
        // user with no game history should have no points
        $this->user->points_hardcore = 0;
        $this->user->points = 0;
        $this->user->save();

        $this->get($this->apiUrl('GetUserSummary', ['u' => $this->user->ulid])) // !!
            ->assertSuccessful()
            ->assertJson([
                'ID' => $this->user->id,
                'TotalPoints' => $this->user->points_hardcore,
                'TotalSoftcorePoints' => $this->user->points,
                'TotalTruePoints' => $this->user->points_weighted,
                'Permissions' => $this->user->Permissions,
                'MemberSince' => $this->user->created_at->__toString(),
                'Untracked' => $this->user->unranked_at !== null,
                'ULID' => $this->user->ulid,
                'UserPic' => '/UserPic/' . $this->user->username . '.png',
                'Motto' => $this->user->motto,
                'UserWallActive' => $this->user->is_user_wall_active,
                'ContribCount' => $this->user->yield_unlocks,
                'ContribYield' => $this->user->yield_points,
                'Rank' => null,
                'TotalRanked' => 0,
                'LastGameID' => null,
                'RichPresenceMsg' => null,
                'RichPresenceMsgDate' => null,
                'RecentlyPlayedCount' => 0,
                'RecentlyPlayed' => [],
            ]);
    }

    public function testGetUserSummary(): void
    {
        // Freeze time.
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $game = $this->seedGame(achievements: 3);
        $game->fill([
            'forum_topic_id' => 222334,
            'publisher' => 'WeSellGames',
            'developer' => 'WeMakeGames',
            'genre' => 'Simulation',
            'image_icon_asset_path' => '/Images/001234.png',
            'image_title_asset_path' => '/Images/001235.png',
            'image_ingame_asset_path' => '/Images/001236.png',
            'image_box_art_asset_path' => '/Images/001237.png',
        ]);
        $game->save();

        GameRelease::factory()->create([
            'game_id' => $game->id,
            'title' => $game->title,
            'released_at' => '1992-05-06',
            'released_at_granularity' => ReleasedAtGranularity::Day,
            'region' => GameReleaseRegion::NorthAmerica,
            'is_canonical_game_title' => true,
        ]);
        $game->refresh(); // pick up released_at sync'd from GameRelease

        $publishedAchievements = $game->achievements;
        (new UpdateGameMetricsAction())->execute($game);

        /** @var User $user */
        $user = User::factory()->create([
            'points_weighted' => random_int(10000, 20000),
            'motto' => 'I play games.',
            'yield_unlocks' => random_int(10, 500),
            'yield_points' => random_int(50, 1000),
            'created_at' => Carbon::now()->subMonths(2),
            'last_activity_at' => Carbon::now()->subDays(5),
        ]);
        $game2 = $this->seedGame();
        $game2->fill([
            'forum_topic_id' => 23543,
            'publisher' => 'WeAlsoSellGames',
            'developer' => 'WeAlsoMakeGames',
            'genre' => 'Platformer',
            'image_icon_asset_path' => '/Images/002345.png',
            'image_title_asset_path' => '/Images/002346.png',
            'image_ingame_asset_path' => '/Images/002347.png',
            'image_box_art_asset_path' => '/Images/002348.png',
        ]);
        $game2->save();

        GameRelease::factory()->create([
            'game_id' => $game2->id,
            'title' => $game2->title,
            'released_at' => '1994-05-07',
            'released_at_granularity' => ReleasedAtGranularity::Day,
            'region' => GameReleaseRegion::NorthAmerica,
            'is_canonical_game_title' => true,
        ]);

        (new UpdateGameMetricsAction())->execute($game2);

        $earnedAchievement = $publishedAchievements->get(0);
        $unlockTime = Carbon::now()->subDays(5);
        $this->addHardcoreUnlock($user, $earnedAchievement, $unlockTime);

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
            'created_at' => Carbon::now()->subDays(1),
            'last_played_at' => Carbon::now()->subMinutes(5),
        ]);
        $playerGame2->save();

        // ensure $user has enough points to be ranked
        $user->refresh();
        $user['points_hardcore'] = random_int(Rank::MIN_POINTS, 10000);
        $user->save();

        // make sure $this->user is ranked higher than $user
        $this->user->points_hardcore = 1_234_567;
        $this->user->save();

        // default parameters returns no games
        $this->get($this->apiUrl('GetUserSummary', ['u' => $user->username]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $user->id,
                'TotalPoints' => $user->points_hardcore,
                'TotalSoftcorePoints' => $user->points,
                'TotalTruePoints' => $user->points_weighted,
                'Permissions' => $user->Permissions,
                'MemberSince' => $user->created_at->__toString(),
                'Untracked' => $user->unranked_at !== null,
                'ULID' => $user->ulid,
                'UserPic' => '/UserPic/' . $user->username . '.png',
                'Motto' => $user->motto,
                'UserWallActive' => $user->is_user_wall_active,
                'ContribCount' => $user->yield_unlocks,
                'ContribYield' => $user->yield_points,
                'Rank' => 2,
                'TotalRanked' => 2, // $this->user and $user
                'LastGameID' => $game->id,
                'RichPresenceMsg' => 'Playing ' . $game->title,
                'RichPresenceMsgDate' => $unlockTime->__toString(),
                'RecentlyPlayedCount' => 0,
                'RecentlyPlayed' => [],
                'LastActivity' => [
                    'ID' => 0,
                    'timestamp' => null,
                    'lastupdate' => null,
                    'activitytype' => null,
                    'User' => $user->username,
                    'data' => null,
                    'data2' => null,
                ],
                'Status' => 'Offline',
            ])
            ->assertSee('"Awarded":{},', false)
            ->assertSee('"RecentAchievements":{},', false);

        // request more games than are available
        $this->get($this->apiUrl('GetUserSummary', ['u' => $user->username, 'g' => 5]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $user->id,
                'TotalPoints' => $user->points_hardcore,
                'TotalSoftcorePoints' => $user->points,
                'TotalTruePoints' => $user->points_weighted,
                'Permissions' => $user->Permissions,
                'MemberSince' => $user->created_at->__toString(),
                'Untracked' => $user->unranked_at !== null,
                'ULID' => $user->ulid,
                'UserPic' => '/UserPic/' . $user->username . '.png',
                'Motto' => $user->motto,
                'UserWallActive' => 1,
                'ContribCount' => $user->yield_unlocks,
                'ContribYield' => $user->yield_points,
                'Rank' => 2,
                'TotalRanked' => 2, // $this->user and $user
                'LastGameID' => $game->id,
                'LastGame' => [
                    'ID' => $game->id,
                    'Title' => $game->title,
                    'ConsoleID' => $game->system->id,
                    'ConsoleName' => $game->system->name,
                    'ForumTopicID' => $game->forum_topic_id,
                    'Flags' => 0,
                    'ImageIcon' => $game->image_icon_asset_path,
                    'ImageTitle' => $game->image_title_asset_path,
                    'ImageIngame' => $game->image_ingame_asset_path,
                    'ImageBoxArt' => $game->image_box_art_asset_path,
                    'Publisher' => $game->publisher,
                    'Developer' => $game->developer,
                    'Genre' => $game->genre,
                    'Released' => $game->released_at->format('Y-m-d'),
                    'ReleasedAtGranularity' => $game->released_at_granularity->value,
                ],
                'RichPresenceMsg' => 'Playing ' . $game->title,
                'RichPresenceMsgDate' => $unlockTime->__toString(),
                'RecentlyPlayedCount' => 2,
                'RecentlyPlayed' => [
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
                        'AchievementsTotal' => 0,
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
                        'AchievementsTotal' => 3,
                    ],
                ],
                'LastActivity' => [
                    'ID' => 0,
                    'timestamp' => null,
                    'lastupdate' => null,
                    'activitytype' => null,
                    'User' => $user->username,
                    'data' => null,
                    'data2' => null,
                ],
                'Status' => 'Offline',
                'Awarded' => [
                    $game->id => [
                        'NumPossibleAchievements' => 3,
                        'PossibleScore' => $publishedAchievements->get(0)->points +
                                           $publishedAchievements->get(1)->points +
                                           $publishedAchievements->get(2)->points,
                        'NumAchievedHardcore' => 1,
                        'ScoreAchievedHardcore' => $earnedAchievement->points,
                        'NumAchieved' => 1,
                        'ScoreAchieved' => $earnedAchievement->points,
                    ],
                    $game2->id => [
                        'NumPossibleAchievements' => 0,
                        'PossibleScore' => 0,
                        'NumAchievedHardcore' => 0,
                        'ScoreAchievedHardcore' => 0,
                        'NumAchieved' => 0,
                        'ScoreAchieved' => 0,
                    ],
                ],
                'RecentAchievements' => [
                    $game->id => [
                        $earnedAchievement->id => [
                            'ID' => $earnedAchievement->id,
                            'Title' => $earnedAchievement->title,
                            'Description' => $earnedAchievement->description,
                            'Points' => $earnedAchievement->points,
                            'BadgeName' => $earnedAchievement->image_name,
                            'GameID' => $game->id,
                            'GameTitle' => $game->title,
                            'IsAwarded' => '1',
                            'DateAwarded' => $unlockTime->__toString(),
                            'HardcoreAchieved' => 1,
                        ],
                    ],
                ],
            ]);

        // repeat the call, but only ask for one game
        $this->get($this->apiUrl('GetUserSummary', ['u' => $user->username, 'g' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $user->id,
                'TotalPoints' => $user->points_hardcore,
                'TotalSoftcorePoints' => $user->points,
                'TotalTruePoints' => $user->points_weighted,
                'Permissions' => $user->Permissions,
                'MemberSince' => $user->created_at->__toString(),
                'Untracked' => $user->unranked_at !== null,
                'ULID' => $user->ulid,
                'UserPic' => '/UserPic/' . $user->username . '.png',
                'Motto' => $user->motto,
                'UserWallActive' => $user->is_user_wall_active,
                'ContribCount' => $user->yield_unlocks,
                'ContribYield' => $user->yield_points,
                'Rank' => 2,
                'TotalRanked' => 2, // $this->user and $user
                'LastGameID' => $game->id,
                'LastGame' => [
                    'ID' => $game->id,
                    'Title' => $game->title,
                    'ConsoleID' => $game->system->id,
                    'ConsoleName' => $game->system->name,
                    'ForumTopicID' => $game->forum_topic_id,
                    'Flags' => 0,
                    'ImageIcon' => $game->image_icon_asset_path,
                    'ImageTitle' => $game->image_title_asset_path,
                    'ImageIngame' => $game->image_ingame_asset_path,
                    'ImageBoxArt' => $game->image_box_art_asset_path,
                    'Publisher' => $game->publisher,
                    'Developer' => $game->developer,
                    'Genre' => $game->genre,
                    'Released' => $game->released_at->format('Y-m-d'),
                    'ReleasedAtGranularity' => $game->released_at_granularity->value,
                ],
                'RichPresenceMsg' => 'Playing ' . $game->title,
                'RichPresenceMsgDate' => $unlockTime->__toString(),
                'RecentlyPlayedCount' => 1,
                'RecentlyPlayed' => [
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
                    ],
                ],
                'LastActivity' => [
                    'ID' => 0,
                    'timestamp' => null,
                    'lastupdate' => null,
                    'activitytype' => null,
                    'User' => $user->username,
                    'data' => null,
                    'data2' => null,
                ],
                'Status' => 'Offline',
                'Awarded' => [
                    $game->id => [
                        'NumPossibleAchievements' => 3,
                        'PossibleScore' => $publishedAchievements->get(0)->points +
                                           $publishedAchievements->get(1)->points +
                                           $publishedAchievements->get(2)->points,
                        'NumAchievedHardcore' => 1,
                        'ScoreAchievedHardcore' => $earnedAchievement->points,
                        'NumAchieved' => 1,
                        'ScoreAchieved' => $earnedAchievement->points,
                    ],
                ],
                'RecentAchievements' => [
                    $game->id => [
                        $earnedAchievement->id => [
                            'ID' => $earnedAchievement->id,
                            'Title' => $earnedAchievement->title,
                            'Description' => $earnedAchievement->description,
                            'Points' => $earnedAchievement->points,
                            'BadgeName' => $earnedAchievement->image_name,
                            'GameID' => $game->id,
                            'GameTitle' => $game->title,
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
        $game = Game::factory()->create(['system_id' => $system->id]);
        $publishedAchievements = Achievement::factory()->promoted()->count(7)->create(['game_id' => $game->id]);

        /** @var Game $game2 */
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        $publishedAchievements2 = Achievement::factory()->promoted()->count(4)->create(['game_id' => $game2->id]);

        $now = Carbon::now();

        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(1), $now->clone()->subMinutes(3));
        $this->addSoftcoreUnlock($this->user, $publishedAchievements->get(4), $now->clone()->subMinutes(6));
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(6), $now->clone()->subMinutes(10));
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(0), $now->clone()->subMinutes(20));
        $this->addHardcoreUnlock($this->user, $publishedAchievements->get(2), $now->clone()->subMinutes(30));

        $this->addHardcoreUnlock($this->user, $publishedAchievements2->get(2), $now->clone()->subMinutes(90));

        $this->get($this->apiUrl('GetUserSummary', ['u' => $this->user->username, 'g' => 5, 'a' => 2]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $this->user->id,
                'Awarded' => [
                    $game->id => [
                        'NumPossibleAchievements' => 7,
                        'NumAchieved' => 5,
                        'NumAchievedHardcore' => 4,
                    ],
                    $game2->id => [
                        'NumPossibleAchievements' => 4,
                        'NumAchieved' => 1,
                        'NumAchievedHardcore' => 1,
                    ],
                ],
                'RecentAchievements' => [
                    $game->id => [
                        $publishedAchievements->get(1)->id => [
                            'HardcoreAchieved' => 1,
                        ],
                        $publishedAchievements->get(4)->id => [
                            'HardcoreAchieved' => 0,
                        ],
                    ],
                ],
            ])
            // only two recent achievements were requested - both should be in game 1
            // and nothing should be returned for game 2
            ->assertJsonCount(1, "RecentAchievements")
            ->assertJsonCount(2, "RecentAchievements.{$game->id}");

        // user only has 6 unlocks, so return all of them, and nothing more
        $this->get($this->apiUrl('GetUserSummary', ['u' => $this->user->username, 'g' => 5, 'a' => 7]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $this->user->id,
                'Awarded' => [
                    $game->id => [
                        'NumPossibleAchievements' => 7,
                        'NumAchieved' => 5,
                        'NumAchievedHardcore' => 4,
                    ],
                    $game2->id => [
                        'NumPossibleAchievements' => 4,
                        'NumAchieved' => 1,
                        'NumAchievedHardcore' => 1,
                    ],
                ],
                'RecentAchievements' => [
                    $game->id => [
                        $publishedAchievements->get(1)->id => [
                            'HardcoreAchieved' => 1,
                        ],
                        $publishedAchievements->get(4)->id => [
                            'HardcoreAchieved' => 0,
                        ],
                        $publishedAchievements->get(6)->id => [
                            'HardcoreAchieved' => 1,
                        ],
                        $publishedAchievements->get(0)->id => [
                            'HardcoreAchieved' => 1,
                        ],
                        $publishedAchievements->get(2)->id => [
                            'HardcoreAchieved' => 1,
                        ],
                    ],
                    $game2->id => [
                        $publishedAchievements2->get(2)->id => [
                            'HardcoreAchieved' => 1,
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(2, "RecentAchievements")
            ->assertJsonCount(5, "RecentAchievements.{$game->id}")
            ->assertJsonCount(1, "RecentAchievements.{$game2->id}");
    }
}
