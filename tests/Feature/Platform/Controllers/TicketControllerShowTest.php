<?php

declare(strict_types=1);

use App\Community\Enums\TicketResolution;
use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\Role;
use App\Models\System;
use App\Models\Ticket;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Enums\TriggerableType;
use Carbon\Carbon;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

uses(LazilyRefreshDatabase::class);

function createTicketShowPageGame(): Game
{
    $system = System::factory()->create();

    return Game::factory()->create(['system_id' => $system->id]);
}

describe('Redirects and Errors', function () {
    it('given a guest, redirects to login', function () {
        // ARRANGE
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create();

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertRedirect(route('login'));
    });

    it('given a ticket whose ticketable is gone, returns a 404', function () {
        // ARRANGE
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create();
        $achievement->delete();

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertNotFound();
    });
});

describe('Achievement Ticket Props', function () {
    it('given an open achievement ticket, includes ticket, trigger versions, and related tickets in props', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $reporter = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
            'description' => 'Complete Level 3 without taking damage',
        ]);

        $reportedTrigger = Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'version' => 1,
        ]);
        $currentTrigger = Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'version' => 2,
            'parent_id' => $reportedTrigger->id,
        ]);
        $achievement->update(['trigger_id' => $currentTrigger->id]);

        $emulator = Emulator::factory()->create(['name' => 'RetroArch']);
        $gameHash = GameHash::factory()->create(['game_id' => $game->id, 'md5' => 'e7b1a2c3d4f560718293a4b5c6d7e8f9']);

        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $developer->id,
            'reporter_id' => $reporter->id,
            'trigger_id' => $reportedTrigger->id,
            'emulator_id' => $emulator->id,
            'emulator_version' => '1.20.0',
            'emulator_core' => 'snes9x',
            'game_hash_id' => $gameHash->id,
            'hardcore' => true,
            'created_at' => Carbon::parse('2024-03-10 12:00:00'),
        ]);

        $otherOpenTicket = Ticket::factory()->forAchievement($achievement)->create(['state' => TicketState::Request]);
        $otherClosedTicket = Ticket::factory()->forAchievement($achievement)->closed()->create([
            'resolution' => TicketResolution::MistakenReport,
        ]);

        PlayerAchievement::factory()->create([
            'user_id' => User::factory()->create()->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::parse('2024-03-15 08:30:00'),
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => User::factory()->create()->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::parse('2024-02-01 10:15:00'),
            'unlocked_hardcore_at' => null,
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('ticket/[ticket]')
            ->where('ticket.id', $ticket->id)
            ->where('ticket.ticketableType', 'achievement')
            ->where('ticket.state', 'open')
            ->where('ticket.type', 'did_not_trigger')
            ->where('ticket.hardcore', true)
            ->has('ticket.createdAt')
            ->missing('ticket.resolvedAt')
            ->where('ticket.reporter.displayName', $reporter->display_name)
            ->where('ticket.reporter.isGone', false)
            ->where('ticket.author.displayName', $developer->display_name)
            ->missing('ticket.resolver')
            ->where('ticket.emulator.name', 'RetroArch')
            ->where('ticket.emulatorVersion', '1.20.0')
            ->where('ticket.emulatorCore', 'snes9x')
            ->where('ticket.gameHash.md5', 'e7b1a2c3d4f560718293a4b5c6d7e8f9')
            ->where('ticket.ticketableId', $achievement->id)
            ->where('ticket.game.id', $game->id)
            ->has('ticket.game.badgeUrl')
            ->where('achievement.id', $achievement->id)
            ->where('achievement.points', $achievement->points)
            ->where('ticketableDescription', 'Complete Level 3 without taking damage')
            ->missing('leaderboard')
            ->where('ticket.ticketableBadgeUrl', $achievement->badge_url)
            ->has('relatedTickets', 2)
            ->has('relatedTickets.0.createdAt')
            ->where('relatedTickets', fn ($relatedTickets) => collect($relatedTickets)
                ->sortBy('id')
                ->map(fn (array $item) => [$item['id'], $item['state'], $item['resolution'] ?? null])
                ->values()
                ->all() === [
                    [$otherOpenTicket->id, 'request', null],
                    [$otherClosedTicket->id, 'closed', 'mistaken_report'],
                ]
            )
            ->missing('reporterUnlock')
            ->where('unlocksSinceReported', 1)
            ->where('reportedTriggerVersion', 1)
            ->where('currentTriggerVersion', 2)
            ->where('ticket.author.isGone', false)
            ->where('hasMaintainer', false)
            ->missing('reporterLeaderboardEntry')
            ->missing('leaderboardEntryCount')
        );
    });

    it('given an achievement ticket whose author is not the achievement developer, sets hasMaintainer to true', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $maintainer = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => $maintainer->id,
            'reporter_id' => User::factory()->create()->id,
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('ticket.author.displayName', $maintainer->display_name)
            ->where('achievement.developer.displayName', $developer->display_name)
            ->where('achievement.developer.isGone', false)
            ->where('hasMaintainer', true)
        );
    });

    it('given a quarantined achievement ticket, omits unlocksSinceReported', function () {
        // ARRANGE
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->quarantined()->create();

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('ticket.state', 'quarantined')
            ->missing('unlocksSinceReported')
        );
    });
});

describe('Leaderboard Ticket Props', function () {
    it('given a leaderboard ticket where the reporter has an entry, includes the leaderboard and the reporter entry in props', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $reporter = User::factory()->create();
        $game = createTicketShowPageGame();
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'author_id' => $developer->id,
            'format' => 'SCORE',
            'rank_asc' => false,
        ]);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => User::factory()->create()->id,
            'score' => 9500,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $reporter->id,
            'score' => 3850,
        ]);

        $ticket = Ticket::factory()->forLeaderboard($leaderboard)->resolved()->create([
            'ticketable_author_id' => null,
            'reporter_id' => $reporter->id,
            'resolver_id' => $developer->id,
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('ticket/[ticket]')
            ->where('ticket.ticketableType', 'leaderboard')
            ->where('ticket.state', 'resolved')
            ->has('ticket.resolvedAt')
            ->where('ticket.resolver.displayName', $developer->display_name)
            ->where('ticket.ticketableId', $leaderboard->id)
            ->missing('achievement')
            ->where('leaderboard.id', $leaderboard->id)
            ->where('leaderboard.format', 'SCORE')
            ->where('leaderboard.rankAsc', false)
            ->where('leaderboard.developer.displayName', $developer->display_name)
            ->where('leaderboard.developer.isGone', false)
            ->where('ticketableDescription', $leaderboard->description)
            ->where('ticket.game.badgeUrl', $game->badge_url)
            ->where('reporterLeaderboardEntry.formattedScore', '003850')
            ->where('reporterLeaderboardEntry.rank', 2)
            ->where('leaderboardEntryCount', 2)
            ->missing('unlocksSinceReported')
            ->missing('reportedTriggerVersion')
            ->missing('currentTriggerVersion')
            ->where('hasMaintainer', false)
        );
    });

    it('given a leaderboard ticket where the reporter has no entry, omits the reporter entry and entry count', function () {
        // ARRANGE
        $game = createTicketShowPageGame();
        $leaderboard = Leaderboard::factory()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forLeaderboard($leaderboard)->create([
            'reporter_id' => User::factory()->create()->id,
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('reporterLeaderboardEntry')
            ->missing('leaderboardEntryCount')
        );
    });
});

describe('Reporter Unlock Props', function () {
    it('given the reporter unlocked the achievement, only roles that can see player history receive the unlock', function (?string $role, bool $expected) {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $reporter = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'reporter_id' => $reporter->id,
            'created_at' => Carbon::parse('2024-04-01 00:00:00'),
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => $reporter->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::parse('2024-04-01 02:15:00'),
            'unlocked_hardcore_at' => null,
        ]);

        $viewer = User::factory()->create();
        if ($role) {
            $viewer->assignRole($role);
        }
        actingAs($viewer);

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $expected
            ? $page
                ->has('reporterUnlock.unlockedAt')
                ->where('reporterUnlock.isHardcore', false)
                ->missing('reporterUnlock.unlocker')
            : $page->missing('reporterUnlock')
        );
    })->with([
        'developer' => [Role::DEVELOPER, true],
        'cheat investigator' => [Role::CHEAT_INVESTIGATOR, true],
        'normal player' => [null, false],
    ]);

    it('given a manual unlock for the reporter, includes the awarding user in props', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $reporter = User::factory()->create();
        $awarder = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create(['reporter_id' => $reporter->id]);
        PlayerAchievement::factory()->create([
            'user_id' => $reporter->id,
            'achievement_id' => $achievement->id,
            'unlocker_id' => $awarder->id,
        ]);

        $viewer = User::factory()->create();
        $viewer->assignRole(Role::DEVELOPER);
        actingAs($viewer);

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('reporterUnlock.unlocker.displayName', $awarder->display_name)
        );
    });
});
