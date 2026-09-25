<?php

declare(strict_types=1);

use App\Models\EventAchievement;
use App\Models\PlayerAchievement;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\BackfillEventAchievementUnlocksAction;
use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $sourceGame = $this->seedGame(achievements: 1, withHash: false);
    $eventSystem = System::find(System::Events) ?? System::factory()->create(['id' => System::Events]);
    $eventGame = $this->seedGame($eventSystem, achievements: 1, withHash: false);

    $this->sourceAchievement = $sourceGame->achievements()->firstOrFail();
    $this->eventAchievement = $eventGame->achievements()->firstOrFail();
    $this->eventAchievementLink = EventAchievement::withoutEvents(
        fn (): EventAchievement => EventAchievement::create([
            'achievement_id' => $this->eventAchievement->id,
            'source_achievement_id' => $this->sourceAchievement->id,
        ]),
    );
    $this->eventGame = $eventGame;
});

it('given a hardcore winner, credits the event achievement with the source timestamp', function () {
    // ARRANGE
    $player = User::factory()->create();
    $timestamp = Carbon::parse('2026-01-02 03:04:05');
    PlayerAchievement::query()->insert([
        'user_id' => $player->id,
        'achievement_id' => $this->sourceAchievement->id,
        'unlocked_at' => $timestamp->toDateTimeString(),
        'unlocked_hardcore_at' => $timestamp->toDateTimeString(),
    ]);
    Queue::fake();
    Event::fake();

    // ACT
    (new BackfillEventAchievementUnlocksAction())->execute(
        $this->eventAchievementLink->id,
    );

    // ASSERT
    $eventUnlock = PlayerAchievement::query()
        ->where('user_id', $player->id)
        ->where('achievement_id', $this->eventAchievement->id)
        ->firstOrFail();

    expect($eventUnlock->unlocked_at->equalTo($timestamp))->toBeTrue()
        ->and($eventUnlock->unlocked_hardcore_at->equalTo($timestamp))->toBeTrue();
});

it('given a softcore-only or unranked winner, does not credit the event achievement', function () {
    // ARRANGE
    $softcorePlayer = User::factory()->create();
    $unrankedPlayer = User::factory()->create(['unranked_at' => now()]);
    PlayerAchievement::query()->insert([
        [
            'user_id' => $softcorePlayer->id,
            'achievement_id' => $this->sourceAchievement->id,
            'unlocked_at' => now()->toDateTimeString(),
            'unlocked_hardcore_at' => null,
        ],
        [
            'user_id' => $unrankedPlayer->id,
            'achievement_id' => $this->sourceAchievement->id,
            'unlocked_at' => now()->toDateTimeString(),
            'unlocked_hardcore_at' => now()->toDateTimeString(),
        ],
    ]);
    Queue::fake();
    Event::fake();

    // ACT
    (new BackfillEventAchievementUnlocksAction())->execute(
        $this->eventAchievementLink->id,
    );

    // ASSERT
    expect(PlayerAchievement::query()->where('achievement_id', $this->eventAchievement->id)->exists())->toBeFalse();
});

it('given the event achievement has active bounds, credits only winners inside that active bounds window', function () {
    // ARRANGE
    $activeFrom = Carbon::parse('2026-01-02');
    $activeUntil = Carbon::parse('2026-01-04');
    EventAchievement::query()->whereKey($this->eventAchievementLink->id)->update([
        'active_from' => $activeFrom,
        'active_until' => $activeUntil,
    ]);

    $timestamps = [
        Carbon::parse('2026-01-01 23:59:59'),
        Carbon::parse('2026-01-02 00:00:00'),
        Carbon::parse('2026-01-03 12:00:00'),
        Carbon::parse('2026-01-04 00:00:00'),
    ];
    $players = User::factory()->count(count($timestamps))->create();
    PlayerAchievement::query()->insert($players->zip($timestamps)->map(
        fn (Collection $winner): array => [
            'user_id' => $winner->get(0)->id,
            'achievement_id' => $this->sourceAchievement->id,
            'unlocked_at' => $winner->get(1)->toDateTimeString(),
            'unlocked_hardcore_at' => $winner->get(1)->toDateTimeString(),
        ],
    )->all());
    app()->instance(UpdatePlayerGameMetricsAction::class, Mockery::spy(UpdatePlayerGameMetricsAction::class));
    Queue::fake();
    Event::fake();

    // ACT
    (new BackfillEventAchievementUnlocksAction())->execute(
        $this->eventAchievementLink->id,
    );

    // ASSERT
    expect(PlayerAchievement::query()->where('achievement_id', $this->eventAchievement->id)->count())->toBe(2);
});

it('given an existing event row for the player, leaves it untouched', function () {
    // ARRANGE
    $player = User::factory()->create();
    $timestamp = Carbon::parse('2026-02-03 04:05:06');
    $alreadyCredited = Carbon::parse('2026-02-01 01:01:01');
    PlayerAchievement::query()->insert([
        [
            'user_id' => $player->id,
            'achievement_id' => $this->sourceAchievement->id,
            'unlocked_at' => $timestamp->toDateTimeString(),
            'unlocked_hardcore_at' => $timestamp->toDateTimeString(),
        ],
        [
            'user_id' => $player->id,
            'achievement_id' => $this->eventAchievement->id,
            'unlocked_at' => $alreadyCredited->toDateTimeString(),
            'unlocked_hardcore_at' => $alreadyCredited->toDateTimeString(),
        ],
    ]);
    Queue::fake();
    Event::fake();

    // ACT
    (new BackfillEventAchievementUnlocksAction())->execute(
        $this->eventAchievementLink->id,
    );

    // ASSERT
    $eventUnlocks = PlayerAchievement::query()
        ->where('user_id', $player->id)
        ->where('achievement_id', $this->eventAchievement->id)
        ->get();

    expect($eventUnlocks)->toHaveCount(1)
        ->and($eventUnlocks->first()->unlocked_hardcore_at->equalTo($alreadyCredited))->toBeTrue();
});
