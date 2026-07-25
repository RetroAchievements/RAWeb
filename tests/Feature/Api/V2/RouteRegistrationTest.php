<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteRegistrationTest extends TestCase
{
    public function testItRegistersNoRelationshipLinkageRoutes(): void
    {
        // Arrange
        $v2Routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'v2.'));

        // Act
        $offending = $v2Routes
            ->map(fn ($route) => $route->uri())
            ->filter(fn (string $uri) => in_array('relationships', explode('/', $uri), true))
            ->values();

        // Assert
        $this->assertNotEmpty($v2Routes);
        $this->assertEmpty($offending);
    }
}
