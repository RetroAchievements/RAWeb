<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\UserGameListType;
use App\Community\Enums\UserRelationship;
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
        $user = User::factory()->create(['User' => 'myUser']);
        $this->user = $user;
    }

    protected function apiUrl(string $method, array $params = []): string
    {
        $params = array_merge(['y' => $this->user->APIKey], $params);

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
        $followedUser = User::factory()->create(['User' => 'followedUser']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $followedUser->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $followingUser */
        $followingUser = User::factory()->create(['User' => 'followingUser']);
        UserRelation::create([
            'user_id' => $followingUser->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);

        /** @var User $friend */
        $friend = User::factory()->create(['User' => 'myFriend']);
        UserRelation::create([
            'user_id' => $this->user->id,
            'related_user_id' => $friend->id,
            'Friendship' => UserRelationship::Following,
        ]);
        UserRelation::create([
            'user_id' => $friend->id,
            'related_user_id' => $this->user->id,
            'Friendship' => UserRelationship::Following,
        ]);
        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $this->user->id,
            'GameID' => $gameOne->ID,
            'type' => UserGameListType::Play,
        ]);
        UserGameListEntry::create([
            'user_id' => $followedUser->id,
            'GameID' => $gameOne->ID,
            'type' => UserGameListType::Play,
        ]);
        UserGameListEntry::create([
            'user_id' => $followingUser->id,
            'GameID' => $gameOne->ID,
            'type' => UserGameListType::Play,
        ]);
        UserGameListEntry::create([
            'user_id' => $friend->id,
            'GameID' => $gameOne->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $this->user->id,
            'GameID' => $gameTwo->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $this->user->id,
            'GameID' => $gameThree->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameFour */
        $gameFour = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $this->user->id,
            'GameID' => $gameFour->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameFive */
        $gameFive = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $this->user->id,
            'GameID' => $gameFive->ID,
            'type' => UserGameListType::Play,
        ]);

        $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $this->user->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 5,
                'Total' => 5,
                'Results' => [
                    [
                        "ID" => $gameOne->ID,
                        "Title" => $gameOne->Title,
                        "ImageIcon" => $gameOne->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "PointsTotal" => $gameOne->points_total,
                        'AchievementsPublished' => $gameOne->achievements_published,
                    ],
                    [
                        "ID" => $gameTwo->ID,
                        "Title" => $gameTwo->Title,
                        "ImageIcon" => $gameTwo->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "PointsTotal" => $gameTwo->points_total,
                        'AchievementsPublished' => $gameTwo->achievements_published,
                    ],
                    [
                        "ID" => $gameThree->ID,
                        "Title" => $gameThree->Title,
                        "ImageIcon" => $gameThree->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "PointsTotal" => $gameThree->points_total,
                        'AchievementsPublished' => $gameThree->achievements_published,
                    ],
                    [
                        "ID" => $gameFour->ID,
                        "Title" => $gameFour->Title,
                        "ImageIcon" => $gameFour->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "PointsTotal" => $gameFour->points_total,
                        'AchievementsPublished' => $gameFour->achievements_published,
                    ],
                    [
                        "ID" => $gameFive->ID,
                        "Title" => $gameFive->Title,
                        "ImageIcon" => $gameFive->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "PointsTotal" => $gameFive->points_total,
                        'AchievementsPublished' => $gameFive->achievements_published,
                    ],
                ],
            ]);

            $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $this->user->User, 'o' => 3]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $gameFour->ID,
                            "Title" => $gameFour->Title,
                            "ImageIcon" => $gameFour->ImageIcon,
                            "ConsoleID" => $system->ID,
                            "PointsTotal" => $gameFour->points_total,
                            'AchievementsPublished' => $gameFour->achievements_published,
                        ],
                        [
                            "ID" => $gameFive->ID,
                            "Title" => $gameFive->Title,
                            "ImageIcon" => $gameFive->ImageIcon,
                            "ConsoleID" => $system->ID,
                            "PointsTotal" => $gameFive->points_total,
                            'AchievementsPublished' => $gameFive->achievements_published,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $this->user->User, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $gameOne->ID,
                            "Title" => $gameOne->Title,
                            "ImageIcon" => $gameOne->ImageIcon,
                            "ConsoleID" => $system->ID,
                            "PointsTotal" => $gameOne->points_total,
                            'AchievementsPublished' => $gameOne->achievements_published,
                        ],
                        [
                            "ID" => $gameTwo->ID,
                            "Title" => $gameTwo->Title,
                            "ImageIcon" => $gameTwo->ImageIcon,
                            "ConsoleID" => $system->ID,
                            "PointsTotal" => $gameTwo->points_total,
                            'AchievementsPublished' => $gameTwo->achievements_published,
                        ],
                    ],
                ]);

            $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $this->user->User, 'o' => 1, 'c' => 2]))
                ->assertSuccessful()
                ->assertJson([
                    'Count' => 2,
                    'Total' => 5,
                    'Results' => [
                        [
                            "ID" => $gameTwo->ID,
                            "Title" => $gameTwo->Title,
                            "ImageIcon" => $gameTwo->ImageIcon,
                            "ConsoleID" => $system->ID,
                            "PointsTotal" => $gameTwo->points_total,
                            'AchievementsPublished' => $gameTwo->achievements_published,
                        ],
                        [
                            "ID" => $gameThree->ID,
                            "Title" => $gameThree->Title,
                            "ImageIcon" => $gameThree->ImageIcon,
                            "ConsoleID" => $system->ID,
                            "PointsTotal" => $gameThree->points_total,
                            'AchievementsPublished' => $gameThree->achievements_published,
                        ],
                    ],
                ]);

                // friendship tests
                $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $followedUser->User]))
                    ->assertUnauthorized()
                    ->assertJson([]);

                $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $followingUser->User]))
                    ->assertUnauthorized()
                    ->assertJson([]);

                $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $friend->User]))
                    ->assertSuccessful()
                    ->assertJson([
                        'Count' => 1,
                        'Total' => 1,
                        'Results' => [
                            [
                                "ID" => $gameOne->ID,
                                "Title" => $gameOne->Title,
                                "ImageIcon" => $gameOne->ImageIcon,
                                "ConsoleID" => $system->ID,
                                "PointsTotal" => $gameOne->points_total,
                                'AchievementsPublished' => $gameOne->achievements_published,
                            ],
                        ],
                    ]);
    }
}
