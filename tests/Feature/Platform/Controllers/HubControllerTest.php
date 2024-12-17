<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers;

use App\Models\GameSet;
use App\Models\GameSetLink;
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
        $centralHub = GameSet::factory()->create([
            'id' => GameSet::CentralHubId,
            'title' => '[Central]',
            'type' => GameSetType::Hub,
        ]);

        $hub1 = GameSet::factory()->create([
            'title' => '[Central - Developer]',
            'type' => GameSetType::Hub,
        ]);

        $hub2 = GameSet::factory()->create([
            'title' => '[Developer - Access]',
            'type' => GameSetType::Hub,
        ]);

        GameSetLink::factory()->create([
            'parent_game_set_id' => $centralHub->id,
            'child_game_set_id' => $hub1->id,
        ]);
        GameSetLink::factory()->create([
            'parent_game_set_id' => $hub1->id,
            'child_game_set_id' => $hub2->id,
        ]);

        $system = System::factory()->create(['ID' => 1, 'name' => 'Nintendo Entertainment System', 'name_short' => 'NES']);
        $hub2->games()->create([
            'Title' => 'Test Game',
            'ConsoleID' => $system->id,
        ]);

        // Act
        $response = $this->get(route('hub.show', ['gameSet' => $hub2]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->component('hub/[gameSet]')
            ->where('hub', fn ($hubData) => $hubData['title'] === '[Developer - Access]'
                && isset($hubData['badgeUrl'])
                && isset($hubData['updatedAt'])
            )
            ->has('breadcrumbs', 3)
            ->has('relatedHubs')
            ->has('filterableSystemOptions', 1)
            ->where('can.develop', false)
            ->where('can.manageGameSets', false)
            ->has('paginatedGameListEntries.items', 1)
            ->where('paginatedGameListEntries.items.0.game.title', 'Test Game')
        );
    }
}
