<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers;

use App\Models\GameSet;
use App\Models\System;
use App\Platform\Enums\GameSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HubControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testShowReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        GameSet::factory()->create([
            'id' => GameSet::CentralHubId,
            'title' => '[Central]',
            'type' => GameSetType::Hub,
        ]);

        $system = System::factory()->create([
            'ID' => 1,
            'name' => 'Nintendo Entertainment System',
            'name_short' => 'NES',
        ]);

        $hub = GameSet::factory()->create([
            'title' => '[Genre - RPG]',
            'type' => GameSetType::Hub,
        ]);

        $hub->games()->create([
            'Title' => 'Test Game',
            'ConsoleID' => $system->id,
        ]);

        // Act
        $response = $this->get(route('hub.show', ['gameSet' => $hub]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->component('hub/[gameSet]')
            ->where('hub', fn ($hubData) => $hubData['title'] === '[Genre - RPG]'
                && isset($hubData['badgeUrl'])
                && isset($hubData['updatedAt'])
            )
            ->has('breadcrumbs', 2) // [Central] and [Genre - RPG]
            ->has('relatedHubs')
            ->has('filterableSystemOptions', 1)
            ->where('can.develop', false)
            ->where('can.manageGameSets', false)
            ->has('paginatedGameListEntries.items', 1)
            ->where('paginatedGameListEntries.items.0.game.title', 'Test Game')
        );
    }
}
