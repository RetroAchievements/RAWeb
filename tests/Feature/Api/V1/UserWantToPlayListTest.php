<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\UserGameListType;
use App\Community\Enums\UserRelationStatus;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserWantToPlayListTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = User::factory()->create(['username' => 'myUser']);
        $this->user = $user;
    }

    protected function apiUrl(string $method, array $params = []): string
    {
        $params = array_merge(['y' => $this->user->web_api_key], $params);

        return sprintf('API/API_%s.php?%s', $method, http_build_query($params));
    }

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetUserWantToPlayList'))
            ->assertJsonValidationErrors([
                'u',
            ]);
    }

    public function testGetUserWantToPlayListUnknownUser(): void
    {
        $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => 'nonExistant']))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetUserWantToPlayList(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** Set up a user with 5 games on Want to Play List: */

        /** @var User $followedUser */
        $followedUser = User::factory()->create(['username' => 'followedUser']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followedUser->id,
            'status' => UserRelationStatus::Following,
        ]);

        /** @var User $followingUser */
        $followingUser = User::factory()->create(['username' => 'followingUser']);
        UserRelation::create([
            'user_id' => $followingUser->id,
            'related_user_id' => $this->user->id,
            'status' => UserRelationStatus::Following,
        ]);

        /** @var User $friend */
        $friend = User::factory()->create(['username' => 'myFriend']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $friend->id,
            'status' => UserRelationStatus::Following,
        ]);
        UserRelation::create([
            'user_id' => $friend->id,
            'related_user_id' => $this->user->id,
            'status' => UserRelationStatus::Following,
        ]);
        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['system_id' => $system->id]);
        UserGameListEntry::create([
            'user_id' => $this->user->id,
            'game_id' => $gameOne->id,
            'type' => UserGameListType::Play,
        ]);
        UserGameListEntry::create([
            'user_id' => $followedUser->id,
            'game_id' => $gameOne->id,
            'type' => UserGameListType::Play,
        ]);
        UserGameListEntry::create([
            'user_id' => $followingUser->id,
            'game_id' => $gameOne->id,
            'type' => UserGameListType::Play,
        ]);
        UserGameListEntry::create([
            'user_id' => $friend->id,
            'game_id' => $gameOne->id,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['system_id' => $system->id]);
        UserGameListEntry::create([
            'user_id' => $this->user->id,
            'game_id' => $gameTwo->id,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['system_id' => $system->id]);
        UserGameListEntry::create([
            'user_id' => $this->user->id,
            'game_id' => $gameThree->id,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameFour */
        $gameFour = Game::factory()->create(['system_id' => $system->id]);
        UserGameListEntry::create([
            'user_id' => $this->user->id,
            'game_id' => $gameFour->id,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameFive */
        $gameFive = Game::factory()->create(['system_id' => $system->id]);
        UserGameListEntry::create([
            'user_id' => $this->user->id,
            'game_id' => $gameFive->id,
            'type' => UserGameListType::Play,
        ]);

        $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $this->user->username]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "ID" => $gameOne->id,
                        "Title" => $gameOne->title,
                        "ImageIcon" => $gameOne->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "PointsTotal" => $gameOne->points_total,
                        'AchievementsPublished' => $gameOne->achievements_published,
                    ],
                    [
                        "ID" => $gameTwo->id,
                        "Title" => $gameTwo->title,
                        "ImageIcon" => $gameTwo->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "PointsTotal" => $gameTwo->points_total,
                        'AchievementsPublished' => $gameTwo->achievements_published,
                    ],
                    [
                        "ID" => $gameThree->id,
                        "Title" => $gameThree->title,
                        "ImageIcon" => $gameThree->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "PointsTotal" => $gameThree->points_total,
                        'AchievementsPublished' => $gameThree->achievements_published,
                    ],
                    [
                        "ID" => $gameFour->id,
                        "Title" => $gameFour->title,
                        "ImageIcon" => $gameFour->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "PointsTotal" => $gameFour->points_total,
                        'AchievementsPublished' => $gameFour->achievements_published,
                    ],
                    [
                        "ID" => $gameFive->id,
                        "Title" => $gameFive->title,
                        "ImageIcon" => $gameFive->image_icon_asset_path,
                        "ConsoleID" => $system->id,
                        "PointsTotal" => $gameFive->points_total,
                        'AchievementsPublished' => $gameFive->achievements_published,
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $this->user->username, 'o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $gameFour->id,
                            "Title" => $gameFour->title,
                            "ImageIcon" => $gameFour->image_icon_asset_path,
                            "ConsoleID" => $system->id,
                            "PointsTotal" => $gameFour->points_total,
                            'AchievementsPublished' => $gameFour->achievements_published,
                        ],
                        [
                            "ID" => $gameFive->id,
                            "Title" => $gameFive->title,
                            "ImageIcon" => $gameFive->image_icon_asset_path,
                            "ConsoleID" => $system->id,
                            "PointsTotal" => $gameFive->points_total,
                            'AchievementsPublished' => $gameFive->achievements_published,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $this->user->username, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $gameOne->id,
                            "Title" => $gameOne->title,
                            "ImageIcon" => $gameOne->image_icon_asset_path,
                            "ConsoleID" => $system->id,
                            "PointsTotal" => $gameOne->points_total,
                            'AchievementsPublished' => $gameOne->achievements_published,
                        ],
                        [
                            "ID" => $gameTwo->id,
                            "Title" => $gameTwo->title,
                            "ImageIcon" => $gameTwo->image_icon_asset_path,
                            "ConsoleID" => $system->id,
                            "PointsTotal" => $gameTwo->points_total,
                            'AchievementsPublished' => $gameTwo->achievements_published,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $this->user->username, 'o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $gameTwo->id,
                            "Title" => $gameTwo->title,
                            "ImageIcon" => $gameTwo->image_icon_asset_path,
                            "ConsoleID" => $system->id,
                            "PointsTotal" => $gameTwo->points_total,
                            'AchievementsPublished' => $gameTwo->achievements_published,
                        ],
                        [
                            "ID" => $gameThree->id,
                            "Title" => $gameThree->title,
                            "ImageIcon" => $gameThree->image_icon_asset_path,
                            "ConsoleID" => $system->id,
                            "PointsTotal" => $gameThree->points_total,
                            'AchievementsPublished' => $gameThree->achievements_published,
                        ],
                    ],
                ]);

                // friendship tests
                $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $followedUser->username]))
                    ->assertUnauthorized()
                    ->assertJson([]);

                $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $followingUser->username]))
                    ->assertUnauthorized()
                    ->assertJson([]);

                $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $friend->username]))
                    ->assertSuccessful()
                    ->assertJson([
                        'Count' => 1,
                        'Total' => 1,
                        'Results' => [
                            [
                                "ID" => $gameOne->id,
                                "Title" => $gameOne->title,
                                "ImageIcon" => $gameOne->image_icon_asset_path,
                                "ConsoleID" => $system->id,
                                "PointsTotal" => $gameOne->points_total,
                                'AchievementsPublished' => $gameOne->achievements_published,
                            ],
                        ],
                    ]);
    }
}
