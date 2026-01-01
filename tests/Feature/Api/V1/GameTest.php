<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\GameRelease;
use App\Models\System;
use App\Platform\Enums\GameReleaseRegion;
use App\Platform\Enums\ReleasedAtGranularity;
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
            'system_id' => $system->id,
            'forum_topic_id' => 1234,
            'image_icon_asset_path' => '/Images/000011.png',
            'image_title_asset_path' => '/Images/000021.png',
            'image_ingame_asset_path' => '/Images/000031.png',
            'image_box_art_asset_path' => '/Images/000041.png',
            'publisher' => 'WePublishStuff',
            'developer' => 'WeDevelopStuff',
            'genre' => 'Action',
        ]);
        GameRelease::factory()->create([
            'game_id' => $gameOne->id,
            'title' => $gameOne->title,
            'released_at' => $releasedAt,
            'released_at_granularity' => ReleasedAtGranularity::Day,
            'region' => GameReleaseRegion::NorthAmerica,
            'is_canonical_game_title' => true,
        ]);

        // Ensure that null released_at values are properly handled.
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create([
            'system_id' => $system->id,
            'forum_topic_id' => 1234,
            'image_icon_asset_path' => '/Images/000011.png',
            'image_title_asset_path' => '/Images/000021.png',
            'image_ingame_asset_path' => '/Images/000031.png',
            'image_box_art_asset_path' => '/Images/000041.png',
            'publisher' => 'WePublishStuff',
            'developer' => 'WeDevelopStuff',
            'genre' => 'Action',
            'released_at' => null,
            'released_at_granularity' => null,
        ]);

        $this->get($this->apiUrl('GetGame', ['i' => $gameOne->id]))
            ->assertSuccessful()
            ->assertJson([
                'Title' => $gameOne->title,
                'GameTitle' => $gameOne->title,
                'ConsoleID' => $system->id,
                'ConsoleName' => $system->name,
                'Console' => $system->name,
                'ForumTopicID' => $gameOne->forum_topic_id,
                'Flags' => 0,
                'GameIcon' => $gameOne->image_icon_asset_path,
                'ImageIcon' => $gameOne->image_icon_asset_path,
                'ImageTitle' => $gameOne->image_title_asset_path,
                'ImageIngame' => $gameOne->image_ingame_asset_path,
                'ImageBoxArt' => $gameOne->image_box_art_asset_path,
                'Publisher' => $gameOne->publisher,
                'Developer' => $gameOne->developer,
                'Genre' => $gameOne->genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
            ]);

        $this->get($this->apiUrl('GetGame', ['i' => $gameTwo->id]))
            ->assertSuccessful()
            ->assertJson([
                'Title' => $gameTwo->title,
                'GameTitle' => $gameTwo->title,
                'ConsoleID' => $system->id,
                'ConsoleName' => $system->name,
                'Console' => $system->name,
                'ForumTopicID' => $gameTwo->forum_topic_id,
                'Flags' => 0,
                'GameIcon' => $gameTwo->image_icon_asset_path,
                'ImageIcon' => $gameTwo->image_icon_asset_path,
                'ImageTitle' => $gameTwo->image_title_asset_path,
                'ImageIngame' => $gameTwo->image_ingame_asset_path,
                'ImageBoxArt' => $gameTwo->image_box_art_asset_path,
                'Publisher' => $gameTwo->publisher,
                'Developer' => $gameTwo->developer,
                'Genre' => $gameTwo->genre,
                'Released' => null,
                'ReleasedAtGranularity' => null,
            ]);
    }
}
