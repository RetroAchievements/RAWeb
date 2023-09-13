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

    public function testItAllowsFilteringBySystem(): void
    {
        /**
         * One game will go to System A. Two games will go to System B.
         */

        // Arrange
        $users = User::factory()->count(3)->create();
        $systems = System::factory()->count(2)->create();
        $gameOne = Game::factory()->create(['ConsoleID' => $systems->get(0)->ID]);
        $gameTwo = Game::factory()->create(['ConsoleID' => $systems->get(1)->ID]);
        $gameThree = Game::factory()->create(['ConsoleID' => $systems->get(1)->ID]);

        $this->addGameBeatenAward($users->get(0), $gameOne);

        $this->addGameBeatenAward($users->get(1), $gameOne);
        $this->addGameBeatenAward($users->get(1), $gameTwo);

        $this->addGameBeatenAward($users->get(2), $gameOne);
        $this->addGameBeatenAward($users->get(2), $gameTwo);
        $this->addGameBeatenAward($users->get(2), $gameThree);

        // Act
        $view = $this->get('/ranking/beaten-games?filter[system]=' . $systems->get(1)->ID);

        // Assert
        $view->assertSeeTextInOrder([
            '#1', $users->get(2)->User,
            '#2', $users->get(1)->User,
        ]);
    }

    public function testItExcludesAllSubsetsTestkitsAndMultiSets(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();

        $subset = Game::factory()->create(['Title' => '~Subset~ Beat Super Mario Bros in 42 seconds', 'ConsoleID' => $system->ID]);
        $testKit = Game::factory()->create(['Title' => '~Test Kit~ Make Sure Your N64 Turns On', 'ConsoleID' => $system->ID]);
        $multi = Game::factory()->create(['Title' => '~Multi~ Donkey Kong: Two Players But One Joystick', 'ConsoleID' => $system->ID]);
        $normalGame = Game::factory()->create(['ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($user, $subset);
        $this->addGameBeatenAward($user, $testKit);
        $this->addGameBeatenAward($user, $multi);
        $this->addGameBeatenAward($user, $normalGame);

        // Act
        $view = $this->get('/ranking/beaten-games');

        // Assert
        $view->assertSee($user->User . '-count-1');
    }

    public function testItExcludesUntrackedUsers(): void
    {
        // Arrange
        $trackedUser = User::factory()->create();
        $untrackedUser = User::factory()->create(['Untracked' => 1]);

        $system = System::factory()->create();
        $games = Game::factory()->count(3)->create(['ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($trackedUser, $games->get(0));

        $this->addGameBeatenAward($untrackedUser, $games->get(0));
        $this->addGameBeatenAward($untrackedUser, $games->get(1));
        $this->addGameBeatenAward($untrackedUser, $games->get(2));

        // Act
        $view = $this->get('/ranking/beaten-games');

        // Assert
        $view->assertSeeTextInOrder(['#1', $trackedUser->User]);
        $view->assertDontSeeText($untrackedUser->User);
        $view->assertDontSeeText("#2");
    }

    public function testItAllowsExcludingRetailGames(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();

        $hack = Game::factory()->create(['Title' => '~Hack~ Beat Super Mario Bros in 42 seconds', 'ConsoleID' => $system->ID]);
        $retail = Game::factory()->create(['Title' => 'Donkey Kong', 'ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($user, $hack);
        $this->addGameBeatenAward($user, $retail);

        // Act
        $view = $this->get('/ranking/beaten-games?filter[retail]=false');

        // Assert
        $view->assertSee($user->User . '-count-1');
    }
}
