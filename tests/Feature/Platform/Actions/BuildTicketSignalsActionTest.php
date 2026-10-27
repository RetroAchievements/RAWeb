<?php

declare(strict_types=1);

use App\Community\Enums\TicketSignal;
use App\Community\Enums\TicketSignalVerdict;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\Ticket;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Actions\BuildTicketSignalsAction;
use App\Platform\Data\TicketSignalsData;
use App\Platform\Enums\TriggerableType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function createSignalsGame(): Game
{
    return Game::factory()->create(['system_id' => System::factory()->create()->id]);
}

function seedSignalsUnlocks(Achievement $achievement, int $count, Carbon $unlockedAt): void
{
    PlayerAchievement::insert(User::factory()->count($count)->create()->map(fn (User $user) => [
        'user_id' => $user->id,
        'achievement_id' => $achievement->id,
        'unlocked_at' => $unlockedAt,
    ])->all());
}

function seedSignalsPlayers(int $gameId, int $count, Carbon $playedAt): void
{
    foreach (User::factory()->count($count)->create() as $user) {
        PlayerSession::factory()->create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'rich_presence_updated_at' => $playedAt,
        ]);
    }
}

/**
 * @param 'same-logic'|'newer-logic'|'older-logic'|'unknown-logic'|'hardcore-later'|'manual'|'before-report'|null $reporterUnlock
 */
function createSignalsTicket(
    int $unlocksBeforeReport,
    ?string $reporterUnlock = null,
    bool $isTicketLinkedToTrigger = true,
    bool $isPublished = true,
    int $otherPlayers = 5,
): Ticket {
    $reportedAt = Carbon::parse('2026-06-01 12:00:00');
    $achievement = Achievement::factory()->promoted()->create([
        'game_id' => createSignalsGame()->id,
        'is_promoted' => $isPublished,
    ]);
    $reportTrigger = Trigger::factory()->create([
        'triggerable_type' => TriggerableType::Achievement,
        'triggerable_id' => $achievement->id,
        'version' => 2,
        'created_at' => $reportedAt->copy()->subDays(20),
    ]);
    $reporter = User::factory()->create();
    $ticket = Ticket::factory()->forAchievement($achievement)->create([
        'reporter_id' => $reporter->id,
        'trigger_id' => $isTicketLinkedToTrigger ? $reportTrigger->id : null,
        'created_at' => $reportedAt,
    ]);
    seedSignalsUnlocks($achievement, $unlocksBeforeReport, $reportedAt->copy()->subDays(5));
    seedSignalsPlayers($achievement->game_id, $otherPlayers, $reportedAt->copy()->subDays(3));

    if ($reporterUnlock === null) {
        return $ticket;
    }

    $unlockTriggerId = match ($reporterUnlock) {
        'unknown-logic' => null,
        'newer-logic' => Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'version' => 3,
            'created_at' => $reportedAt->copy()->addDays(3),
        ])->id,
        'older-logic' => Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'version' => 1,
            'created_at' => $reportedAt->copy()->subDays(40),
        ])->id,
        default => $reportTrigger->id,
    };
    PlayerAchievement::factory()->create([
        'user_id' => $reporter->id,
        'achievement_id' => $achievement->id,
        'trigger_id' => $unlockTriggerId,
        'unlocker_id' => $reporterUnlock === 'manual' ? User::factory()->create()->id : null,
        'unlocked_at' => in_array($reporterUnlock, ['before-report', 'hardcore-later'], true)
            ? $reportedAt->copy()->subDays(2)
            : $reportedAt->copy()->addWeek(),
        'unlocked_hardcore_at' => $reporterUnlock === 'hardcore-later' ? $reportedAt->copy()->addWeek() : null,
    ]);

    return $ticket;
}

/**
 * @return array<int, TicketSignalsData|null>
 */
function buildSignals(Ticket ...$tickets): array
{
    $ids = array_map(fn (Ticket $ticket) => $ticket->id, $tickets);

    return (new BuildTicketSignalsAction())->execute(Ticket::with('ticketable')->whereKey($ids)->get());
}

it('settles the verdict from the unlock count together with the reporter unlock', function (
    int $unlocksBeforeReport,
    ?string $reporterUnlock,
    ?TicketSignalVerdict $expectedVerdict,
    array $expectedKinds,
) {
    // ARRANGE
    $ticket = createSignalsTicket($unlocksBeforeReport, $reporterUnlock);

    // ACT
    $signals = buildSignals($ticket)[$ticket->id];

    // ASSERT
    expect($signals?->verdict)->toEqual($expectedVerdict);
    expect($signals?->kinds ?? [])->toEqual($expectedKinds);
})->with([
    'exactly 50 unlocks' => [50, null, TicketSignalVerdict::ProbablyNotDefect, [TicketSignal::ManyRecentUnlocks]],
    'just under 50 unlocks' => [49, null, null, []],
    'zero unlocks' => [0, null, TicketSignalVerdict::ProbablyDefect, [TicketSignal::NoRecentUnlocks]],
    'reporter earned it later on unchanged logic' => [5, 'same-logic', null, []],
    'reporter earned it later on newer logic' => [5, 'newer-logic', TicketSignalVerdict::ProbablyDefect, [TicketSignal::EarnedAfterLogicChange]],
    'reporter earned it later on unknown logic' => [5, 'unknown-logic', null, []],
    'reporter earned it later on older logic' => [5, 'older-logic', null, []],
    'reporter unlocked casual before the report and hardcore after' => [5, 'hardcore-later', null, []],
    '50 unlocks with a later unlock on unknown logic' => [50, 'unknown-logic', TicketSignalVerdict::ProbablyNotDefect, [TicketSignal::ManyRecentUnlocks, TicketSignal::EarnedAfterReport]],
    '50 unlocks with a later unlock on unchanged logic' => [50, 'same-logic', TicketSignalVerdict::ProbablyNotDefect, [TicketSignal::ManyRecentUnlocks, TicketSignal::EarnedWithSameLogic]],
    'zero unlocks with a later unlock on unchanged logic' => [0, 'same-logic', null, []],
    'zero unlocks with a later unlock on unknown logic' => [0, 'unknown-logic', null, []],
]);

it('given a logic change 20 days before the report, counts unlocks only from that point and exposes the evidence', function () {
    // ARRANGE
    $ticket = createSignalsTicket(50, 'unknown-logic');
    seedSignalsUnlocks(Achievement::findOrFail($ticket->ticketable_id), 30, Carbon::parse('2026-05-01 12:00:00'));

    // ACT
    $signals = buildSignals($ticket)[$ticket->id];

    // ASSERT
    expect($signals->unlockCount)->toEqual(50);
    expect($signals->windowStartedAt->toDateTimeString())->toEqual('2026-05-12 12:00:00');
    expect($signals->hasRecentLogicChange)->toBeTrue();
    expect($signals->reporterEarnedAt->toDateTimeString())->toEqual('2026-06-08 12:00:00');
});

it('given no logic change within the 90 days before the report, the window falls back to the 90-day cap', function () {
    // ARRANGE
    $achievement = Achievement::factory()->promoted()->create(['game_id' => createSignalsGame()->id]);
    $reportedAt = Carbon::parse('2026-06-01 12:00:00');
    Trigger::factory()->create([
        'triggerable_type' => TriggerableType::Achievement,
        'triggerable_id' => $achievement->id,
        'created_at' => $reportedAt->copy()->subDays(200),
    ]);
    $ticket = Ticket::factory()->forAchievement($achievement)->create([
        'reporter_id' => User::factory()->create()->id,
        'created_at' => $reportedAt,
    ]);
    seedSignalsUnlocks($achievement, 1, $reportedAt->copy()->subDays(91));
    seedSignalsPlayers($achievement->game_id, 5, $reportedAt->copy()->subDays(10));

    // ACT
    $signals = buildSignals($ticket)[$ticket->id];

    // ASSERT
    expect($signals->kinds)->toEqual([TicketSignal::NoRecentUnlocks]);
    expect($signals->windowStartedAt->toDateTimeString())->toEqual('2026-03-03 12:00:00');
    expect($signals->hasRecentLogicChange)->toBeFalse();
});

it('given fewer than 5 other players on the game in the window, zero unlocks does not receive a verdict', function () {
    // ARRANGE
    $ticket = createSignalsTicket(0, otherPlayers: 4);
    PlayerSession::factory()->create([
        'user_id' => $ticket->reporter_id,
        'game_id' => Achievement::findOrFail($ticket->ticketable_id)->game_id,
        'rich_presence_updated_at' => Carbon::parse('2026-05-29 12:00:00'),
    ]);

    // ACT
    $result = buildSignals($ticket);

    // ASSERT
    expect($result)->toEqual([$ticket->id => null]);
});

it('given a ticket with no linked logic version, measures the reporter unlock against the version live at report time', function () {
    // ARRANGE
    $ticket = createSignalsTicket(50, 'same-logic', isTicketLinkedToTrigger: false);

    // ACT
    $signals = buildSignals($ticket)[$ticket->id];

    // ASSERT
    expect($signals->kinds)->toEqual([TicketSignal::ManyRecentUnlocks, TicketSignal::EarnedWithSameLogic]);
});

it('given reporter unlocks that are manual or predate the report, ignores them', function () {
    // ARRANGE
    $inPlay = createSignalsTicket(50, 'same-logic');
    $manual = createSignalsTicket(50, 'manual');
    $beforeReport = createSignalsTicket(50, 'before-report');

    // ACT
    $result = buildSignals($inPlay, $manual, $beforeReport);

    // ASSERT
    expect($result)->toHaveCount(3);
    expect($result[$inPlay->id]->kinds)->toEqual([TicketSignal::ManyRecentUnlocks, TicketSignal::EarnedWithSameLogic]);
    expect($result[$manual->id]->kinds)->toEqual([TicketSignal::ManyRecentUnlocks]);
    expect($result[$beforeReport->id]->kinds)->toEqual([TicketSignal::ManyRecentUnlocks]);
});
