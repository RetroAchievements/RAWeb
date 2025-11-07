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
            // ... missing required "body" field ...
        ];

        // Act
        $response = $this->postJson(route('api.shortcode-body.preview'), $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['body']);
    }

    public function testItReturnsEmptyCollectionsWhenNoIdsProvided(): void
    {
        // Arrange
        $this->withoutMiddleware();

        $payload = [
            'body' => '', // !! empty
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
            'convertedBody' => '',
        ]);
    }
}
