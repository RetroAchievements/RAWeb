<?php

declare(strict_types=1);

use App\Community\Enums\TicketResolution;
use App\Community\Enums\TicketSignalVerdict;
use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\BuildTicketListAction;
use App\Platform\Data\TicketListEntryData;
use App\Platform\Enums\TicketListFilterKind;
use App\Platform\Enums\TicketListScope;
use App\Platform\Enums\TicketListSortField;
use App\Platform\Requests\TicketListRequest;
use Carbon\Carbon;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
                'assignee_id' => $developer->id,
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
    it('excludes tickets for deleted ticketables from rows and counts', function () {
        // ARRANGE
        $fixture = createTicketListFixture(2);
        $fixture['otherAchievement']->delete();

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::All,
            null,
            TicketListRequest::create('/internal-api/tickets', 'GET', [
                'filter' => ['emulator' => 'unknown'],
            ]),
            viewer: null,
        );

        // ASSERT
        expect(entryIds($result))->toEqual([$fixture['tickets'][0]->id]);
        expect($result['paginatedTickets']->total)->toEqual(1);
        expect($result['paginatedTickets']->unfilteredTotal)->toEqual(1);
        expect($result['stateCounts']->unresolved)->toEqual(1);
        expect($result['stateCounts']->request)->toEqual(0);
        expect($result['stateCounts']->all)->toEqual(1);
        expect($result['facetCounts']['type']['0'])->toEqual(1);
    });

    it('given scope is set to game and there is no query string, only the open and request tickets are returned for the game, sorted by newest tickets first', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $reporter = User::factory()->create();
        $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
        $otherGame = Game::factory()->create(['system_id' => System::factory()->create()->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);
        $otherAchievement = Achievement::factory()->promoted()->create(['game_id' => $otherGame->id, 'user_id' => $developer->id]);
        $attributes = ['assignee_id' => $developer->id, 'reporter_id' => $reporter->id];

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
            viewer: null,
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
            'assignee_id' => $developer->id,
            'reporter_id' => $reporter->id,
        ]);

        Ticket::factory()->forAchievement($ownAchievement)->closed()->create([
            'assignee_id' => $developer->id,
            'reporter_id' => $reporter->id,
        ]);

        Ticket::factory()->forAchievement($otherAchievement)->open()->create([
            'assignee_id' => $otherDeveloper->id,
            'reporter_id' => $developer->id,
        ]);

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::AssignedTo,
            $developer,
            TicketListRequest::create('/internal-api/tickets', 'GET'),
            viewer: null,
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
        $attributes = ['assignee_id' => $user->id, 'reporter_id' => $reporter->id];

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

        Ticket::factory()->forAchievement($achievement)->open()->create([
            ...$attributes,
            'resolver_id' => $user->id,
        ]);

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::ResolvedBy,
            $user,
            TicketListRequest::create('/internal-api/tickets', 'GET'),
            viewer: null,
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
        $filters = $action->getAvailableFilters($scope, viewer: null);

        // ASSERT
        expect(array_map(fn ($filter) => $filter->kind, $filters))->toEqual($expectedKinds);

        foreach ($filters as $filter) {
            expect($filter->isFreeText)->toEqual($filter->kind === TicketListFilterKind::Core);
        }

        $emulatorFilter = collect($filters)->firstWhere('kind', TicketListFilterKind::Emulator);
        if ($emulatorFilter !== null) {
            expect($emulatorFilter->values[0])->toEqual('all');
            expect(end($emulatorFilter->values))->toEqual('unknown');
        }
    })->with([
        'all' => [TicketListScope::All, [TicketListFilterKind::Type, TicketListFilterKind::Mode, TicketListFilterKind::PublishedStatus, TicketListFilterKind::DeveloperType, TicketListFilterKind::Resolution, TicketListFilterKind::System, TicketListFilterKind::Emulator, TicketListFilterKind::Core]],
        'game' => [TicketListScope::Game, [TicketListFilterKind::Type, TicketListFilterKind::Mode, TicketListFilterKind::PublishedStatus, TicketListFilterKind::DeveloperType, TicketListFilterKind::Resolution, TicketListFilterKind::Emulator, TicketListFilterKind::Core]],
        'achievement' => [TicketListScope::Achievement, [TicketListFilterKind::Type, TicketListFilterKind::Mode, TicketListFilterKind::Resolution, TicketListFilterKind::Emulator, TicketListFilterKind::Core]],
        'assignedTo' => [TicketListScope::AssignedTo, [TicketListFilterKind::Type, TicketListFilterKind::Mode, TicketListFilterKind::PublishedStatus, TicketListFilterKind::Resolution, TicketListFilterKind::System, TicketListFilterKind::Emulator, TicketListFilterKind::Core]],
        'reportedBy' => [TicketListScope::ReportedBy, [TicketListFilterKind::Type, TicketListFilterKind::Mode, TicketListFilterKind::PublishedStatus, TicketListFilterKind::Resolution, TicketListFilterKind::System, TicketListFilterKind::Emulator, TicketListFilterKind::Core]],
        'awaitingReporter' => [TicketListScope::AwaitingReporter, []],
        'resolvedBy' => [TicketListScope::ResolvedBy, [TicketListFilterKind::Type, TicketListFilterKind::Mode, TicketListFilterKind::PublishedStatus, TicketListFilterKind::Developer, TicketListFilterKind::Reporter, TicketListFilterKind::Resolution, TicketListFilterKind::System, TicketListFilterKind::Emulator, TicketListFilterKind::Core]],
    ]);

    it('given active and inactive systems, lists active systems by name with labels for their IDs', function () {
        // ARRANGE
        $zebraSystem = System::factory()->create(['name' => 'Zebra System', 'active' => true]);
        $alphaSystem = System::factory()->create(['name' => 'Alpha System', 'active' => true]);
        System::factory()->create(['name' => 'Retired System', 'active' => false]);

        // ACT
        $filters = (new BuildTicketListAction())->getAvailableFilters(TicketListScope::All, viewer: null);

        // ASSERT
        $systemFilter = collect($filters)->firstWhere('kind', TicketListFilterKind::System);

        expect($systemFilter->values)->toEqual(['all', (string) $alphaSystem->id, (string) $zebraSystem->id]);
        expect((array) $systemFilter->valueLabels)->toEqual([
            $alphaSystem->id => 'Alpha System',
            $zebraSystem->id => 'Zebra System',
        ]);
    });

    it('given a system id, then the emulator options are limited to that system', function () {
        // ARRANGE
        $system = System::factory()->create();
        $otherSystem = System::factory()->create();
        $emulator = Emulator::factory()->create(['name' => 'Bizhawk']);
        $otherEmulator = Emulator::factory()->create(['name' => 'PCSX2']);
        $emulator->systems()->attach($system->id);
        $otherEmulator->systems()->attach($otherSystem->id);

        // ACT
        $filters = (new BuildTicketListAction())->getAvailableFilters(TicketListScope::Game, viewer: null, systemId: $system->id);

        // ASSERT
        $emulatorFilter = collect($filters)->firstWhere('kind', TicketListFilterKind::Emulator);
        expect($emulatorFilter->values)->toEqual(['all', 'Bizhawk', 'unknown']);
    });
});

describe('grouping sorts', function () {
    it('given the game sort from A to Z, groups tickets by game in title order even when a later game has more tickets, keeps same-titled games apart, and counts every ticket in each group', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $attributes = ['assignee_id' => $developer->id, 'reporter_id' => User::factory()->create()->id];

        $zelda = Game::factory()->create(['title' => 'Zelda', 'system_id' => System::factory()->create()->id]);
        $metroidNes = Game::factory()->create(['title' => 'Metroid', 'system_id' => System::factory()->create()->id]);
        $metroidGba = Game::factory()->create(['title' => 'Metroid', 'system_id' => System::factory()->create()->id]);

        $zeldaAchievement = Achievement::factory()->promoted()->create(['game_id' => $zelda->id, 'user_id' => $developer->id]);
        $metroidNesAchievement = Achievement::factory()->promoted()->create(['game_id' => $metroidNes->id, 'user_id' => $developer->id]);
        $metroidNesLeaderboard = Leaderboard::factory()->create(['game_id' => $metroidNes->id]);
        $metroidGbaAchievement = Achievement::factory()->promoted()->create(['game_id' => $metroidGba->id, 'user_id' => $developer->id]);

        $zeldaTicket = Ticket::factory()->forAchievement($zeldaAchievement)->open()->create($attributes);
        $olderZeldaTickets = Ticket::factory()
            ->count(2)
            ->forAchievement($zeldaAchievement)
            ->open()
            ->sequence(['created_at' => Carbon::parse('2023-02-01')], ['created_at' => Carbon::parse('2023-01-01')])
            ->create($attributes);
        $olderMetroidNesTicket = Ticket::factory()->forAchievement($metroidNesAchievement)->open()->create([
            ...$attributes,
            'created_at' => Carbon::parse('2024-01-01 00:00:00'),
        ]);
        $newerMetroidNesTicket = Ticket::factory()->forLeaderboard($metroidNesLeaderboard)->open()->create([
            ...$attributes,
            'created_at' => Carbon::parse('2024-02-01 00:00:00'),
        ]);
        $metroidGbaTicket = Ticket::factory()->forAchievement($metroidGbaAchievement)->open()->create($attributes);

        Ticket::factory()->forAchievement($zeldaAchievement)->resolved()->create($attributes);

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::All,
            null,
            TicketListRequest::create('/tickets', 'GET', ['sort' => 'game']),
            viewer: null,
        );

        // ASSERT
        expect(entryIds($result))->toEqual([
            $newerMetroidNesTicket->id,
            $olderMetroidNesTicket->id,
            $metroidGbaTicket->id,
            $zeldaTicket->id,
            ...$olderZeldaTickets->pluck('id')->all(),
        ]);
        expect($result['grouping']->field)->toEqual(TicketListSortField::Game);
        expect($result['grouping']->counts)->toEqual([
            $zelda->id => 3,
            $metroidNes->id => 2,
            $metroidGba->id => 1,
        ]);
    });

    it('given a game group continues onto the next page, its count still covers the whole group', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $game = Game::factory()->create(['title' => 'Castlevania', 'system_id' => System::factory()->create()->id]);
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);
        Ticket::factory()->count(53)->forAchievement($achievement)->open()->create(['assignee_id' => $developer->id]);

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::All,
            null,
            TicketListRequest::create('/tickets', 'GET', ['sort' => 'game']),
            viewer: null,
        );

        // ASSERT
        expect($result['paginatedTickets']->items)->toHaveCount(50);
        expect($result['grouping']->counts)->toEqual([$game->id => 53]);
    });

    it('given the second page of the game sort, continues the game that the first page cut off', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $createGameTickets = function (string $title, string $firstCreatedAt) use ($developer): array {
            $game = Game::factory()->create(['title' => $title, 'system_id' => System::factory()->create()->id]);
            $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id]);
            $tickets = Ticket::factory()
                ->count(30)
                ->forAchievement($achievement)
                ->open()
                ->sequence(fn (Sequence $sequence) => ['created_at' => Carbon::parse($firstCreatedAt)->addDays($sequence->index)])
                ->create(['assignee_id' => $developer->id]);

            return [$game, $tickets];
        };
        [$castlevania] = $createGameTickets('Castlevania', '2024-01-01');

        [$contra, $contraTickets] = $createGameTickets('Contra', '2023-01-01');

        // ACT
        $firstPage = (new BuildTicketListAction())->execute(
            TicketListScope::All,
            null,
            TicketListRequest::create('/tickets', 'GET', ['sort' => 'game']),
            viewer: null,
        );
        $secondPage = (new BuildTicketListAction())->execute(
            TicketListScope::All,
            null,
            TicketListRequest::create('/tickets', 'GET', ['sort' => 'game', 'page' => ['number' => 2]]),
            viewer: null,
        );

        // ASSERT
        expect($firstPage['grouping']->counts)->toEqual([$castlevania->id => 30, $contra->id => 30]);
        expect($firstPage['grouping']->isFirstGroupContinued)->toBeFalse();
        expect($secondPage['grouping']->counts)->toEqual([$contra->id => 30]);
        expect($secondPage['grouping']->isFirstGroupContinued)->toBeTrue();

        $contraIdsNewestFirst = $contraTickets->sortByDesc('created_at')->pluck('id')->values()->all();
        expect(entryIds($firstPage))->toHaveCount(50);
        expect(array_slice(entryIds($firstPage), 30))->toEqual(array_slice($contraIdsNewestFirst, 0, 20));
        expect(entryIds($secondPage))->toEqual(array_slice($contraIdsNewestFirst, 20));
    });

    it('given a page that starts exactly where a game starts, leaves the previous game off that page', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $castlevania = Game::factory()->create(['title' => 'Castlevania', 'system_id' => System::factory()->create()->id]);
        $contra = Game::factory()->create(['title' => 'Contra', 'system_id' => System::factory()->create()->id]);
        $castlevaniaAchievement = Achievement::factory()->promoted()->create(['game_id' => $castlevania->id, 'user_id' => $developer->id]);
        $contraAchievement = Achievement::factory()->promoted()->create(['game_id' => $contra->id, 'user_id' => $developer->id]);
        Ticket::factory()->count(50)->forAchievement($castlevaniaAchievement)->open()->create(['assignee_id' => $developer->id]);
        $contraTickets = Ticket::factory()->count(5)->forAchievement($contraAchievement)->open()->create(['assignee_id' => $developer->id]);

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::All,
            null,
            TicketListRequest::create('/tickets', 'GET', ['sort' => 'game', 'page' => ['number' => 2]]),
            viewer: null,
        );

        // ASSERT
        expect($result['grouping']->counts)->toEqual([$contra->id => 5]);
        expect($result['grouping']->isFirstGroupContinued)->toBeFalse();
        expect(entryIds($result))->toEqualCanonicalizing($contraTickets->pluck('id')->all());
    });

    it('given a later page of the achievement sort, says whether its first group started on the previous page', function (
        int $firstAchievementTicketCount,
        bool $isFirstGroupContinued,
        int $pageTicketCount,
    ) {
        // ARRANGE
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
        $alpha = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id, 'title' => 'Alpha']);
        $beta = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $developer->id, 'title' => 'Beta']);
        Ticket::factory()->count($firstAchievementTicketCount)->forAchievement($alpha)->open()->create(['assignee_id' => $developer->id]);
        Ticket::factory()->count(5)->forAchievement($beta)->open()->create(['assignee_id' => $developer->id]);

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::Game,
            $game,
            TicketListRequest::create('/game/' . $game->id . '/tickets', 'GET', [
                'scope' => TicketListScope::Game->value,
                'sort' => 'ticketable',
                'page' => ['number' => 2],
            ]),
            viewer: null,
        );

        // ASSERT
        expect($result['grouping']->isFirstGroupContinued)->toEqual($isFirstGroupContinued);
        expect(entryIds($result))->toHaveCount($pageTicketCount);
    })->with([
        'the page starts inside a group' => [53, true, 8],
        'the page starts where a group starts' => [50, false, 5],
        'the page is past the end' => [45, false, 50],
    ]);

    it('given the game sort with the most tickets first, orders games by ticket count and breaks ties A to Z', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $alpha = Game::factory()->create(['title' => 'Alpha', 'system_id' => System::factory()->create()->id]);
        $beta = Game::factory()->create(['title' => 'Beta', 'system_id' => System::factory()->create()->id]);
        $contra = Game::factory()->create(['title' => 'Contra', 'system_id' => System::factory()->create()->id]);
        $alphaAchievement = Achievement::factory()->promoted()->create(['game_id' => $alpha->id, 'user_id' => $developer->id]);
        $betaLeaderboard = Leaderboard::factory()->create(['game_id' => $beta->id]);
        $contraAchievement = Achievement::factory()->promoted()->create(['game_id' => $contra->id, 'user_id' => $developer->id]);
        $alphaTickets = Ticket::factory()->count(2)->forAchievement($alphaAchievement)->open()->create(['assignee_id' => $developer->id]);
        $betaTickets = Ticket::factory()->count(2)->forLeaderboard($betaLeaderboard)->open()->create();
        $contraTickets = Ticket::factory()->count(3)->forAchievement($contraAchievement)->open()->create(['assignee_id' => $developer->id]);

        // ACT
        $result = (new BuildTicketListAction())->execute(
            TicketListScope::All,
            null,
            TicketListRequest::create('/tickets', 'GET', ['sort' => '-game']),
            viewer: null,
        );

        // ASSERT
        $entryIds = entryIds($result);
        expect(array_slice($entryIds, 0, 3))->toEqualCanonicalizing($contraTickets->pluck('id')->all());
        expect(array_slice($entryIds, 3, 2))->toEqualCanonicalizing($alphaTickets->pluck('id')->all());
        expect(array_slice($entryIds, 5))->toEqualCanonicalizing($betaTickets->pluck('id')->all());
        expect(array_keys($result['grouping']->counts))->toEqual([$contra->id, $alpha->id, $beta->id]);
        expect($result['grouping']->counts)->toEqual([$contra->id => 3, $alpha->id => 2, $beta->id => 2]);
    });
});

it('prefers the URL sort over the persisted sort', function () {
    // ACT
    $request = TicketListRequest::create('/tickets', 'GET', ['sort' => '-resolvedAt'], [
        'datatable_view_preference_tickets_all' => json_encode(['sortParam' => 'state']),
    ]);

    // ASSERT
    expect($request->getSort())->toEqual([
        'field' => TicketListSortField::ResolvedAt,
        'direction' => 'desc',
    ]);
});

it('the generated sort column is consistent with the enum sort order', function () {
    // ARRANGE
    $fixture = createTicketListFixture();
    foreach (TicketState::cases() as $state) {
        Ticket::factory()->forAchievement($fixture['achievement'])->create(['state' => $state]);
    }

    $ranksByState = Ticket::query()->pluck('state_sort_order', 'state');

    // ASSERT
    foreach (TicketState::cases() as $state) {
        expect((int) $ranksByState[$state->value])->toEqual($state->sortOrder());
    }
});

it('given a closed ticket with a resolution, includes the resolution value in props', function () {
    // ARRANGE
    $fixture = createTicketListFixture(0);
    $ticket = Ticket::factory()->forAchievement($fixture['achievement'])->closed()->create([
        'resolution' => TicketResolution::UnableToReproduce,
    ]);

    // ACT
    $result = (new BuildTicketListAction())->execute(
        TicketListScope::All,
        null,
        TicketListRequest::create('/tickets', 'GET', ['filter' => ['status' => 'closed']]),
        viewer: null,
    );

    // ASSERT
    expect(entryIds($result))->toEqual([$ticket->id]);
    expect($result['paginatedTickets']->items[0]->resolution)->toEqual(TicketResolution::UnableToReproduce);
});

it('given a viewer who can see signal verdicts, the signal filter is available and usable', function () {
    // ARRANGE
    $this->seed(RolesTableSeeder::class);
    $viewer = User::factory()->create();
    $viewer->assignRole(Role::DEVELOPER);
    $fixture = createTicketListFixture(0);
    $flaggedTicket = Ticket::factory()->forAchievement($fixture['achievement'])->open()->create([
        'current_signal_verdict' => TicketSignalVerdict::ProbablyNotDefect,
    ]);
    Ticket::factory()->forAchievement($fixture['achievement'])->open()->create();
    $action = new BuildTicketListAction();

    // ACT
    $result = $action->execute(
        TicketListScope::All,
        null,
        TicketListRequest::create('/tickets', 'GET', ['filter' => ['signal' => 'probably-not-defect']]),
        viewer: $viewer,
    );
    $filters = $action->getAvailableFilters(TicketListScope::All, $viewer);

    // ASSERT
    expect(entryIds($result))->toEqual([$flaggedTicket->id]);
    expect($result['facetCounts']['signal'])->toEqual(['all' => 2, 'probably-not-defect' => 1, 'none' => 1]);
    expect(collect($filters)->pluck('kind'))->toContain(TicketListFilterKind::Signal);
});

it('given a viewer who cannot see signal verdicts, the signal filter is not available or usable', function () {
    // ARRANGE
    $viewer = User::factory()->create();
    $fixture = createTicketListFixture(0);
    $flaggedTicket = Ticket::factory()->forAchievement($fixture['achievement'])->open()->create([
        'current_signal_verdict' => TicketSignalVerdict::ProbablyNotDefect,
    ]);
    $unflaggedTicket = Ticket::factory()->forAchievement($fixture['achievement'])->open()->create();
    $action = new BuildTicketListAction();

    // ACT
    $result = $action->execute(
        TicketListScope::All,
        null,
        TicketListRequest::create('/tickets', 'GET', ['filter' => ['signal' => 'probably-not-defect']]),
        viewer: $viewer,
    );
    $filters = $action->getAvailableFilters(TicketListScope::All, $viewer);

    // ASSERT
    expect(entryIds($result))->toEqualCanonicalizing([$flaggedTicket->id, $unflaggedTicket->id]);
    expect($result['stateCounts']->unresolved)->toEqual(2);
    expect($result['facetCounts'])->not->toHaveKey('signal');
    expect(collect($filters)->pluck('kind'))->not->toContain(TicketListFilterKind::Signal);
});
