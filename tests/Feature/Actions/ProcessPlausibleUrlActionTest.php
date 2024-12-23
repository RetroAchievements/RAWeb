<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\ProcessPlausibleUrlAction;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\GameSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessPlausibleUrlActionTest extends TestCase
{
    use RefreshDatabase;

    private ProcessPlausibleUrlAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ProcessPlausibleUrlAction();
    }

    public function testItCorrectlyHandlesLegacyGameUrls(): void
    {
        // Arrange
        Game::factory()->create(['ID' => 1, 'Title' => 'Sonic the Hedgehog']);

        // Act
        $result = $this->action->execute('game/1');

        // Assert
        $this->assertEquals('/game/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'title' => 'Sonic the Hedgehog',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesSelfHealingGameUrls(): void
    {
        // Arrange
        Game::factory()->create(['ID' => 1, 'Title' => 'Sonic the Hedgehog 3']);

        // Act
        $result = $this->action->execute('game/sonic-the-hedgehog-3-1');

        // Assert
        $this->assertEquals('/game/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'title' => 'Sonic the Hedgehog 3',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesNestedGameUrls(): void
    {
        // Arrange
        Game::factory()->create(['ID' => 1, 'Title' => 'Sonic the Hedgehog']);

        // Act
        $result = $this->action->execute('game/1/foo');

        // Assert
        $this->assertEquals('/game/_PARAM_/foo', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'title' => 'Sonic the Hedgehog',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesLegacyAchievementUrls(): void
    {
        // Arrange
        Achievement::factory()->create(['ID' => 15, 'Title' => "Don't Get Lost"]);

        // Act
        $result = $this->action->execute('achievement/15');

        // Assert
        $this->assertEquals('/achievement/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 15,
            'title' => "Don't Get Lost",
        ], $result['props']);
    }

    public function testItCorrectlyHandlesSelfHealingAchievementUrls(): void
    {
        // Arrange
        Achievement::factory()->create(['ID' => 15, 'Title' => "Don't Get Lost"]);

        // Act
        $result = $this->action->execute('achievement/dont-get-lost-15');

        // Assert
        $this->assertEquals('/achievement/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 15,
            'title' => "Don't Get Lost",
        ], $result['props']);
    }

    public function testItCorrectlyHandlesUserUrls(): void
    {
        // Arrange
        User::factory()->create(['ID' => 1, 'User' => 'Scott']);

        // Act
        $result = $this->action->execute('user/Scott');

        // Assert
        $this->assertEquals('/user/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'username' => 'Scott',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesNestedUserUrls(): void
    {
        // Arrange
        User::factory()->create(['ID' => 1, 'User' => 'Scott']);

        // Act
        $result = $this->action->execute('user/Scott/progress');

        // Assert
        $this->assertEquals('/user/_PARAM_/progress', $result['redactedUrl']);
        $this->assertEquals([
            'username' => 'Scott',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesSystemUrls(): void
    {
        // Arrange
        System::factory()->create(['ID' => 1, 'Name' => 'Game Boy']);

        // Act
        $result = $this->action->execute('system/game-boy-1');

        // Assert
        $this->assertEquals('/system/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'name' => 'Game Boy',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesHubUrls(): void
    {
        // Arrange
        GameSet::factory()->create(['id' => 1, 'type' => GameSetType::Hub, 'title' => '[Series - Mega Man]']);

        // Act
        $result = $this->action->execute('hub/1');

        // Assert
        $this->assertEquals('/hub/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'title' => '[Series - Mega Man]',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesSelfHealingHubUrls(): void
    {
        // Arrange
        GameSet::factory()->create(['id' => 2, 'type' => GameSetType::Hub, 'title' => '[Central - Genre & Subgenre]']);

        // Act
        $result = $this->action->execute('hub/central-genre-subgenre-2');

        // Assert
        $this->assertEquals('/hub/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 2,
            'title' => '[Central - Genre & Subgenre]',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesTicketUrls(): void
    {
        // Arrange
        Ticket::factory()->create(['ID' => 1]);

        // Act
        $result = $this->action->execute('ticket/1');

        // Assert
        $this->assertEquals('/ticket/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
        ], $result['props']);
    }

    public function testItCorrectlyHandlesUnknownEntityUrls(): void
    {
        // Act
        $result = $this->action->execute('thing/1');

        // Assert
        $this->assertEquals('/thing/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
        ], $result['props']);
    }
}
