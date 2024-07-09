<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserWantToPlayListTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

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
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetUserWantToPlayList(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** Set up a user with 10 games on Want to Play List: */

        /** @var User $user */
        $user = User::factory()->create(['User' => 'myUser']);

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameOne->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameTwo->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameThree->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameFour */
        $gameFour = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameFour->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameFive */
        $gameFive = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameFive->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameSix */
        $gameSix = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameSix->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameSeven */
        $gameSeven = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameSeven->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameEight */
        $gameEight = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameEight->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameNine */
        $gameNine = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameNine->ID,
            'type' => UserGameListType::Play,
        ]);

        /** @var Game $gameTen */
        $gameTen = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameTen->ID,
            'type' => UserGameListType::Play,
        ]);

        $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 10,
                'Total' => 10,
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
                    [
                        "ID" => $gameSix->ID,
                        "Title" => $gameSix->Title,
                        "ImageIcon" => $gameSix->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "PointsTotal" => $gameSix->points_total,
                        'AchievementsPublished' => $gameSix->achievements_published,
                    ],
                    [
                        "ID" => $gameSeven->ID,
                        "Title" => $gameSeven->Title,
                        "ImageIcon" => $gameSeven->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "PointsTotal" => $gameSeven->points_total,
                        'AchievementsPublished' => $gameSeven->achievements_published,
                    ],
                    [
                        "ID" => $gameEight->ID,
                        "Title" => $gameEight->Title,
                        "ImageIcon" => $gameEight->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "PointsTotal" => $gameEight->points_total,
                        'AchievementsPublished' => $gameEight->achievements_published,
                    ],
                    [
                        "ID" => $gameNine->ID,
                        "Title" => $gameNine->Title,
                        "ImageIcon" => $gameNine->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "PointsTotal" => $gameNine->points_total,
                        'AchievementsPublished' => $gameNine->achievements_published,
                    ],
                    [
                        "ID" => $gameTen->ID,
                        "Title" => $gameTen->Title,
                        "ImageIcon" => $gameTen->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "PointsTotal" => $gameTen->points_total,
                        'AchievementsPublished' => $gameTen->achievements_published,
                    ],
                ],
            ]);
    }
}
