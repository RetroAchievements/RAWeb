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
    private array $defaultProps;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new ProcessPlausibleUrlAction();
        $this->defaultProps = [
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ];
    }

    public function testItCorrectlyHandlesLegacyGameUrls(): void
    {
        // Arrange
        Game::factory()->create(['ID' => 1, 'Title' => 'Sonic the Hedgehog']);

        // Act
        $result = $this->action->execute('game/1', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/game/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'title' => 'Sonic the Hedgehog',
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesSelfHealingGameUrls(): void
    {
        // Arrange
        Game::factory()->create(['ID' => 1, 'Title' => 'Sonic the Hedgehog 3']);

        // Act
        $result = $this->action->execute('game/sonic-the-hedgehog-3-1', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/game/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'title' => 'Sonic the Hedgehog 3',
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesNestedGameUrls(): void
    {
        // Arrange
        Game::factory()->create(['ID' => 1, 'Title' => 'Sonic the Hedgehog']);

        // Act
        $result = $this->action->execute('game/1/foo', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/game/_PARAM_/foo', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'title' => 'Sonic the Hedgehog',
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesLegacyAchievementUrls(): void
    {
        // Arrange
        Achievement::factory()->create(['ID' => 15, 'Title' => "Don't Get Lost"]);

        // Act
        $result = $this->action->execute('achievement/15', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/achievement/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 15,
            'title' => "Don't Get Lost",
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesSelfHealingAchievementUrls(): void
    {
        // Arrange
        Achievement::factory()->create(['ID' => 15, 'Title' => "Don't Get Lost"]);

        // Act
        $result = $this->action->execute('achievement/dont-get-lost-15', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/achievement/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 15,
            'title' => "Don't Get Lost",
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesUserUrls(): void
    {
        // Arrange
        User::factory()->create(['ID' => 1, 'User' => 'Scott']);

        // Act
        $result = $this->action->execute('user/Scott', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/user/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'username' => 'Scott',
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesNestedUserUrls(): void
    {
        // Arrange
        User::factory()->create(['ID' => 1, 'User' => 'Scott']);

        // Act
        $result = $this->action->execute('user/Scott/progress', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/user/_PARAM_/progress', $result['redactedUrl']);
        $this->assertEquals([
            'username' => 'Scott',
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesSystemUrls(): void
    {
        // Arrange
        System::factory()->create(['ID' => 1, 'Name' => 'Game Boy']);

        // Act
        $result = $this->action->execute('system/game-boy-1', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/system/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'name' => 'Game Boy',
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesHubUrls(): void
    {
        // Arrange
        GameSet::factory()->create(['id' => 1, 'type' => GameSetType::Hub, 'title' => '[Series - Mega Man]']);

        // Act
        $result = $this->action->execute('hub/1', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/hub/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'title' => '[Series - Mega Man]',
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesSelfHealingHubUrls(): void
    {
        // Arrange
        GameSet::factory()->create(['id' => 2, 'type' => GameSetType::Hub, 'title' => '[Central - Genre & Subgenre]']);

        // Act
        $result = $this->action->execute('hub/central-genre-subgenre-2', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/hub/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 2,
            'title' => '[Central - Genre & Subgenre]',
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesTicketUrls(): void
    {
        // Arrange
        Ticket::factory()->create(['ID' => 1]);

        // Act
        $result = $this->action->execute('ticket/1', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/ticket/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesLegacyViewtopicUrls(): void
    {
        // Act
        $result = $this->action->execute('viewtopic.php', ['t' => '123'], $this->defaultProps);

        // Assert
        $this->assertEquals('/viewtopic.php', $result['redactedUrl']);
        $this->assertEquals([
            'topicId' => 123,
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }

    public function testItCorrectlyHandlesUnknownEntityUrls(): void
    {
        // Act
        $result = $this->action->execute('thing/1', [], $this->defaultProps);

        // Assert
        $this->assertEquals('/thing/_PARAM_', $result['redactedUrl']);
        $this->assertEquals([
            'id' => 1,
            'isAuthenticated' => true,
            'scheme' => 'dark',
            'theme' => 'default',
        ], $result['props']);
    }
}
