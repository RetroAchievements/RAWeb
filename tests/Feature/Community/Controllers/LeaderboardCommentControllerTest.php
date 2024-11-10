<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeaderboardCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexWorksForUnauthenticatedVisitors(): void
    {
        // Arrange
        $system = System::factory()->create(['ID' => 1]);
        $game = Game::factory()->create(['Title' => 'Sonic the Hedgehog', 'ConsoleID' => $system->id]);
        $leaderboard = Leaderboard::factory()->create(['Title' => 'Any%', 'GameID' => $game->id]);

        // Act
        $response = $this->get(route('leaderboard.comment.index', ['leaderboard' => $leaderboard]));

        // Assert
        $response->assertOk();
    }

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1]);
        $game = Game::factory()->create(['Title' => 'Sonic the Hedgehog', 'ConsoleID' => $system->id]);
        $leaderboard = Leaderboard::factory()->create(['Title' => 'Any%', 'GameID' => $game->id]);

        // Act
        $response = $this->get(route('leaderboard.comment.index', ['leaderboard' => $leaderboard]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('leaderboard.title', $leaderboard->title)
            ->where('leaderboard.id', $leaderboard->id)

            ->where('leaderboard.game.id', $leaderboard->game->id)
            ->where('leaderboard.game.title', $leaderboard->game->title)

            ->where('leaderboard.game.system.name', $leaderboard->game->system->name)
            ->where('leaderboard.game.system.id', $leaderboard->game->system->id)

            ->has('paginatedComments.items', 0)
            ->where('isSubscribed', false)
            ->where('canComment', true)
        );
    }
}
