<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexWorksForUnauthenticatedVisitors(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);

        // Act
        $response = $this->get(route('game.comment.index', ['game' => $game]));

        // Assert
        $response->assertOk();
    }

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['preferences_bitfield' => 63, 'unread_messages' => 0, 'created_at' => now()->subWeeks(3)]);
        $this->actingAs($user);

        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);

        // Act
        $response = $this->get(route('game.comment.index', ['game' => $game]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('game.id', 1)
            ->where('game.title', $game->title)

            ->has('paginatedComments.items', 0)

            ->where('isSubscribed', false)
            ->where('canComment', true)
        );
    }
}
