<?php

declare(strict_types=1);

use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Game;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\BuildTicketListAction;
use App\Platform\Data\TicketListEntryData;
use App\Platform\Enums\TicketListFilterKind;
use App\Platform\Enums\TicketListScope;
use App\Platform\Requests\TicketListRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{developer: User, reporter: User, resolver: User, game: Game, achievement: Achievement, otherAchievement: Achievement, tickets: Ticket[]}
 */
function createTicketListFixture(int $ticketCount = 12): array
{
    $developer = User::factory()->create();
    $reporter = User::factory()->create();
    $resolver = User::factory()->create();

    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);
    $otherAchievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);

    $states = [
        TicketState::Open,
        TicketState::Request,
        TicketState::Quarantined,
        TicketState::Resolved,
        TicketState::Closed,
    ];

    $tickets = [];
    for ($i = 0; $i < $ticketCount; $i++) {
        $state = $states[$i % count($states)];
        $isTerminal = in_array($state, [TicketState::Resolved, TicketState::Closed], true);

        $tickets[] = Ticket::factory()
            ->forAchievement($i % 2 === 0 ? $achievement : $otherAchievement)
            ->create([
                'ticketable_author_id' => $developer->id,
                'reporter_id' => $reporter->id,
                'resolver_id' => $isTerminal ? $resolver->id : null,
                'state' => $state,
                'created_at' => Carbon::parse('2024-01-01 00:00:00')->addHours($i),
                'resolved_at' => $isTerminal ? Carbon::parse('2024-06-01 00:00:00')->addHours($i) : null,
            ]);
    }

    return [
        'developer' => $developer,
        'reporter' => $reporter,
        'resolver' => $resolver,
        'game' => $game,
        'achievement' => $achievement,
        'otherAchievement' => $otherAchievement,
        'tickets' => $tickets,
    ];
}

/**
 * @return int[]
 */
function entryIds(array $result): array
{
    return array_map(fn (TicketListEntryData $entry) => $entry->id, $result['paginatedTickets']->items);
}

describe('scope default values', function () {
    it('given scope is set to game and there is no query string, only the open and request tickets are returned for the game, sorted by newest tickets first', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $reporter = User::factory()->create();
        $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
        $otherGame = Game::factory()->create(['system_id' => System::factory()->create()->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);
        $otherAchievement = Achievement::factory()->promoted()->create(['game_id' => $otherGame->id, 'user_id' => $developer->id]);
        $attributes = ['ticketable_author_id' => $developer->id, 'reporter_id' => $reporter->id];

        $oldest = Ticket::factory()->forAchievement($achievement)->open()->create([
            ...$attributes,
            'created_at' => Carbon::parse('2024-01-01 00:00:00'),
        ]);
        $middle = Ticket::factory()->forAchievement($achievement)->request()->create([
            ...$attributes,
            'created_at' => Carbon::parse('2024-01-02 00:00:00'),
        ]);
        $newest = Ticket::factory()->forAchievement($achievement)->open()->create([
            ...$attributes,
            'created_at' => Carbon::parse('2024-01-03 00:00:00'),
        ]);

        Ticket::factory()->forAchievement($achievement)->resolved()->create($attributes);
        Ticket::factory()->forAchievement($otherAchievement)->open()->create($attributes);

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::Game,
            $game,
            TicketListRequest::create('/internal-api/tickets', 'GET'),
        );

        // ASSERT
        expect(entryIds($result))->toEqual([$newest->id, $middle->id, $oldest->id]);
        expect($result['paginatedTickets']->total)->toEqual(3);
        expect($result['paginatedTickets']->unfilteredTotal)->toEqual(4);
    });

    it('given scope is set to assignedTo and there is no query string, only the open tickets assigned to that specific developer are returned', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $otherDeveloper = User::factory()->create();
        $reporter = User::factory()->create();
        $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
        $ownAchievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);
        $otherAchievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $otherDeveloper->id]);

        $assigned = Ticket::factory()->forAchievement($ownAchievement)->open()->create([
            'ticketable_author_id' => $developer->id,
            'reporter_id' => $reporter->id,
        ]);

        Ticket::factory()->forAchievement($ownAchievement)->closed()->create([
            'ticketable_author_id' => $developer->id,
            'reporter_id' => $reporter->id,
        ]);

        Ticket::factory()->forAchievement($otherAchievement)->open()->create([
            'ticketable_author_id' => $otherDeveloper->id,
            'reporter_id' => $developer->id,
        ]);

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::AssignedTo,
            $developer,
            TicketListRequest::create('/internal-api/tickets', 'GET'),
        );

        // ASSERT
        expect(entryIds($result))->toEqual([$assigned->id]);
    });

    it('given scope is set to resolvedBy and there is no query string, only the tickets resolved by the user or are closed are returned', function () {
        // ARRANGE
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $reporter = User::factory()->create();
        $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $user->id]);
        $attributes = ['ticketable_author_id' => $user->id, 'reporter_id' => $reporter->id];

        $resolved = Ticket::factory()->forAchievement($achievement)->resolved()->create([
            ...$attributes,
            'resolver_id' => $user->id,
            'created_at' => Carbon::parse('2024-01-02 00:00:00'),
        ]);
        $closed = Ticket::factory()->forAchievement($achievement)->closed()->create([
            ...$attributes,
            'resolver_id' => $user->id,
            'created_at' => Carbon::parse('2024-01-01 00:00:00'),
        ]);

        Ticket::factory()->forAchievement($achievement)->resolved()->create([
            ...$attributes,
            'resolver_id' => $otherUser->id,
        ]);
        Ticket::factory()->forAchievement($achievement)->open()->create($attributes);

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::ResolvedBy,
            $user,
            TicketListRequest::create('/internal-api/tickets', 'GET'),
        );

        // ASSERT
        expect(entryIds($result))->toEqual([$resolved->id, $closed->id]);
        expect($result['stateCounts']->request)->toBeInt();
    });
});

describe('available filters', function () {
    it('given each scope, only its filter kinds are exposed', function (TicketListScope $scope, array $expectedKinds) {
        // ARRANGE
        $action = new BuildTicketListAction();

        // ACT
        $filters = $action->getAvailableFilters($scope);

        // ASSERT
        expect(array_map(fn ($filter) => $filter->kind, $filters))->toEqual($expectedKinds);

        $emulatorFilter = collect($filters)->firstWhere('kind', TicketListFilterKind::Emulator);
        if ($emulatorFilter !== null) {
            expect($emulatorFilter->values[0])->toEqual('all');
            expect(end($emulatorFilter->values))->toEqual('unknown');
        }
    })->with([
        'all' => [TicketListScope::All, [TicketListFilterKind::Type, TicketListFilterKind::PublishedStatus, TicketListFilterKind::Mode, TicketListFilterKind::DeveloperType, TicketListFilterKind::Emulator]],
        'game' => [TicketListScope::Game, [TicketListFilterKind::Type, TicketListFilterKind::PublishedStatus, TicketListFilterKind::Mode, TicketListFilterKind::DeveloperType, TicketListFilterKind::Emulator]],
        'achievement' => [TicketListScope::Achievement, [TicketListFilterKind::Type, TicketListFilterKind::Mode, TicketListFilterKind::Emulator]],
        'assignedTo' => [TicketListScope::AssignedTo, [TicketListFilterKind::Type, TicketListFilterKind::PublishedStatus, TicketListFilterKind::Mode, TicketListFilterKind::Emulator]],
        'reportedBy' => [TicketListScope::ReportedBy, [TicketListFilterKind::Type, TicketListFilterKind::PublishedStatus, TicketListFilterKind::Mode, TicketListFilterKind::Emulator]],
        'awaitingReporter' => [TicketListScope::AwaitingReporter, []],
        'resolvedBy' => [TicketListScope::ResolvedBy, [TicketListFilterKind::Type, TicketListFilterKind::PublishedStatus, TicketListFilterKind::Mode, TicketListFilterKind::Developer, TicketListFilterKind::Reporter, TicketListFilterKind::Emulator]],
    ]);

    it('given a system id, then the emulator options are limited to that system', function () {
        // ARRANGE
        $system = System::factory()->create();
        $otherSystem = System::factory()->create();
        $emulator = Emulator::factory()->create(['name' => 'Bizhawk']);
        $otherEmulator = Emulator::factory()->create(['name' => 'PCSX2']);
        $emulator->systems()->attach($system->id);
        $otherEmulator->systems()->attach($otherSystem->id);

        // ACT
        $filters = (new BuildTicketListAction())->getAvailableFilters(TicketListScope::Game, $system->id);

        // ASSERT
        $emulatorFilter = collect($filters)->firstWhere('kind', TicketListFilterKind::Emulator);
        expect($emulatorFilter->values)->toEqual(['all', 'Bizhawk', 'unknown']);
    });
});
