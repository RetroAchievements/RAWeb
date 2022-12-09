<?php

declare(strict_types=1);

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Site\Models\StaticData;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersPageWithEmptyDatabase(): void
    {
        $this->get('/')->assertSuccessful();
    }

    public function testItRendersPageWithStaticData(): void
    {
        /** @var StaticData $staticData */
        $staticData = StaticData::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ID' => $staticData->LastCreatedGameID,
        ]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->create([
            'ID' => $staticData->LastCreatedAchievementID, 'GameID' => $staticData->LastCreatedGameID,
        ]);

        $this->get('/')->assertSuccessful()
            ->assertSee('Achievement of the Week')
            ->assertSee($game->Title)
            ->assertSee($achievement->Title);
    }
}
