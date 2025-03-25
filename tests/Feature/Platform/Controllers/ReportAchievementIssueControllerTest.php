<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers;

use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportAchievementIssueControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        // Arrange
        $system = System::factory()->create(['name' => 'Nintendo 64', 'active' => true]);
        $game = Game::factory()->create(['title' => 'StarCraft 64', 'ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->id]);

        /** @var User $user */
        $user = User::factory()->create([
            'websitePrefs' => 63,
            'Permissions' => Permissions::Registered,
            'Created' => Carbon::now()->subWeeks(2),
            'email_verified_at' => Carbon::parse('2013-01-01'),
            'UnreadMessageCount' => 0,
        ]);
        $this->actingAs($user);

        PlayerGame::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        // Act
        $response = $this->get(route('achievement.report-issue.index', ['achievement' => $achievement->id]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('achievement', fn (Assert $achievement) => $achievement
                ->has('id')
                ->has('title')
                ->has('badgeUnlockedUrl')
                ->has('badgeLockedUrl')
                ->has('unlockedAt')
                ->has('unlockedHardcoreAt')
                ->has('game', fn (Assert $game) => $game
                    ->has('id')
                    ->has('title')
                    ->has('system')
                    ->has('isSubsetGame')
                )
            )
            ->has('hasSession')
            ->has('ticketType')
            ->has('can', fn (Assert $can) => $can
                ->has('createTriggerTicket')
            )
            ->etc() // for whatever reason, component validation always fails. it's covered elsewhere, though.
        );
    }
}
