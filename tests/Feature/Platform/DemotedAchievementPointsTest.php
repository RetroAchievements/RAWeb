<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;

use function Pest\Laravel\actingAs;

uses(LazilyRefreshDatabase::class);
uses(TestsPlayerAchievements::class);

function createPromotedAchievement(int $gameId, int $points): Achievement
{
    $developer = User::factory()->create();
    $achievement = Achievement::factory()->create([
        'game_id' => $gameId,
        'user_id' => $developer->id,
        'points' => $points,
        'is_promoted' => false,
    ]);

    actingAs($developer);
    $achievement->update(['is_promoted' => true]);

    return $achievement;
}

it('given an achievement demoted a week or more after promotion, retains its points for players', function () {
    // ARRANGE
    Carbon::setTestNow('2026-02-01 12:00:00');
    $game = $this->seedGame(withHash: false);
    $achievement = createPromotedAchievement($game->id, 10);
    $hardcorePlayer = User::factory()->create();
    $casualPlayer = User::factory()->create();
    $this->addHardcoreUnlock($hardcorePlayer, $achievement);
    $this->addCasualUnlock($casualPlayer, $achievement);

    // ACT
    Carbon::setTestNow('2026-02-08 12:00:00'); // !! 7 days
    $achievement->update(['is_promoted' => false]);

    // ASSERT
    expect($achievement->refresh()->should_keep_demoted_points)->toBeTrue();
    expect($hardcorePlayer->refresh()->points_hardcore)->toEqual(10);
    expect($hardcorePlayer->points)->toEqual(0);
    expect($casualPlayer->refresh()->points_hardcore)->toEqual(0);
    expect($casualPlayer->points)->toEqual(10);
});

it('given an achievement demoted less than a week after promotion, does not retain its points', function () {
    // ARRANGE
    Carbon::setTestNow('2026-02-01 12:00:00');
    $game = $this->seedGame(withHash: false);
    $achievement = createPromotedAchievement($game->id, 10);
    $player = User::factory()->create();
    $this->addHardcoreUnlock($player, $achievement);

    // ACT
    Carbon::setTestNow('2026-02-08 11:59:59'); // !! 1 second short of 7 days
    $achievement->update(['is_promoted' => false]);

    // ASSERT
    expect($achievement->refresh()->should_keep_demoted_points)->toBeFalse();
    expect($player->refresh()->points_hardcore)->toEqual(0);
});

it('given a demoted achievement rescored to zero and then rescored again, removes the points and then restores them', function () {
    // ARRANGE
    Carbon::setTestNow('2026-02-01 12:00:00');
    $game = $this->seedGame(withHash: false);
    $achievement = createPromotedAchievement($game->id, 10);
    $player = User::factory()->create();
    $this->addHardcoreUnlock($player, $achievement);

    Carbon::setTestNow('2026-05-01 12:00:00');
    $achievement->update(['is_promoted' => false]);
    expect($player->refresh()->points_hardcore)->toEqual(10);

    // ACT
    $achievement->update(['points' => 0]);
    $pointsAfterRescoreToZero = $player->refresh()->points_hardcore;

    $achievement->update(['points' => 5]);
    $pointsAfterRescoreToFive = $player->refresh()->points_hardcore;

    // ASSERT
    expect($pointsAfterRescoreToZero)->toEqual(0);
    expect($pointsAfterRescoreToFive)->toEqual(5);
});

it('given a demoted achievement that is deleted and then restored, removes the points and then restores them', function () {
    // ARRANGE
    Carbon::setTestNow('2026-02-01 12:00:00');
    $game = $this->seedGame(withHash: false);
    $achievement = createPromotedAchievement($game->id, 10);
    $player = User::factory()->create();
    $this->addHardcoreUnlock($player, $achievement);

    Carbon::setTestNow('2026-05-01 12:00:00');
    $achievement->update(['is_promoted' => false]);
    expect($player->refresh()->points_hardcore)->toEqual(10);

    // ACT
    $achievement->delete();
    $pointsAfterDelete = $player->refresh()->points_hardcore;

    $achievement->restore();
    $pointsAfterRestore = $player->refresh()->points_hardcore;

    // ASSERT
    expect($pointsAfterDelete)->toEqual(0);
    expect($pointsAfterRestore)->toEqual(10);
});
