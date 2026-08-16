<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Emulator;
use App\Models\Game;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Services\TicketListService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

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
        'resolved' => ['resolved', ['resolved', 'closed']],
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
});
