<?php

declare(strict_types=1);

use App\Community\Enums\TicketSignalVerdict;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\Ticket;
use App\Models\TicketVerdictAccuracyResult;
use App\Models\Trigger;
use App\Platform\Actions\MeasureTicketVerdictAccuracyAction;
use App\Platform\Enums\TriggerableType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function createFinishedTicket(
    ?TicketSignalVerdict $verdict,
    ?string $logicChangedAt = null,
    string $createdAt = '2026-06-01 12:00:00',
    string $resolvedAt = '2026-06-20 12:00:00',
): Ticket {
    $achievement = Achievement::factory()->promoted()->create([
        'game_id' => Game::factory()->create(['system_id' => System::factory()->create()->id])->id,
    ]);
    $reportTrigger = Trigger::factory()->create([
        'triggerable_type' => TriggerableType::Achievement,
        'triggerable_id' => $achievement->id,
        'version' => 2,
        'created_at' => Carbon::parse($createdAt)->subMonth(),
    ]);
    if ($logicChangedAt !== null) {
        Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'version' => 3,
            'parent_id' => $reportTrigger->id,
            'created_at' => Carbon::parse($logicChangedAt),
        ]);
    }

    return Ticket::factory()->forAchievement($achievement)->closed()->create([
        'resolved_signal_verdict' => $verdict,
        'created_at' => Carbon::parse($createdAt),
        'resolved_at' => Carbon::parse($resolvedAt),
    ]);
}

/**
 * @return array<string, array{int, int}> ticket count and correct count keyed by verdict
 */
function storedAccuracyByVerdict(): array
{
    return TicketVerdictAccuracyResult::query()
        ->orderBy('id')
        ->get()
        ->mapWithKeys(fn (TicketVerdictAccuracyResult $result) => [
            $result->verdict->value => [$result->ticket_count, $result->correct_count],
        ])
        ->all();
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-01 04:00:00'));
});

it('given a mix of tickets that did and did not get a logic change while open, scores a verdict as correct only when the outcome matched its prediction', function () {
    // ARRANGE
    createFinishedTicket(TicketSignalVerdict::ProbablyNotDefect);
    createFinishedTicket(TicketSignalVerdict::ProbablyNotDefect, logicChangedAt: '2026-06-06 12:00:00');
    createFinishedTicket(TicketSignalVerdict::ProbablyDefect, logicChangedAt: '2026-06-06 12:00:00');

    // ACT
    (new MeasureTicketVerdictAccuracyAction())->execute();

    // ASSERT
    expect(storedAccuracyByVerdict())->toEqual([
        TicketSignalVerdict::ProbablyNotDefect->value => [2, 1],
        TicketSignalVerdict::ProbablyDefect->value => [1, 1],
    ]);
});

it('given logic changes that land only before the report or after the ticket finished, treats the ticket as having no logic change', function () {
    // ARRANGE
    createFinishedTicket(TicketSignalVerdict::ProbablyNotDefect, logicChangedAt: '2026-05-30 12:00:00', createdAt: '2026-06-01 12:00:00', resolvedAt: '2026-06-20 12:00:00');
    createFinishedTicket(TicketSignalVerdict::ProbablyDefect, logicChangedAt: '2026-06-21 12:00:00', createdAt: '2026-06-01 12:00:00', resolvedAt: '2026-06-20 12:00:00');

    // ACT
    (new MeasureTicketVerdictAccuracyAction())->execute();

    // ASSERT
    expect(storedAccuracyByVerdict())->toEqual([
        TicketSignalVerdict::ProbablyNotDefect->value => [1, 1],
        TicketSignalVerdict::ProbablyDefect->value => [1, 0],
    ]);
});
