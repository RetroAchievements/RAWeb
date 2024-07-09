<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Community\Enums\UserGameListType;
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
                'u'
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

        /** @var User $me */
        $me = User::factory()->create(['User' => 'myUser']);

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameOne->ID,
            'type' => UserGameListType::Play
        ]);

        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameTwo->ID,
            'type' => UserGameListType::Play
        ]);
        
        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameThree->ID,
            'type' => UserGameListType::Play
        ]);

        /** @var Game $gameFour */
        $gameFour = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameFour->ID,
            'type' => UserGameListType::Play
        ]);

        /** @var Game $gameFive */
        $gameFive = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameFive->ID,
            'type' => UserGameListType::Play
        ]);

        /** @var Game $gameSix */
        $gameSix = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameSix->ID,
            'type' => UserGameListType::Play
        ]);

        /** @var Game $gameSeven */
        $gameSeven = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameSeven->ID,
            'type' => UserGameListType::Play
        ]);

        /** @var Game $gameEight */
        $gameEight = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameEight->ID,
            'type' => UserGameListType::Play
        ]);

        /** @var Game $gameNine */
        $gameNine = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameNine->ID,
            'type' => UserGameListType::Play
        ]);

        /** @var Game $gameTen */
        $gameTen = Game::factory()->create(['ConsoleID' => $system->ID]);
        UserGameListEntry::create([
            'user_id' => $user->id,
            'GameID' => $gameTen->ID,
            'type' => UserGameListType::Play
        ]);


        $this->get($this->apiUrl('GetUserWantToPlayList', ['u' => $me->User]))
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
                        "TotalPoints" => $gameOne->points_total,
                        'NumPossibleAchievements' => $gameOne->achievements_published
                    ],
                    [
                        "ID" => $gameTwo->ID,
                        "Title" => $gameTwo->Title,
                        "ImageIcon" => $gameTwo->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "TotalPoints" => $gameTwo->points_total,
                        'NumPossibleAchievements' => $gameTwo->achievements_published
                    ],
                    [
                        "ID" => $gameThree->ID,
                        "Title" => $gameThree->Title,
                        "ImageIcon" => $gameThree->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "TotalPoints" => $gameThree->points_total,
                        'NumPossibleAchievements' => $gameThree->achievements_published
                    ],
                    [
                        "ID" => $gameFour->ID,
                        "Title" => $gameFour->Title,
                        "ImageIcon" => $gameFour->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "TotalPoints" => $gameFour->points_total,
                        'NumPossibleAchievements' => $gameFour->achievements_published
                    ],
                    [
                        "ID" => $gameFive->ID,
                        "Title" => $gameFive->Title,
                        "ImageIcon" => $gameFive->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "TotalPoints" => $gameFive->points_total,
                        'NumPossibleAchievements' => $gameFive->achievements_published
                    ],
                    [
                        "GameID" => $gameSix->ID,
                        "Title" => $gameSix->Title,
                        "ImageIcon" => $gameSix->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "TotalPoints" => $gameSix->points_total,
                        'NumPossibleAchievements' => $gameSix->achievements_published
                    ],
                    [
                        "GameID" => $gameSeven->ID,
                        "Title" => $gameSeven->Title,
                        "ImageIcon" => $gameSeven->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "TotalPoints" => $gameSeven->points_total,
                        'NumPossibleAchievements' => $gameSeven->achievements_published
                    ],
                    [
                        "GameID" => $gameEight->ID,
                        "Title" => $gameEight->Title,
                        "ImageIcon" => $gameEight->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "TotalPoints" => $gameEight->points_total,
                        'NumPossibleAchievements' => $gameEight->achievements_published
                    ],
                    [
                        "GameID" => $gameNine->ID,
                        "Title" => $gameNine->Title,
                        "ImageIcon" => $gameNine->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "TotalPoints" => $gameNine->points_total,
                        'NumPossibleAchievements' => $gameNine->achievements_published
                    ],
                    [
                        "GameID" => $gameTen->ID,
                        "Title" => $gameTen->Title,
                        "ImageIcon" => $gameTen->ImageIcon,
                        "ConsoleID" => $system->ID,
                        "TotalPoints" => $gameTen->points_total,
                        'NumPossibleAchievements' => $gameTen->achievements_published
                    ],
                ],
            ]);
    }
}
