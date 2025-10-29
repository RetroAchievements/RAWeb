<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortcodeApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testItValidatesRequiredFields(): void
    {
        // Arrange
        $this->withoutMiddleware();

        $payload = [
            'achievementIds' => [],
            'eventIds' => [],
            'gameIds' => [],
            'hubIds' => [],
            'setIds' => [],
            'ticketIds' => [],
            // !! missing required "usernames" array
        ];

        // Act
        $response = $this->postJson(route('api.shortcode-body.preview'), $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['usernames']);
    }

    public function testItReturnsEmptyCollectionsWhenNoIdsProvided(): void
    {
        // Arrange
        $this->withoutMiddleware();

        $payload = [
            'achievementIds' => [],
            'eventIds' => [],
            'gameIds' => [],
            'hubIds' => [],
            'setIds' => [],
            'ticketIds' => [],
            'usernames' => [],
        ];

        // Act
        $response = $this->postJson(route('api.shortcode-body.preview'), $payload);

        // Assert
        $response->assertOk();
        $response->assertExactJson([
            'users' => [],
            'tickets' => [],
            'achievements' => [],
            'games' => [],
            'hubs' => [],
            'events' => [],
        ]);
    }
}
