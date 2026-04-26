<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\AchievementGroup;
use App\Models\AchievementSetAchievement;
use App\Models\Game;
use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('preserves achievement set metadata for soft-deleted achievements during metrics sync', function () {
    // ARRANGE
    $game = Game::factory()->create();
    $deletedAchievement = Achievement::factory()->promoted()->for($game)->create([
        'order_column' => 10,
        'points' => 5,
    ]);
    $activeAchievement = Achievement::factory()->promoted()->for($game)->create([
        'order_column' => 20,
        'points' => 3,
    ]);

    (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

    $achievementSet = $game->achievementSets()->firstOrFail();
    $group = AchievementGroup::factory()->create([
        'achievement_set_id' => $achievementSet->id,
    ]);

    AchievementSetAchievement::query()
        ->where('achievement_set_id', $achievementSet->id)
        ->where('achievement_id', $deletedAchievement->id)
        ->update(['achievement_group_id' => $group->id]);

    $deletedAchievement->delete();

    // ACT
    (new UpdateGameMetricsAction())->execute($game->refresh());

    // ASSERT
    $this->assertDatabaseHas('achievement_set_achievements', [
        'achievement_set_id' => $achievementSet->id,
        'achievement_id' => $deletedAchievement->id,
        'achievement_group_id' => $group->id,
        'order_column' => 10,
    ]);

    $visibleAchievementIds = $achievementSet->achievements()->pluck('achievements.id');

    expect($achievementSet->refresh()->achievements_published)->toBe(1)
        ->and($achievementSet->points_total)->toBe(3)
        ->and($visibleAchievementIds)->toContain($activeAchievement->id)
        ->and($visibleAchievementIds)->not->toContain($deletedAchievement->id);
});

it('keeps restored achievements attached to their set with existing metadata', function () {
    // ARRANGE
    $game = Game::factory()->create();
    $restoredAchievement = Achievement::factory()->promoted()->for($game)->create([
        'order_column' => 10,
        'points' => 5,
    ]);
    $activeAchievement = Achievement::factory()->promoted()->for($game)->create([
        'order_column' => 20,
        'points' => 3,
    ]);

    (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

    $achievementSet = $game->achievementSets()->firstOrFail();
    $group = AchievementGroup::factory()->create([
        'achievement_set_id' => $achievementSet->id,
    ]);

    AchievementSetAchievement::query()
        ->where('achievement_set_id', $achievementSet->id)
        ->where('achievement_id', $restoredAchievement->id)
        ->update(['achievement_group_id' => $group->id]);

    // ACT
    $restoredAchievement->delete();
    (new UpdateGameMetricsAction())->execute($game->refresh());

    $restoredAchievement->restore();
    (new UpdateGameMetricsAction())->execute($game->refresh());

    // ASSERT
    $this->assertDatabaseHas('achievement_set_achievements', [
        'achievement_set_id' => $achievementSet->id,
        'achievement_id' => $restoredAchievement->id,
        'achievement_group_id' => $group->id,
        'order_column' => 10,
    ]);

    $visibleAchievementIds = $achievementSet->achievements()->pluck('achievements.id');

    expect($achievementSet->refresh()->achievements_published)->toBe(2)
        ->and($achievementSet->points_total)->toBe(8)
        ->and($visibleAchievementIds)->toContain($activeAchievement->id)
        ->and($visibleAchievementIds)->toContain($restoredAchievement->id);
});
