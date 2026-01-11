<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\Event;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexWorksForUnauthenticatedVisitors(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['title' => 'Achievement of the Week', 'system_id' => $system->id]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id]);

        // Act
        $response = $this->get(route('event.comment.index', ['event' => $event]));

        // Assert
        $response->assertOk();
    }

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['preferences_bitfield' => 63, 'unread_messages' => 0]);
        $this->actingAs($user);

        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['title' => 'Achievement of the Week', 'system_id' => $system->id]);
        $event = Event::factory()->create(['legacy_game_id' => $game->id]);

        // Act
        $response = $this->get(route('event.comment.index', ['event' => $event]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('event.id', $event->id)
            ->where('event.legacyGame.id', $game->id)
            ->where('event.legacyGame.title', $game->title)

            ->has('paginatedComments.items', 0)
            ->where('isSubscribed', false)
            ->where('canComment', true)
        );
    }
}
