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

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1]);
        $game = Game::factory()->create(['Title' => 'Sonic the Hedgehog', 'ConsoleID' => $system->id]);

        // Act
        $response = $this->get(route('game.comment.index', ['game' => $game]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('game.id', 1)
            ->where('game.title', $game->title)
            ->has('paginatedComments.items', 0)
            ->has('subscription', null)
        );
    }
}
