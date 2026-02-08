<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AchievementCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexWorksForUnauthenticatedVisitors(): void
    {
        // Arrange
        $system = System::factory()->create(['id' => 1]);
        $game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['title' => 'Ancient Steps Retraced', 'game_id' => $game->id]);

        // Act
        $response = $this->get(route('achievement.comment.index', ['achievement' => $achievement]));

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
        $achievement = Achievement::factory()->create(['title' => 'Ancient Steps Retraced', 'game_id' => $game->id]);

        // Act
        $response = $this->get(route('achievement.comment.index', ['achievement' => $achievement]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('achievement.title', $achievement->title)
            ->where('achievement.badgeUnlockedUrl', $achievement->badgeUnlockedUrl)
            ->where('achievement.id', $achievement->id)

            ->where('achievement.game.id', $game->id)
            ->where('achievement.game.title', $game->title)

            ->where('achievement.game.system.name', $game->system->name)
            ->where('achievement.game.system.id', $game->system->id)

            ->has('paginatedComments.items', 0)
            ->where('isSubscribed', false)
            ->where('canComment', true)
        );
    }
}
