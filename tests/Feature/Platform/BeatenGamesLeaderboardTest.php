<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BeatenGamesLeaderboardTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerBadges;

    public function testItRendersWithoutCrashing(): void
    {
        $this->get('/ranking/beaten-games')->assertStatus(200);
    }

    public function testItRendersEmptyState(): void
    {
        $this->get('/ranking/beaten-games')->assertSeeText("any rows matching your");
    }

    public function testItRendersRanks(): void
    {
        // Arrange
        $users = User::factory()->count(3)->create();
        $system = System::factory()->create();
        $games = Game::factory()->count(3)->create(['ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($users->get(0), $games->get(0));

        $this->addGameBeatenAward($users->get(1), $games->get(0));
        $this->addGameBeatenAward($users->get(1), $games->get(1));

        $this->addGameBeatenAward($users->get(2), $games->get(0));
        $this->addGameBeatenAward($users->get(2), $games->get(1));
        $this->addGameBeatenAward($users->get(2), $games->get(2));

        // Act
        $view = $this->get('/ranking/beaten-games');

        // Assert
        $view->assertSeeTextInOrder([
            '#1', $users->get(2)->User,
            '#2', $users->get(1)->User,
            '#3', $users->get(0)->User,
        ]);
    }

    public function testItRendersTies(): void
    {
        // Arrange
        $users = User::factory()->count(3)->create();
        $system = System::factory()->create();
        $games = Game::factory()->count(3)->create(['ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($users->get(0), $games->get(0));

        $this->addGameBeatenAward($users->get(1), $games->get(0), awardTime: Carbon::now()->subMinutes(30));
        $this->addGameBeatenAward($users->get(1), $games->get(1), awardTime: Carbon::now()->subMinutes(30));

        $this->addGameBeatenAward($users->get(2), $games->get(0));
        $this->addGameBeatenAward($users->get(2), $games->get(1));

        // Act
        $view = $this->get('/ranking/beaten-games');

        // Assert
        $view->assertSeeTextInOrder([
            '#1', $users->get(1)->User,
            '#1', $users->get(2)->User,
            '#3', $users->get(0)->User,
        ]);
    }
}
