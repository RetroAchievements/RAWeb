<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameListTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetGameListUnknownConsole(): void
    {
        $this->get($this->apiUrl('GetGameList', ['i' => 999999]))
            ->assertSuccessful()
            ->assertJsonCount(0)
            ->assertJson([]);
    }

    public function testGetGameList(): void
    {
        /** @var System $system1 */
        $system1 = System::factory()->create();
        /** @var System $system2 */
        $system2 = System::factory()->create();
        /** @var Game $game1 */
        $game1 = Game::factory()->create([
            'Title' => 'Alpha',
            'ConsoleID' => $system1->ID,
            'ImageIcon' => '/Images/123456.png',
            'ForumTopicID' => 123,
        ]);
        $game1Achievements = Achievement::factory()->published()->count(3)->create(['GameID' => $game1->ID]);
        $game1Points = $game1Achievements->get(0)->Points +
                       $game1Achievements->get(1)->Points +
                       $game1Achievements->get(2)->Points;
        /** @var Game $game2 */
        $game2 = Game::factory()->create([
            'Title' => 'Beta',
            'ConsoleID' => $system1->ID,
            'ImageIcon' => '/Images/213425.png',
        ]);
        /** @var Game $game3 */
        $game3 = Game::factory()->create([
            'Title' => 'Gamma',
            'ConsoleID' => $system2->ID,
            'ImageIcon' => '/Images/327584.png',
        ]);
        $game3Achievements = Achievement::factory()->published()->count(5)->create(['GameID' => $game3->ID]);
        Achievement::factory()->create(['GameID' => $game3->ID]);
        $game3Points = $game3Achievements->get(0)->Points +
                       $game3Achievements->get(1)->Points +
                       $game3Achievements->get(2)->Points +
                       $game3Achievements->get(3)->Points +
                       $game3Achievements->get(4)->Points;
        /** @var Game $game4 */
        $game4 = Game::factory()->create([
            'Title' => 'Delta',
            'ConsoleID' => $system2->ID,
            'ImageIcon' => '/Images/051283.png',
        ]);
        $game4Achievements = Achievement::factory()->published()->count(4)->create(['GameID' => $game4->ID]);
        $game4Points = $game4Achievements->get(0)->Points +
                       $game4Achievements->get(1)->Points +
                       $game4Achievements->get(2)->Points +
                       $game4Achievements->get(3)->Points;
        $hash1 = new GameHash(['GameID' => $game4->ID, 'MD5' => 'abcdef0123456789']);
        $game4->hashes()->save($hash1);
        $hash2 = new GameHash(['GameID' => $game4->ID, 'MD5' => 'deadbeefdeadbeef']);
        $game4->hashes()->save($hash2);

        // all games for system 1
        $this->get($this->apiUrl('GetGameList', ['i' => $system1->ID]))
            ->assertSuccessful()
            ->assertJsonCount(2)
            ->assertJson([
                [
                    'ID' => $game1->ID,
                    'Title' => $game1->Title,
                    'ConsoleID' => $system1->ID,
                    'ConsoleName' => $system1->Name,
                    'ImageIcon' => $game1->ImageIcon,
                    'NumAchievements' => 3,
                    'Points' => $game1Points,
                    'NumLeaderboards' => 0,
                    'ForumTopicID' => 123,
                ],
                [
                    'ID' => $game2->ID,
                    'Title' => $game2->Title,
                    'ConsoleID' => $system1->ID,
                    'ConsoleName' => $system1->Name,
                    'ImageIcon' => $game2->ImageIcon,
                    'NumAchievements' => 0,
                    'Points' => 0,
                    'NumLeaderboards' => 0,
                    'ForumTopicID' => null,
                ],
            ]);

        // games with achievements for system 1
        $this->get($this->apiUrl('GetGameList', ['i' => $system1->ID, 'f' => 1]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'ID' => $game1->ID,
                    'Title' => $game1->Title,
                    'ConsoleID' => $system1->ID,
                    'ConsoleName' => $system1->Name,
                    'ImageIcon' => $game1->ImageIcon,
                    'NumAchievements' => 3,
                    'Points' => $game1Points,
                    'NumLeaderboards' => 0,
                    'ForumTopicID' => 123,
                ],
            ]);

        // games with achievements for system 2 with hashes
        $this->get($this->apiUrl('GetGameList', ['i' => $system2->ID, 'f' => 1, 'h' => 1]))
            ->assertSuccessful()
            ->assertJsonCount(2)
            ->assertJson([
                [
                    'ID' => $game4->ID, /* Delta before Gamma */
                    'Title' => $game4->Title,
                    'ConsoleID' => $system2->ID,
                    'ConsoleName' => $system2->Name,
                    'ImageIcon' => $game4->ImageIcon,
                    'NumAchievements' => 4,
                    'Points' => $game4Points,
                    'NumLeaderboards' => 0,
                    'ForumTopicID' => null,
                    'Hashes' => [
                        $hash1->MD5,
                        $hash2->MD5,
                    ],
                ],
                [
                    'ID' => $game3->ID,
                    'Title' => $game3->Title,
                    'ConsoleID' => $system2->ID,
                    'ConsoleName' => $system2->Name,
                    'ImageIcon' => $game3->ImageIcon,
                    'NumAchievements' => 5, /* does not include unofficial */
                    'Points' => $game3Points,
                    'NumLeaderboards' => 0,
                    'ForumTopicID' => null,
                    'Hashes' => [],
                ],
            ]);

        // games with achievements for all systems

        /* disabled until denormalized data is available
           - causes "General error: 1114 The table '/tmp/#sql1d7b7f_1d21d4be_2' is full" error on server

        $this->get($this->apiUrl('GetGameList', ['i' => 0, 'f' => 1]))
            ->assertSuccessful()
            ->assertJsonCount(3)
            ->assertJson([
                [
                    'ID' => $game1->ID,
                    'Title' => $game1->Title,
                    'ConsoleID' => $system1->ID,
                    'ConsoleName' => $system1->Name,
                    'ImageIcon' => $game1->ImageIcon,
                    'NumAchievements' => 3,
                    'Points' => $game1Points,
                    'NumLeaderboards' => 0,
                    'ForumTopicID' => 123,
                ],
                [
                    'ID' => $game4->ID, /* Delta before Gamma * /
                    'Title' => $game4->Title,
                    'ConsoleID' => $system2->ID,
                    'ConsoleName' => $system2->Name,
                    'ImageIcon' => $game4->ImageIcon,
                    'NumAchievements' => 4,
                    'Points' => $game4Points,
                    'NumLeaderboards' => 0,
                    'ForumTopicID' => null,
                ],
                [
                    'ID' => $game3->ID,
                    'Title' => $game3->Title,
                    'ConsoleID' => $system2->ID,
                    'ConsoleName' => $system2->Name,
                    'ImageIcon' => $game3->ImageIcon,
                    'NumAchievements' => 5, /* does not include unofficial * /
                    'Points' => $game3Points,
                    'NumLeaderboards' => 0,
                    'ForumTopicID' => null,
                ],
            ]);
        */
    }
}
