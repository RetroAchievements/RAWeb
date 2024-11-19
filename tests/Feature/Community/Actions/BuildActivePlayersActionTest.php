<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\BuildActivePlayersAction;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildActivePlayersActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsEmptyDataWhenNoActivePlayers(): void
    {
        // Act
        $result = (new BuildActivePlayersAction())->execute();

        // Assert
        $this->assertEquals(0, $result->total);
        $this->assertEquals(1, $result->currentPage);
        $this->assertEquals(0, count($result->items));
    }

    public function testItReturnsPaginatedData(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        User::factory()->count(5)->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
        ]);

        // Act
        $result = (new BuildActivePlayersAction())->execute(perPage: 2, page: 1);

        // Assert
        $this->assertEquals(5, $result->total);
        $this->assertEquals(1, $result->currentPage);
        $this->assertEquals(3, $result->lastPage);
        $this->assertEquals(2, count($result->items));
    }

    public function testItSupportsFilteringPlayersByTheirLastGameId(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['ConsoleID' => $system->id]);
        $game2 = Game::factory()->create(['ConsoleID' => $system->id]);

        User::factory()->count(5)->create([
            'LastGameID' => $game1->id, // !!
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
        ]);
        User::factory()->count(3)->create([
            'LastGameID' => $game2->id, // !!
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
        ]);

        // Act
        $result = (new BuildActivePlayersAction())->execute(gameIds: [$game1->id]);

        // Assert
        $this->assertEquals(5, $result->total);
        $this->assertEquals(5, count($result->items));

        $this->assertEquals($game1->id, $result->items[0]->game->id);
    }

    public function testItSupportsSearchesByUsername(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'User' => 'testuser123', // !!
            'RichPresenceMsg' => 'Playing game',
        ]);
        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'User' => 'otheruser456', // !!
            'RichPresenceMsg' => 'Playing game',
        ]);

        // Act
        $result = (new BuildActivePlayersAction())->execute(search: 'testuser');

        // Assert
        $this->assertEquals(1, $result->total);
        $this->assertEquals('testuser123', $result->items[0]->user->displayName);
    }

    public function testItSupportsSearchesByRichPresenceMessage(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'User' => 'testuser123',
            'RichPresenceMsg' => 'Developing achievements',
        ]);
        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'User' => 'otheruser456',
            'RichPresenceMsg' => 'Playing game',
        ]);

        // Act
        $result = (new BuildActivePlayersAction())->execute(search: 'developing');

        // Assert
        $this->assertEquals(1, $result->total);
        $this->assertEquals('testuser123', $result->items[0]->user->displayName);
        $this->assertEquals('Developing achievements', $result->items[0]->user->richPresenceMsg->resolve());
    }

    public function testItSupportsSearchesByGameTitle(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['ConsoleID' => $system->id, 'Title' => 'Sonic the Hedgehog']);
        $game2 = Game::factory()->create(['ConsoleID' => $system->id, 'Title' => 'Legend of Zelda']);

        User::factory()->create([
            'LastGameID' => $game1->id, // !!
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'User' => 'Scott',
        ]);
        User::factory()->create([
            'LastGameID' => $game2->id, // !!
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'User' => 'Batman',
        ]);

        // Act
        $result = (new BuildActivePlayersAction())->execute(search: 'zelda');

        // Assert
        $this->assertEquals(1, $result->total);
        $this->assertEquals('Batman', $result->items[0]->user->displayName);
        $this->assertEquals($game2->id, $result->items[0]->game->id);
    }

    public function testItSupportsLogicalOrSearchOperations(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'RichPresenceMsg' => 'Playing game',
        ]);
        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'RichPresenceMsg' => 'Developing achievements',
        ]);
        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'RichPresenceMsg' => 'Fixing achievements',
        ]);

        // Act
        $result = (new BuildActivePlayersAction())->execute(search: 'developing|fixing');

        // Assert
        $this->assertEquals(2, $result->total);
    }

    public function testItExcludesInactivePlayers(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
        ]);
        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now()->subDays(2),
            'Permissions' => Permissions::Registered,
        ]);

        // Act
        $result = (new BuildActivePlayersAction())->execute();

        // Assert
        $this->assertEquals(1, $result->total);
    }

    public function testItPutsUntrackedPlayersAtBottomOfTheList(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'User' => 'tracked_high_points',
            'Untracked' => 0,
            'RAPoints' => 100000,
        ]);
        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'User' => 'untracked_high_points',
            'Untracked' => 1,
            'RAPoints' => 999999,
        ]);
        User::factory()->create([
            'LastGameID' => $game->id,
            'RichPresenceMsgDate' => now(),
            'Permissions' => Permissions::Registered,
            'User' => 'tracked_low_points',
            'Untracked' => 0,
            'RAPoints' => 50000,
        ]);

        // Act
        $result = (new BuildActivePlayersAction())->execute();

        // Assert
        $this->assertEquals(3, $result->total);

        $this->assertEquals('tracked_high_points', $result->items[0]->user->displayName);
        $this->assertEquals('tracked_low_points', $result->items[1]->user->displayName);
        $this->assertEquals('untracked_high_points', $result->items[2]->user->displayName);
    }
}
