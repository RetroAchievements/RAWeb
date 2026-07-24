<?php

declare(strict_types=1);

use App\Community\Actions\CreateGameClaimAction;
use App\Community\Actions\DropGameClaimAction;
use App\Community\Actions\UpdateGameClaimAction;
use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimStatus;
use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\RevalidateMediaContributionBadgeEligibilityAction;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Events\AchievementMoved;
use App\Platform\Events\SiteBadgeAwarded;
use App\Platform\Listeners\RevalidateMediaContributionBadgeEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function createApprovedScreenshotsForRevalidateActionTest(
    int $count,
    Game $game,
    User $submitter,
    User $reviewer,
): void {
    for ($i = 0; $i < $count; $i++) {
        GameScreenshot::factory()
            ->for($game)
            ->ingame()
            ->create([
                'captured_by_user_id' => $submitter->id,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'status' => GameScreenshotStatus::Approved,
            ]);
    }
}

it('awards a tier 0 badge when eligible screenshots cross the first threshold', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(10, $game, $submitter, $reviewer);

    Event::fake([SiteBadgeAwarded::class]);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge)->not->toBeNull();
    expect($badge->award_type)->toEqual(AwardType::MediaContribution);
    expect($badge->award_key)->toEqual(0);
    expect($badge->award_tier)->toEqual(0);

    Event::assertDispatched(
        SiteBadgeAwarded::class,
        fn (SiteBadgeAwarded $event) => $event->playerBadge->is($badge),
    );
});

it('keeps exactly one mediaContrib row when upgrading across multiple thresholds', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(100, $game, $submitter, $reviewer);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge->award_key)->toEqual(2);
    expect($badge->award_tier)->toEqual(2);
    expect(
        PlayerBadge::where('user_id', $submitter->id)
            ->where('award_type', AwardType::MediaContribution)
            ->count()
    )->toEqual(1);
});

it('excludes self-approved screenshots from the eligible count', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(10, $game, $submitter, $submitter);

    Event::fake([SiteBadgeAwarded::class]);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge)->toBeNull();
    expect(
        PlayerBadge::where('user_id', $submitter->id)
            ->where('award_type', AwardType::MediaContribution)
            ->count()
    )->toEqual(0);

    Event::assertNotDispatched(SiteBadgeAwarded::class);
});

it('excludes screenshots for games with active claims and counts those with dropped claims', function () {
    // ARRANGE
    $activeClaimGame = Game::factory()->create(['system_id' => System::factory()]);
    $droppedClaimGame = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    AchievementSetClaim::factory()->create([
        'game_id' => $activeClaimGame->id,
        'user_id' => $submitter->id,
    ]);
    AchievementSetClaim::factory()->create([
        'game_id' => $droppedClaimGame->id,
        'user_id' => $submitter->id,
        'status' => ClaimStatus::Dropped,
    ]);

    // ... the active claim game contributes a screenshot that should be ignored ...
    createApprovedScreenshotsForRevalidateActionTest(1, $activeClaimGame, $submitter, $reviewer);

    // ... the dropped claim game contributes screenshots that should still count ...
    createApprovedScreenshotsForRevalidateActionTest(10, $droppedClaimGame, $submitter, $reviewer);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge)->not->toBeNull();
    expect($badge->award_key)->toEqual(0);
});

it('removes existing badges and returns null when no screenshots are eligible', function () {
    // ARRANGE
    $submitter = User::factory()->create();

    PlayerBadge::factory()->create([
        'user_id' => $submitter->id,
        'award_type' => AwardType::MediaContribution,
        'award_key' => 1,
        'award_tier' => 1,
    ]);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge)->toBeNull();
    expect(
        PlayerBadge::where('user_id', $submitter->id)
            ->where('award_type', AwardType::MediaContribution)
            ->count()
    )->toEqual(0);
});

it('reuses an existing badge at the expected tier without dispatching an event', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(10, $game, $submitter, $reviewer);

    $existing = PlayerBadge::factory()->create([
        'user_id' => $submitter->id,
        'award_type' => AwardType::MediaContribution,
        'award_key' => 0,
        'award_tier' => 0,
        'order_column' => 7,
    ]);

    Event::fake([SiteBadgeAwarded::class]);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge->id)->toEqual($existing->id);
    expect(
        PlayerBadge::where('user_id', $submitter->id)
            ->where('award_type', AwardType::MediaContribution)
            ->count()
    )->toEqual(1);

    Event::assertNotDispatched(SiteBadgeAwarded::class);
});

it('downgrades the existing row in place when eligibility drops to a lower tier', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(10, $game, $submitter, $reviewer);

    $existing = PlayerBadge::factory()->create([
        'user_id' => $submitter->id,
        'award_type' => AwardType::MediaContribution,
        'award_key' => 1,
        'award_tier' => 1,
    ]);

    Event::fake([SiteBadgeAwarded::class]);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge->id)->toEqual($existing->id);
    expect($badge->award_key)->toEqual(0);
    expect($badge->award_tier)->toEqual(0);
    expect(
        PlayerBadge::where('user_id', $submitter->id)
            ->where('award_type', AwardType::MediaContribution)
            ->count()
    )->toEqual(1);

    Event::assertNotDispatched(SiteBadgeAwarded::class);
});

it('clears display_award_tier on downgrade when the displayed tier is no longer earned', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(10, $game, $submitter, $reviewer);

    PlayerBadge::factory()->create([
        'user_id' => $submitter->id,
        'award_type' => AwardType::MediaContribution,
        'award_key' => 2,
        'award_tier' => 2,
        'display_award_tier' => 2,
    ]);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge->award_tier)->toEqual(0);
    expect($badge->display_award_tier)->toBeNull();
});

it('preserves display_award_tier on downgrade when the displayed tier is still earned', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(30, $game, $submitter, $reviewer);

    PlayerBadge::factory()->create([
        'user_id' => $submitter->id,
        'award_type' => AwardType::MediaContribution,
        'award_key' => 2,
        'award_tier' => 2,
        'display_award_tier' => 0,
    ]);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge->award_tier)->toEqual(1);
    expect($badge->display_award_tier)->toEqual(0);
});

it('preserves the order_column from the previous highest tier when upgrading', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(30, $game, $submitter, $reviewer);

    PlayerBadge::factory()->create([
        'user_id' => $submitter->id,
        'award_type' => AwardType::MediaContribution,
        'award_key' => 0,
        'award_tier' => 0,
        'order_column' => 7,
    ]);

    Event::fake([SiteBadgeAwarded::class]);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge->award_key)->toEqual(1);
    expect($badge->order_column)->toEqual(7);

    Event::assertDispatched(
        SiteBadgeAwarded::class,
        fn (SiteBadgeAwarded $event) => $event->playerBadge->is($badge),
    );
});

it('awards upgraded tiers at the time they are earned', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(30, $game, $submitter, $reviewer);

    $originalAwardedAt = Carbon::parse('2025-01-15 12:00:00');
    $upgradedAt = Carbon::parse('2025-02-15 12:00:00');

    PlayerBadge::factory()->create([
        'user_id' => $submitter->id,
        'award_type' => AwardType::MediaContribution,
        'award_key' => 0,
        'award_tier' => 0,
        'awarded_at' => $originalAwardedAt,
        'order_column' => 7,
    ]);

    Carbon::setTestNow($upgradedAt);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect($badge->award_key)->toEqual(1);
    expect($badge->order_column)->toEqual(7);
    expect($badge->awarded_at->toDateTimeString())->toEqual($upgradedAt->toDateTimeString());
    expect($badge->awarded_at->toDateTimeString())->not->toEqual($originalAwardedAt->toDateTimeString());

    Carbon::setTestNow();
});

it('revalidates the badge when a game claim is created', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(10, $game, $submitter, $reviewer);

    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);
    expect($badge?->award_key)->toEqual(0);

    // ACT
    (new CreateGameClaimAction())->execute($game, $submitter);

    // ASSERT
    expect(
        PlayerBadge::where('user_id', $submitter->id)
            ->where('award_type', AwardType::MediaContribution)
            ->count()
    )->toEqual(0);
});

it('revalidates the badge when a game claim is dropped', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $claim = AchievementSetClaim::factory()->create([
        'game_id' => $game->id,
        'user_id' => $submitter->id,
    ]);

    createApprovedScreenshotsForRevalidateActionTest(10, $game, $submitter, $reviewer);

    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);
    expect($badge)->toBeNull();

    // ACT
    (new DropGameClaimAction())->execute($claim, $submitter);

    // ASSERT
    expect(
        PlayerBadge::where('user_id', $submitter->id)
            ->where('award_type', AwardType::MediaContribution)
            ->sole()
            ->award_key
    )->toEqual(0);
});

it('revalidates the badge when a game claim status is updated to dropped', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $claim = AchievementSetClaim::factory()->create([
        'game_id' => $game->id,
        'user_id' => $submitter->id,
    ]);

    createApprovedScreenshotsForRevalidateActionTest(10, $game, $submitter, $reviewer);

    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);
    expect($badge)->toBeNull();

    // ACT
    $this->actingAs($reviewer);
    (new UpdateGameClaimAction())->execute($claim, ['status' => ClaimStatus::Dropped->value]);

    // ASSERT
    expect(
        PlayerBadge::where('user_id', $submitter->id)
            ->where('award_type', AwardType::MediaContribution)
            ->sole()
            ->award_key
    )->toEqual(0);
});

it('revalidates the badge when the submitter authors an achievement for the game', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(10, $game, $submitter, $reviewer);

    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);
    expect($badge?->award_key)->toEqual(0); // they have a badge from their screenshots

    // ACT
    Achievement::factory()->for($game)->create(['user_id' => $submitter->id]);

    // ASSERT
    expect(
        PlayerBadge::where('user_id', $submitter->id)
            ->where('award_type', AwardType::MediaContribution)
            ->count()
    )->toEqual(0);
});

it('revalidates the original game when an achievement moves away from submitted screenshots', function () {
    // ARRANGE
    $originalGame = Game::factory()->create(['system_id' => System::factory()]);
    $newGame = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $achievement = Achievement::factory()->for($originalGame)->create(['user_id' => $submitter->id]);

    createApprovedScreenshotsForRevalidateActionTest(10, $originalGame, $submitter, $reviewer);

    $achievement->game_id = $newGame->id;
    $achievement->saveQuietly();

    // ACT
    (new RevalidateMediaContributionBadgeEligibility())->handle(
        new AchievementMoved($achievement, $originalGame),
    );

    // ASSERT
    expect(
        PlayerBadge::where('user_id', $submitter->id)
            ->where('award_type', AwardType::MediaContribution)
            ->sole()
            ->award_key
    )->toEqual(0);
});

it('retains media contribution credit when a different capturer replaces the screenshot', function () {
    // ARRANGE
    $otherGame = Game::factory()->create(['system_id' => System::factory()]);
    $sharedGame = Game::factory()->create(['system_id' => System::factory()]);
    $originalCapturer = User::factory()->create();
    $replacementCapturer = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(9, $otherGame, $originalCapturer, $reviewer);

    $originalTitle = GameScreenshot::factory()
        ->for($sharedGame)
        ->title()
        ->create([
            'captured_by_user_id' => $originalCapturer->id,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'status' => GameScreenshotStatus::Approved,
        ]);

    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($originalCapturer);
    expect($badge?->award_key)->toEqual(0);

    $originalTitle->update(['status' => GameScreenshotStatus::Replaced]);

    GameScreenshot::factory()
        ->for($sharedGame)
        ->title()
        ->create([
            'captured_by_user_id' => $replacementCapturer->id,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'status' => GameScreenshotStatus::Approved,
        ]);

    // ACT
    $revalidated = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($originalCapturer);

    // ASSERT
    expect(GameScreenshot::query()->eligibleForMediaContributionBy($originalCapturer)->count())->toEqual(10);
    expect(GameScreenshot::query()->eligibleForMediaContributionBy($replacementCapturer)->count())->toEqual(1);
    expect($revalidated?->award_key)->toEqual(0);
    expect(
        PlayerBadge::where('user_id', $originalCapturer->id)
            ->where('award_type', AwardType::MediaContribution)
            ->sole()
            ->award_key
    )->toEqual(0);
});

it('does not stack credit when the same capturer replaces their own screenshot', function () {
    // ARRANGE
    $otherGame = Game::factory()->create(['system_id' => System::factory()]);
    $titleGame = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(9, $otherGame, $submitter, $reviewer);

    $originalTitle = GameScreenshot::factory()
        ->for($titleGame)
        ->title()
        ->create([
            'captured_by_user_id' => $submitter->id,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'status' => GameScreenshotStatus::Approved,
        ]);

    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);
    expect($badge?->award_key)->toEqual(0);
    expect(GameScreenshot::query()->eligibleForMediaContributionBy($submitter)->count())->toEqual(10);

    $originalTitle->update(['status' => GameScreenshotStatus::Replaced]);

    GameScreenshot::factory()
        ->for($titleGame)
        ->title()
        ->create([
            'captured_by_user_id' => $submitter->id,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'status' => GameScreenshotStatus::Approved,
        ]);

    // ACT
    $revalidated = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect(GameScreenshot::query()->eligibleForMediaContributionBy($submitter)->count())->toEqual(10);
    expect($revalidated?->award_key)->toEqual(0);
});

it('excludes non-creditable screenshot statuses from the eligible count', function (
    GameScreenshotStatus $status,
) {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    createApprovedScreenshotsForRevalidateActionTest(9, $game, $submitter, $reviewer);

    GameScreenshot::factory()
        ->for($game)
        ->ingame()
        ->create([
            'captured_by_user_id' => $submitter->id,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'status' => $status,
        ]);

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect(GameScreenshot::query()->eligibleForMediaContributionBy($submitter)->count())->toEqual(9);
    expect($badge)->toBeNull();
})->with([
    'rejected' => [GameScreenshotStatus::Rejected],
    'pending' => [GameScreenshotStatus::Pending],
]);

it('excludes replaced screenshots when the capturer is disqualified on the game', function (
    string $disqualifier,
) {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    if ($disqualifier === 'claim') {
        AchievementSetClaim::factory()->create([
            'game_id' => $game->id,
            'user_id' => $submitter->id,
        ]);
    }

    createApprovedScreenshotsForRevalidateActionTest(10, $game, $submitter, $reviewer);

    GameScreenshot::query()
        ->where('captured_by_user_id', $submitter->id)
        ->where('game_id', $game->id)
        ->update(['status' => GameScreenshotStatus::Replaced]);

    if ($disqualifier === 'achievement') {
        Achievement::withoutEvents(fn () => Achievement::factory()->for($game)->create([
            'user_id' => $submitter->id,
        ]));
    }

    // ACT
    $badge = (new RevalidateMediaContributionBadgeEligibilityAction())->execute($submitter);

    // ASSERT
    expect(GameScreenshot::query()->eligibleForMediaContributionBy($submitter)->count())->toEqual(0);
    expect($badge)->toBeNull();
})->with([
    'authored an achievement' => ['achievement'],
    'holds an active claim' => ['claim'],
]);
