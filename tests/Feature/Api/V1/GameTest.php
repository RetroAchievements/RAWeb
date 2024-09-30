<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        $releasedAt = Carbon::parse('1992-05-16');

        /** @var System $system */
        $system = System::factory()->create();

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create([
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
            'released_at' => $releasedAt,
            'released_at_granularity' => 'day',
        ]);

        // Ensure that null released_at values are properly handled.
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create([
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
            'released_at' => null,
            'released_at_granularity' => null,
        ]);

        $this->get($this->apiUrl('GetGame', ['i' => $gameOne->id]))
            ->assertSuccessful()
            ->assertJson([
                'Title' => $gameOne->Title,
                'GameTitle' => $gameOne->Title,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
                'Console' => $system->Name,
                'ForumTopicID' => $gameOne->ForumTopicID,
                'Flags' => 0,
                'GameIcon' => $gameOne->ImageIcon,
                'ImageIcon' => $gameOne->ImageIcon,
                'ImageTitle' => $gameOne->ImageTitle,
                'ImageIngame' => $gameOne->ImageIngame,
                'ImageBoxArt' => $gameOne->ImageBoxArt,
                'Publisher' => $gameOne->Publisher,
                'Developer' => $gameOne->Developer,
                'Genre' => $gameOne->Genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
            ]);

        $this->get($this->apiUrl('GetGame', ['i' => $gameTwo->id]))
            ->assertSuccessful()
            ->assertJson([
                'Title' => $gameTwo->Title,
                'GameTitle' => $gameTwo->Title,
                'ConsoleID' => $system->ID,
                'ConsoleName' => $system->Name,
                'Console' => $system->Name,
                'ForumTopicID' => $gameTwo->ForumTopicID,
                'Flags' => 0,
                'GameIcon' => $gameTwo->ImageIcon,
                'ImageIcon' => $gameTwo->ImageIcon,
                'ImageTitle' => $gameTwo->ImageTitle,
                'ImageIngame' => $gameTwo->ImageIngame,
                'ImageBoxArt' => $gameTwo->ImageBoxArt,
                'Publisher' => $gameTwo->Publisher,
                'Developer' => $gameTwo->Developer,
                'Genre' => $gameTwo->Genre,
                'Released' => null,
                'ReleasedAtGranularity' => null,
            ]);
    }
}
