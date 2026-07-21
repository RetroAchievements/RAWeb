<?php

declare(strict_types=1);

use App\Community\Enums\AwardType;
use App\Community\Enums\RankType;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\PlayerGlobalRanking;
use App\Models\PlayerGlobalRankingTotal;
use App\Models\User;
use App\Platform\Actions\UpdatePlayerGlobalRankingsAction;
use App\Platform\Enums\GlobalRankingMode;
use App\Platform\Enums\GlobalRankingWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-07-19 12:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
});

function findPlayerGlobalRanking(
    User $user,
    GlobalRankingMode $mode,
    GlobalRankingWindow $window = GlobalRankingWindow::AllTime,
): PlayerGlobalRanking {
    return PlayerGlobalRanking::query()
        ->where('user_id', $user->id)
        ->where('mode', $mode)
        ->where('window', $window)
        ->firstOrFail();
}

it('builds all-time ranks and persisted totals', function () {
    // ARRANGE
    $first = User::factory()->create([
        'points_hardcore' => 500,
        'points' => 600,
        'points_weighted' => 1_300,
        'achievements_unlocked_hardcore' => 10,
        'achievements_unlocked' => 12,
    ]);
    $tied = User::factory()->create([
        'points_hardcore' => 500,
        'points' => 400,
        'points_weighted' => 1_250,
        'achievements_unlocked_hardcore' => 8,
        'achievements_unlocked' => 8,
    ]);
    $weightedOnly = User::factory()->create([
        'points_hardcore' => 100,
        'points' => 100,
        'points_weighted' => 1_250,
    ]);
    User::factory()->untracked()->create(['points_hardcore' => 10_000, 'points' => 10_000]);
    User::factory()->create(['points_hardcore' => 10_000, 'points' => 10_000, 'deleted_at' => now()]);

    // ACT
    app(UpdatePlayerGlobalRankingsAction::class)->execute(GlobalRankingWindow::AllTime);

    // ASSERT
    $firstHardcore = findPlayerGlobalRanking($first, GlobalRankingMode::Hardcore);
    $tiedHardcore = findPlayerGlobalRanking($tied, GlobalRankingMode::Hardcore);
    $weightedOnlyHardcore = findPlayerGlobalRanking($weightedOnly, GlobalRankingMode::Hardcore);

    expect($firstHardcore->rank_number)->toBe(1)
        ->and($tiedHardcore->rank_number)->toBe(1)
        ->and($weightedOnlyHardcore->rank_number)->toBeNull()
        ->and($firstHardcore->weighted_rank_number)->toBe(1)
        ->and($tiedHardcore->weighted_rank_number)->toBe(2)
        ->and($weightedOnlyHardcore->weighted_rank_number)->toBe(2)
        ->and(findPlayerGlobalRanking($first, GlobalRankingMode::Casual)->achievements_unlocked)->toBe(2)
        ->and(findPlayerGlobalRanking($tied, GlobalRankingMode::Casual)->achievements_unlocked)->toBe(0)
        ->and(PlayerGlobalRankingTotal::forRankType(RankType::Hardcore))->toBe(2)
        ->and(PlayerGlobalRankingTotal::forRankType(RankType::Casual))->toBe(2)
        ->and(PlayerGlobalRankingTotal::forRankType(RankType::TruePoints))->toBe(3);
});

it('materializes sub-threshold players with null rank numbers', function () {
    // ARRANGE
    $subThreshold = User::factory()->create(['points_hardcore' => 100, 'points' => 50]);

    // ACT
    app(UpdatePlayerGlobalRankingsAction::class)->execute(GlobalRankingWindow::AllTime);

    // ASSERT
    $hardcore = findPlayerGlobalRanking($subThreshold, GlobalRankingMode::Hardcore);
    $casual = findPlayerGlobalRanking($subThreshold, GlobalRankingMode::Casual);

    expect($hardcore->points)->toBe(100)
        ->and($hardcore->rank_number)->toBeNull()
        ->and($hardcore->weighted_rank_number)->toBeNull()
        ->and($casual->rank_number)->toBeNull()
        ->and(PlayerGlobalRankingTotal::forRankType(RankType::Hardcore))->toBe(0);
});

it('tolerates inverted unlock counters without aborting the rebuild', function () {
    // ARRANGE
    $inverted = User::factory()->create([
        'points' => 300,
        'points_hardcore' => 300,
        'achievements_unlocked' => 5,
        'achievements_unlocked_hardcore' => 10,
    ]);

    // ACT
    app(UpdatePlayerGlobalRankingsAction::class)->execute(GlobalRankingWindow::AllTime);

    // ASSERT
    expect(findPlayerGlobalRanking($inverted, GlobalRankingMode::Casual)->achievements_unlocked)->toBe(0);
});

it('builds current daily rows and replaces stale rows', function () {
    // ARRANGE
    $today = User::factory()->create(['points_hardcore' => 10]);
    $stale = User::factory()->create();
    $achievement = Achievement::factory()->create(['points' => 25, 'points_weighted' => 50]);

    PlayerAchievement::factory()->create([
        'user_id' => $today->id,
        'achievement_id' => $achievement->id,
        'unlocked_at' => now('UTC')->subHour(),
        'unlocked_hardcore_at' => now('UTC')->subHour(),
    ]);
    PlayerAchievement::factory()->create([
        'user_id' => $stale->id,
        'achievement_id' => Achievement::factory()->create(['points' => 50])->id,
        'unlocked_at' => now('UTC')->subDay(),
    ]);
    PlayerBadge::factory()->create([
        'user_id' => $today->id,
        'award_type' => AwardType::Mastery,
        'award_tier' => 1,
        'awarded_at' => now('UTC')->subHour(),
    ]);

    app(UpdatePlayerGlobalRankingsAction::class)->execute(GlobalRankingWindow::Daily);

    $dailyHardcore = findPlayerGlobalRanking($today, GlobalRankingMode::Hardcore, GlobalRankingWindow::Daily);
    $dailyCasual = findPlayerGlobalRanking($today, GlobalRankingMode::Casual, GlobalRankingWindow::Daily);

    expect($dailyHardcore->points)->toBe(25)
        ->and($dailyHardcore->points_weighted)->toBe(50)
        ->and($dailyHardcore->awards_count)->toBe(1)
        ->and($dailyCasual->awards_count)->toBe(1)
        ->and(PlayerGlobalRanking::query()
            ->where('user_id', $stale->id)
            ->where('window', GlobalRankingWindow::Daily)
            ->exists())->toBeFalse();

    // ACT
    $today->unranked_at = now();
    $today->save();
    app(UpdatePlayerGlobalRankingsAction::class)->execute(GlobalRankingWindow::Daily);

    // ASSERT
    expect(PlayerGlobalRanking::query()
        ->where('user_id', $today->id)
        ->where('window', GlobalRankingWindow::Daily)
        ->exists())->toBeFalse();
});

it('uses Sunday as the weekly boundary', function () {
    // ARRANGE
    Carbon::setTestNow(Carbon::parse('2026-07-19 00:30:00', 'UTC'));

    $user = User::factory()->create();
    $sundayAchievement = Achievement::factory()->create(['points' => 10]);
    $saturdayAchievement = Achievement::factory()->create(['points' => 50]);

    PlayerAchievement::factory()->create([
        'user_id' => $user->id,
        'achievement_id' => $sundayAchievement->id,
        'unlocked_at' => Carbon::parse('2026-07-19 00:15:00', 'UTC'),
        'unlocked_hardcore_at' => Carbon::parse('2026-07-19 00:15:00', 'UTC'),
    ]);
    PlayerAchievement::factory()->create([
        'user_id' => $user->id,
        'achievement_id' => $saturdayAchievement->id,
        'unlocked_at' => Carbon::parse('2026-07-18 23:59:00', 'UTC'),
        'unlocked_hardcore_at' => Carbon::parse('2026-07-18 23:59:00', 'UTC'),
    ]);

    // ACT
    app(UpdatePlayerGlobalRankingsAction::class)->execute(GlobalRankingWindow::Weekly);

    // ASSERT
    expect(findPlayerGlobalRanking($user, GlobalRankingMode::Hardcore, GlobalRankingWindow::Weekly)->points)->toBe(10);
});
