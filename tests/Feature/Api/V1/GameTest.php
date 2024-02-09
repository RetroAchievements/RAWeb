<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetGameUnknownGame(): void
    {
        $this->get($this->apiUrl('GetGame', ['i' => 999999]))
            ->assertSuccessful()
            ->assertExactJson([]);
    }

    public function testGetGame(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ForumTopicID' => 1234,
            'ImageIcon' => '/Images/000011.png',
            'ImageTitle' => '/Images/000021.png',
            'ImageIngame' => '/Images/000031.png',
            'ImageBoxArt' => '/Images/000041.png',
            'Publisher' => 'WePublishStuff',
            'Developer' => 'WeDevelopStuff',
            'Genre' => 'Action',
            'Released' => 'Jan 1989',
        ]);

        $this->get($this->apiUrl('GetGame', ['i' => $game->ID]))
            ->assertSuccessful()
            ->assertJson([
                'Title' => $game->Title,
                'GameTitle' => $game->Title,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
                'Console' => $system->Name,
                'ForumTopicID' => $game->ForumTopicID,
                'Flags' => 0,
                'GameIcon' => $game->ImageIcon,
                'ImageIcon' => $game->ImageIcon,
                'ImageTitle' => $game->ImageTitle,
                'ImageIngame' => $game->ImageIngame,
                'ImageBoxArt' => $game->ImageBoxArt,
                'Publisher' => $game->Publisher,
                'Developer' => $game->Developer,
                'Genre' => $game->Genre,
                'Released' => $game->Released,
            ]);
    }
}
