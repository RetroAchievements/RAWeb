<?php

declare(strict_types=1);

namespace Tests\Feature\Site;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\StaticData;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ID' => $staticData->LastCreatedGameID,
            'ConsoleID' => $system->ID,
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
