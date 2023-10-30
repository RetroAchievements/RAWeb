<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Components;

use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class ActivePlayersTest extends TestCase
{
    use RefreshDatabase;

    public function testGivenNoPlayersItRendersAnEmptyState(): void
    {
        $view = $this->blade('<x-active-players />');

        $view->assertSee("empty-state-visible");
    }

    public function testGivenPlayersItRendersTheTable(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var User $user */
        $user = User::factory()->create([
            'RAPoints' => 10000,
            'RichPresenceMsg' => 'Having fun',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);

        // Act
        $view = $this->blade('<x-active-players />');

        // Assert
        $view->assertDontSee('empty-state-visible');
        $view->assertSeeText($user->RichPresenceMsg);
    }

    public function testGivenManyPlayersItOnlyRendersRecentActivePlayers(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var User $userOne */
        $userOne = User::factory()->create([
            'RAPoints' => 10000,
            'RichPresenceMsg' => 'UserOneRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);
        /** @var User $userTwo */
        $userTwo = User::factory()->create([
            'RAPoints' => 10000,
            'RichPresenceMsg' => 'UserTwoRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(45),
            'LastGameID' => $game->ID,
        ]);
        /** @var User $userThree */
        $userThree = User::factory()->create([
            'RAPoints' => 10000,
            'RichPresenceMsg' => 'UserThreeRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(55),
            'LastGameID' => $game->ID,
        ]);

        // Act
        $view = $this->blade('<x-active-players />');

        // Assert
        $view->assertDontSee('empty-state-visible');
        $view->assertSeeText($userOne->RichPresenceMsg);
        $view->assertDontSeeText($userTwo->RichPresenceMsg);
        $view->assertDontSeeText($userThree->RichPresenceMsg);
    }

    public function testItRanksPlayersByPoints(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var User $userOne */
        $userOne = User::factory()->create([
            'RAPoints' => 10000, // 2nd place
            'RichPresenceMsg' => 'UserOneRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);
        /** @var User $userTwo */
        $userTwo = User::factory()->create([
            'RAPoints' => 15000, // 1st place
            'RichPresenceMsg' => 'UserTwoRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);
        /** @var User $userThree */
        $userThree = User::factory()->create([
            'RAPoints' => 8000, // 3rd place
            'RichPresenceMsg' => 'UserThreeRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);

        // Act
        $view = $this->blade('<x-active-players />');

        // Assert
        $view->assertDontSee('empty-state-visible');
        $view->assertSeeTextInOrder([
            $userTwo->RichPresenceMsg,
            $userOne->RichPresenceMsg,
            $userThree->RichPresenceMsg,
        ]);
    }

    public function testItTreatsUntrackedUsersAsHavingZeroPoints(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var User $userOne */
        $userOne = User::factory()->create([
            'RAPoints' => 10000,
            'RichPresenceMsg' => 'UserOneRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);
        /** @var User $userTwo */
        $userTwo = User::factory()->create([
            'RAPoints' => 9999999,
            'RASoftcorePoints' => 999999,
            'Untracked' => 1,
            'RichPresenceMsg' => 'UserTwoRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);
        /** @var User $userThree */
        $userThree = User::factory()->create([
            'RAPoints' => 8000,
            'RichPresenceMsg' => 'UserThreeRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);

        // Act
        $view = $this->blade('<x-active-players />');

        // Assert
        $view->assertDontSee('empty-state-visible');
        $view->assertSeeTextInOrder([
            $userOne->RichPresenceMsg,
            $userThree->RichPresenceMsg,
            $userTwo->RichPresenceMsg,
        ]);
    }

    public function testItAddsGameTitlesToDevelopmentStatuses(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        $games = Game::factory()->count(4)->create(['ConsoleID' => $system->ID]);

        User::factory()->create([
            'RAPoints' => 10000,
            'RichPresenceMsg' => 'Developing Achievements',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $games->get(0)->ID,
        ]);
        User::factory()->create([
            'RAPoints' => 9999999,
            'RASoftcorePoints' => 999999,
            'RichPresenceMsg' => 'Fixing Achievements',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $games->get(1)->ID,
        ]);
        User::factory()->create([
            'RAPoints' => 8000,
            'RichPresenceMsg' => 'Inspecting Memory',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $games->get(2)->ID,
        ]);
        User::factory()->create([
            'RAPoints' => 8000,
            'RichPresenceMsg' => 'Having fun',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $games->get(3)->ID,
        ]);

        // Act
        $view = $this->blade('<x-active-players />');

        // Assert
        $view->assertDontSee('empty-state-visible');
        $view->assertSeeText("Developing Achievements for " . $games->get(0)->Title);
        $view->assertSeeText("Fixing Achievements for " . $games->get(1)->Title);
        $view->assertSeeText("Inspecting Memory for " . $games->get(2)->Title);
        $view->assertDontSeeText($games->get(3)->Title);
    }

    public function testItAllowsFilteringByRichPresenceMsg(): void
    {
        // Arrange
        $mockedRequest = Mockery::mock(Request::class);

        /** @var \Mockery\Expectation|\Mockery\MockInterface $mockExpectation */
        $mockExpectation = $mockedRequest->shouldReceive('cookie');
        $mockExpectation->with('active_players_search')
            ->andReturn('Having fun'); // The user's pre-set filter, stored in a cookie.

        $this->instance(Request::class, $mockedRequest);

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var User $userOne */
        $userOne = User::factory()->create([
            'RAPoints' => 10000,
            'RichPresenceMsg' => 'Having fun', // They should only initially see this user.
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);
        /** @var User $userTwo */
        $userTwo = User::factory()->create([
            'RAPoints' => 15000,
            'RichPresenceMsg' => 'UserTwoRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);
        /** @var User $userThree */
        $userThree = User::factory()->create([
            'RAPoints' => 8000,
            'RichPresenceMsg' => 'UserThreeRichPresenceMsg',
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);

        // Act
        $view = $this->blade('<x-active-players />');

        // Assert
        $view->assertSeeTextInOrder(["Viewing", "1", "of", "3", "players in-game"]);
        $view->assertSeeText($userOne->RichPresenceMsg);
        $view->assertDontSeeText($userTwo->RichPresenceMsg);
        $view->assertDontSeeText($userThree->RichPresenceMsg);
    }

    public function testItHidesBrokenRichPresenceMessages(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var User $user */
        $user = User::factory()->create([
            'RAPoints' => 10000,
            'RichPresenceMsg' => 'Having fun Unknown macro', // "Unknown macro" appears when using outdated emulators.
            'RichPresenceMsgDate' => Carbon::now()->subMinutes(5),
            'LastGameID' => $game->ID,
        ]);

        // Act
        $view = $this->blade('<x-active-players />');

        // Assert
        $view->assertDontSee('empty-state-visible');
        $view->assertDontSeeText($user->RichPresenceMsg);
        $view->assertSeeText("⚠️ Playing " . $game->Title);
    }
}
