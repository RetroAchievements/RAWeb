<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Enums\TicketListFilterKind;
use App\Platform\Enums\TicketListStatusFilter;
use App\Platform\Services\TicketListService;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;

uses(LazilyRefreshDatabase::class);

function defaultTicketListFilterOptions(array $overrides = []): array
{
    return array_merge([
        'status' => 'unresolved',
        'type' => 0,
        'publishedStatus' => 'all',
        'mode' => 'all',
        'developerType' => 'all',
        'developer' => 'all',
        'reporter' => 'all',
        'emulator' => 'all',
        'core' => '',
    ], $overrides);
}

function createTicketableAchievement(?User $author = null): Achievement
{
    $author ??= User::factory()->create();
    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);

    return Achievement::factory()->create([
        'game_id' => $game->id,
        'user_id' => $author->id,
    ]);
}

/**
 * @return array<string, Ticket>
 */
function createTicketInEveryState(Achievement $achievement): array
{
    return [
        'open' => Ticket::factory()->forAchievement($achievement)->open()->create(),
        'request' => Ticket::factory()->forAchievement($achievement)->request()->create(),
        'resolved' => Ticket::factory()->forAchievement($achievement)->resolved()->create(),
        'closed' => Ticket::factory()->forAchievement($achievement)->closed()->create(),
        'quarantined' => Ticket::factory()->forAchievement($achievement)->quarantined()->create(),
    ];
}

/**
 * @return int[]
 */
function buildTicketIds(TicketListService $service, array $filterOptions, ?User $comparisonUser = null): array
{
    $ids = $service->applyFilters(Ticket::query()->withLiveTicketable(), $filterOptions, $comparisonUser)->pluck('id')->all();
    sort($ids);

    return $ids;
}

/**
 * @param Ticket[] $tickets
 * @return int[]
 */
function sortedTicketIds(array $tickets): array
{
    $ids = array_map(fn (Ticket $ticket) => $ticket->id, $tickets);
    sort($ids);

    return $ids;
}

describe('getFilterOptions', function () {
    it('given no filter params, every default is filled', function () {
        // ARRANGE
        $service = new TicketListService();

        // ACT
        $options = $service->getFilterOptions(Request::create('/tickets', 'GET'));

        // ASSERT
        expect($options)->toEqual(defaultTicketListFilterOptions());
    });

    it('given a default status and no status in the URL, the default status is used', function () {
        // ARRANGE
        $service = new TicketListService();

        // ACT
        $options = $service->getFilterOptions(Request::create('/tickets', 'GET'), TicketListStatusFilter::Request);

        // ASSERT
        expect($options['status'])->toEqual('request');
    });

    it('given a default status and a status in the URL, the URL status is used', function () {
        // ARRANGE
        $service = new TicketListService();
        $request = Request::create('/tickets', 'GET', ['filter' => ['status' => 'all']]);

        // ACT
        $options = $service->getFilterOptions($request, TicketListStatusFilter::Request);

        // ASSERT
        expect($options['status'])->toEqual('all');
    });

    it('given a core search term with outer spaces, the term is automatically trimmed', function () {
        // ARRANGE
        $service = new TicketListService();
        $request = Request::create('/tickets', 'GET', ['filter' => ['core' => '  nestopia ']]);

        // ACT
        $options = $service->getFilterOptions($request);

        // ASSERT
        expect($options['core'])->toEqual('nestopia');
    });
});

describe('applyFilters', function () {
    it('given a status filter, only tickets with matching status values are returned', function (string $status, array $expectedStateKeys) {
        // ARRANGE
        $tickets = createTicketInEveryState(createTicketableAchievement());
        $service = new TicketListService();

        // ACT
        $ids = buildTicketIds($service, defaultTicketListFilterOptions(['status' => $status]));

        // ASSERT
        $expectedTickets = array_map(fn (string $key) => $tickets[$key], $expectedStateKeys);
        expect($ids)->toEqual(sortedTicketIds($expectedTickets));
    })->with([
        'unresolved' => ['unresolved', ['open', 'request']],
        'request' => ['request', ['request']],
        'resolved' => ['resolved', ['resolved']],
        'closed' => ['closed', ['closed']],
        'quarantined' => ['quarantined', ['quarantined']],
        'all' => ['all', ['open', 'request', 'resolved', 'closed', 'quarantined']],
    ]);

    it('given a comparison user, the developer and reporter filters compare against that user', function (string $kind, string $value, string $expectedTicketKey) {
        // ARRANGE
        $comparisonUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownAchievement = createTicketableAchievement($comparisonUser);
        $otherAchievement = createTicketableAchievement($otherUser);
        $tickets = [
            'own' => Ticket::factory()->forAchievement($ownAchievement)->open()->create([
                'ticketable_author_id' => $comparisonUser->id,
                'reporter_id' => $comparisonUser->id,
            ]),
            'other' => Ticket::factory()->forAchievement($otherAchievement)->open()->create([
                'ticketable_author_id' => $otherUser->id,
                'reporter_id' => $otherUser->id,
            ]),
        ];
        $service = new TicketListService();

        // ACT
        $ids = buildTicketIds($service, defaultTicketListFilterOptions([$kind => $value]), $comparisonUser);

        // ASSERT
        expect($ids)->toEqual([$tickets[$expectedTicketKey]->id]);
    })->with([
        'developer self' => ['developer', 'self', 'own'],
        'developer others' => ['developer', 'others', 'other'],
        'reporter self' => ['reporter', 'self', 'own'],
        'reporter others' => ['reporter', 'others', 'other'],
    ]);

    it('given no comparison user, the developer and reporter values are ignored', function () {
        // ARRANGE
        $developer = User::factory()->create();
        $reporter = User::factory()->create();
        $achievement = createTicketableAchievement($developer);
        $ticketA = Ticket::factory()->forAchievement($achievement)->open()->create([
            'ticketable_author_id' => $developer->id,
            'reporter_id' => $reporter->id,
        ]);
        $ticketB = Ticket::factory()->forAchievement($achievement)->open()->create([
            'ticketable_author_id' => User::factory()->create()->id,
            'reporter_id' => User::factory()->create()->id,
        ]);
        $service = new TicketListService();

        // ACT
        $ids = buildTicketIds($service, defaultTicketListFilterOptions([
            'developer' => 'self',
            'reporter' => 'self',
        ]));

        // ASSERT
        expect($ids)->toEqual(sortedTicketIds([$ticketA, $ticketB]));
    });

    it('given an emulator, only tickets for that emulator are returned', function () {
        // ARRANGE
        $achievement = createTicketableAchievement();
        $emulator = Emulator::factory()->create(['name' => 'RAlibretro']);
        $otherEmulator = Emulator::factory()->create(['name' => 'RANes']);
        $matchingTicket = Ticket::factory()->forAchievement($achievement)->open()->create([
            'emulator_id' => $emulator->id,
        ]);
        Ticket::factory()->forAchievement($achievement)->open()->create([
            'emulator_id' => $otherEmulator->id,
        ]);
        $service = new TicketListService();

        // ACT
        $ids = buildTicketIds($service, defaultTicketListFilterOptions(['emulator' => 'RAlibretro']));

        // ASSERT
        expect($ids)->toEqual([$matchingTicket->id]);
    });

    it('given a core search term, tickets match on any spelling that contains the term and ignore letter case', function () {
        // ARRANGE
        $achievement = createTicketableAchievement();
        $matchingTickets = [
            Ticket::factory()->forAchievement($achievement)->open()->create(['emulator_core' => 'nestopia']),
            Ticket::factory()->forAchievement($achievement)->open()->create(['emulator_core' => 'FCEUmm, Mesen and Nestopia UE']),
        ];
        Ticket::factory()->forAchievement($achievement)->open()->create(['emulator_core' => 'fceumm']);
        Ticket::factory()->forAchievement($achievement)->open()->create(['emulator_core' => null]);
        $service = new TicketListService();

        // ACT
        $ids = buildTicketIds($service, defaultTicketListFilterOptions(['core' => 'NESTOPIA']));

        // ASSERT
        expect($ids)->toEqual(sortedTicketIds($matchingTickets));
    });

    it('given a core search term with a wildcard character, the character matches only itself', function () {
        // ARRANGE
        $achievement = createTicketableAchievement();
        $matchingTicket = Ticket::factory()->forAchievement($achievement)->open()->create(['emulator_core' => 'genesis_plus_gx']);
        Ticket::factory()->forAchievement($achievement)->open()->create(['emulator_core' => 'Genesis Plus GX']);
        $service = new TicketListService();

        // ACT
        $ids = buildTicketIds($service, defaultTicketListFilterOptions(['core' => 's_p']));

        // ASSERT
        expect($ids)->toEqual([$matchingTicket->id]);
    });

    it('given a core search term, facet counts skip the core filter and still apply it to other filters', function () {
        // ARRANGE
        $achievement = createTicketableAchievement();
        Ticket::factory()->forAchievement($achievement)->open()->create(['emulator_core' => 'nestopia', 'hardcore' => true]);
        Ticket::factory()->forAchievement($achievement)->open()->create(['emulator_core' => 'fceumm', 'hardcore' => true]);
        $service = new TicketListService();
        $options = defaultTicketListFilterOptions(['core' => 'nestopia']);

        // ACT
        $counts = $service->getFacetCounts($options, Ticket::query(), [TicketListFilterKind::Mode, TicketListFilterKind::Core]);

        // ASSERT
        expect($counts)->not->toHaveKey('core');
        expect($counts['mode']['hardcore'])->toEqual(1);
    });
});

it('given a facet filter, its counts always match the corresponding list filters', function (TicketListFilterKind $kind) {
    // ARRANGE
    $this->seed(RolesTableSeeder::class);
    foreach ([Role::DEVELOPER, Role::DEVELOPER_JUNIOR, Role::DEVELOPER_RETIRED] as $role) {
        $author = User::factory()->create();
        $author->assignRole($role);
        $authors[$role] = $author;
        $achievement = createTicketableAchievement($author);
        $achievement->is_promoted = $role !== Role::DEVELOPER_JUNIOR;
        $achievement->save();
        Ticket::factory()->forAchievement($achievement)->open()->create(['ticketable_author_id' => $author->id]);
    }
    foreach ([LeaderboardState::Active, LeaderboardState::Unpromoted] as $state) {
        $leaderboard = Leaderboard::factory()->create(['game_id' => $achievement->game_id, 'state' => $state]);
        Ticket::factory()->forLeaderboard($leaderboard)->open()->create(['ticketable_author_id' => $author->id]);
    }
    $service = new TicketListService();
    $options = defaultTicketListFilterOptions([$kind->value => $kind->values()[1]]);

    // ACT
    $counts = $service->getFacetCounts($options, Ticket::query(), [$kind]);

    // ASSERT
    if ($kind === TicketListFilterKind::DeveloperType) {
        expect($counts[$kind->value])->toEqual(['all' => 5, 'active' => 2, 'junior' => 1, 'inactive' => 3]);
        $authors[Role::DEVELOPER]->assignRole(Role::DEVELOPER_JUNIOR);
        $overlappingRoles = $service->getFacetCounts($options, Ticket::query(), [$kind]);
        expect($overlappingRoles[$kind->value])->toEqual(['all' => 5, 'active' => 2, 'junior' => 2, 'inactive' => 3]);
        $authors[Role::DEVELOPER]->removeRole(Role::DEVELOPER_JUNIOR);
    }
    foreach ($kind->values() as $value) {
        $expected = $service->applyFilters(Ticket::query(), array_merge($options, [$kind->value => $value]))->count();
        expect($counts[$kind->value][$value])->toEqual($expected);
    }
})->with([
    'publication status' => [TicketListFilterKind::PublishedStatus],
    'developer type' => [TicketListFilterKind::DeveloperType],
]);
