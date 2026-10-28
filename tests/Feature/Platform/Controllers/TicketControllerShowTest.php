<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketResolution;
use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Emulator;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
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
            ->missing('ticket.reporter.id')
            ->where('isViewerReporter', false)
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

    it('given manual unlocks and a later logic change, counts only unlocks before the logic change', function () {
        // ARRANGE
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

        $originalTrigger = Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'version' => 1,
            'created_at' => Carbon::parse('2025-02-01 12:00:00'),
        ]);
        $reportedTrigger = Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'version' => 2,
            'parent_id' => $originalTrigger->id,
            'created_at' => Carbon::parse('2025-03-01 12:00:00'),
        ]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'created_at' => Carbon::parse('2025-04-01 12:00:00'),
        ]);
        Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'version' => 3,
            'parent_id' => $reportedTrigger->id,
            'created_at' => Carbon::parse('2025-04-20 12:00:00'),
        ]);

        PlayerAchievement::factory()->create([
            'user_id' => User::factory()->create()->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::parse('2025-04-05 12:00:00'), // !! before logic change
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => User::factory()->create()->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::parse('2025-04-06 12:00:00'),
            'unlocker_id' => User::factory()->create()->id, // !! manual unlock
        ]);
        PlayerAchievement::factory()->create([
            'user_id' => User::factory()->create()->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::parse('2025-04-25 12:00:00'), // !! after logic change
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('unlocksSinceReported', 1)
            ->where('logicChangedAfterReportAt', fn (string $changedAt) => Carbon::parse($changedAt)->equalTo('2025-04-20 12:00:00'))
        );
    });

    it('given a quarantined achievement ticket, omits unlocksSinceReported', function () {
        // ARRANGE
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->quarantined()->create([
            'ticketable_author_id' => User::factory()->create()->id,
        ]);

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
            'ticketable_author_id' => User::factory()->create()->id,
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
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => $reporter->id,
        ]);
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

describe('Reporter Activity Props', function () {
    it('given a viewer who may see player history and a reporter with no recorded activity, defers the activity with the ticket creation event', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $reporter = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'title' => 'Test Achievement']);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => $reporter->id,
            'created_at' => Carbon::parse('2024-04-01 00:00:00'),
        ]);

        $viewer = User::factory()->create();
        $viewer->assignRole(Role::DEVELOPER);
        actingAs($viewer);

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('hasReporterHistoryAccess', true)
            ->missing('reporterActivity')
            ->loadDeferredProps(fn (Assert $deferredPage) => $deferredPage
                ->has('reporterActivity.sessions', 1)
                ->where('reporterActivity.sessions.0.type', 'ticket-created')
                ->where('reporterActivity.sessions.0.events.0.type', 'ticket-created')
                ->where('reporterActivity.sessions.0.events.0.when', '2024-04-01T00:00:00+00:00')
            )
        );
    });

    it('given a viewer who may not see player history, sends no activity even though the reporter played the game', function () {
        // ARRANGE
        $reporter = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => $reporter->id,
        ]);
        PlayerGame::factory()->create(['user_id' => $reporter->id, 'game_id' => $game->id]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('hasReporterHistoryAccess', false)
            ->missing('reporterActivity')
            ->missing('deferredProps')
        );
    });
});

describe('Signal Props', function () {
    it('given a developer viewing an open ticket without recent unlocks, exposes signal data', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => createTicketShowPageGame()->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => User::factory()->create()->id,
            'created_at' => Carbon::parse('2026-06-01 12:00:00'),
        ]);
        PlayerSession::factory()->count(5)->create([
            'user_id' => fn () => User::factory()->create()->id,
            'game_id' => $achievement->game_id,
            'rich_presence_updated_at' => Carbon::parse('2026-05-25 12:00:00'),
        ]);

        $viewer = User::factory()->create();
        $viewer->assignRole(Role::DEVELOPER);
        actingAs($viewer);

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('ticket.signals.verdict', 'probably-defect')
            ->where('ticket.signals.kinds', ['no-recent-unlocks'])
            ->where('ticket.signals.unlockCount', 0)
        );
    });

    it('given a viewer without a developer role, does not expose signal data', function () {
        // ARRANGE
        $achievement = Achievement::factory()->promoted()->create(['game_id' => createTicketShowPageGame()->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => User::factory()->create()->id,
            'created_at' => Carbon::parse('2026-06-01 12:00:00'),
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->missing('ticket.signals'));
    });
});

describe('Comment Props', function () {
    it('given a ticket with comments, includes the data as props', function () {
        // ARRANGE
        $reporter = User::factory()->create();
        $commenter = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => $reporter->id,
            'body' => "It never unlocks.\nI tried twice.",
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => $commenter->id,
            'body' => 'Second comment.',
            'created_at' => Carbon::parse('2024-05-02 00:00:00'),
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => $commenter->id,
            'body' => 'First comment.',
            'created_at' => Carbon::parse('2024-05-01 00:00:00'),
        ]);

        actingAs($reporter);

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('reportBody', "It never unlocks.\nI tried twice.")
            ->has('comments', 2)
            ->where('comments.0.payload', 'First comment.')
            ->where('comments.1.payload', 'Second comment.')
            ->where('can.createTicketComments', true)
        );
    });

    it('given the user is muted, they cannot comment on the ticket', function () {
        // ARRANGE
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
        ]);

        actingAs(User::factory()->create(['muted_until' => now()->addWeek()]));

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('can.createTicketComments', false));
    });

    it('given a comment that was posted by a now-banned user, a regular viewer can still see it', function () {
        // ARRANGE
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => User::factory()->create(['banned_at' => now()])->id,
            'body' => 'The save state is attached.',
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('comments', 1)
            ->where('comments.0.payload', 'The save state is attached.')
        );
    });

    it('given a comment that was posted by a soft deleted user, does not crash', function () {
        // ARRANGE
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => User::factory()->create([
                'display_name' => 'BenGhazi',
                'deleted_at' => now(),
            ])->id,
            'body' => 'I can confirm this one.',
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('comments', 1)
            ->where('comments.0.user.displayName', 'BenGhazi')
            ->whereNot('comments.0.user.deletedAt', null)
        );
    });

    it('given a ticket whose state at some point was changed, includes the changelog entry and hides associated legacy Server comments', function () {
        // ARRANGE
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->create([
            'ticketable_author_id' => User::factory()->create()->id,
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'Which ROM did you use?',
        ]);

        actingAs(User::factory()->create(['display_name' => 'Scott']));
        $ticket->state = TicketState::Closed;
        $ticket->resolution = TicketResolution::WrongRom;
        $ticket->save();

        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => User::factory()->create(['id' => Comment::SYSTEM_USER_ID])->id,
            'body' => 'Ticket closed by Scott. Reason: "Wrong ROM".',
            'created_at' => now(),
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('comments', 1)
            ->where('comments.0.payload', 'Which ROM did you use?')
            ->has('changelog', 1)
            ->where('changelog.0.type', 'closed')
            ->where('changelog.0.resolution', 'wrong_rom')
            ->where('changelog.0.user.displayName', 'Scott')
        );
    });
});

describe('Ticket Action Props', function () {
    it('given a developer on an open ticket, allows resolving, demoting, and every close reason', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => User::factory()->create()->id,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);
        actingAs($developer);

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('allowedTicketActions', [
                'closed-mistaken',
                'resolved',
                'demoted',
                'not-enough-info',
                'wrong-rom',
                'network',
                'unable-to-reproduce',
                'unable-to-debug',
                'closed-other',
            ])
        );
    });

    it('given a developer on a quarantined ticket, allows only approving and the close reasons', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->quarantined()->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => User::factory()->create()->id,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);
        actingAs($developer);

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('allowedTicketActions', [
                'closed-mistaken',
                'not-enough-info',
                'wrong-rom',
                'network',
                'unable-to-reproduce',
                'unable-to-debug',
                'closed-other',
                'reopen',
            ])
        );
    });

    it('given a developer on a resolved ticket, allows only reopening', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->resolved()->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => User::factory()->create()->id,
            'resolution' => TicketResolution::Fixed,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);
        actingAs($developer);

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('allowedTicketActions', ['reopen'])
        );
    });

    it('given the reporter views their Request ticket, allows only closing as a mistaken report', function () {
        // ARRANGE
        $reporter = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->request()->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => $reporter->id,
        ]);

        actingAs($reporter);

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('allowedTicketActions', ['closed-mistaken'])
        );
    });

    it('given the reporter posted the newest non-automated comment, sets hasReporterRepliedLast to true', function () {
        // ARRANGE
        $reporter = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => $reporter->id,
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'Which level were you on?',
            'created_at' => Carbon::parse('2024-05-01 00:00:00'),
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => $reporter->id,
            'body' => 'Level 3.',
            'created_at' => Carbon::parse('2024-05-02 00:00:00'),
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => User::factory()->create(['id' => Comment::SYSTEM_USER_ID])->id,
            'body' => 'Ticket reopened by Scott.',
            'created_at' => Carbon::parse('2024-05-03 00:00:00'),
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('hasReporterRepliedLast', true));
    });

    it('given someone other than the reporter wrote the latest comment, sets hasReporterRepliedLast to false', function () {
        // ARRANGE
        $reporter = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => $reporter->id,
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => $reporter->id,
            'body' => 'It still does not unlock.',
            'created_at' => Carbon::parse('2024-05-01 00:00:00'),
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'Which level were you on?',
            'created_at' => Carbon::parse('2024-05-02 00:00:00'),
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('hasReporterRepliedLast', false));
    });

    it('given only the reporter has commented, sets hasReporterRepliedLast to false because they did not actually reply to anything', function () {
        // ARRANGE
        $reporter = User::factory()->create();
        $game = createTicketShowPageGame();
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        $ticket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => $reporter->id,
        ]);
        Comment::factory()->create([
            'commentable_type' => CommentableType::Ticket,
            'commentable_id' => $ticket->id,
            'user_id' => $reporter->id,
            'body' => 'It still does not unlock.',
        ]);

        actingAs(User::factory()->create());

        // ACT
        $response = get(route('ticket2.show', ['ticket' => $ticket]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->where('hasReporterRepliedLast', false));
    });
});
