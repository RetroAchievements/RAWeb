<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\AchievementGroup;
use App\Models\AchievementSetAchievement;
use App\Models\Game;
use App\Models\GameBadge;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\GameBadgeAttribution;
use App\Platform\Events\GameBecamePlayable;
use App\Platform\Jobs\UpdateGamePlayerGamesJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

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

it('queues outdated player games when achievement set version changes', function () {
    // ARRANGE
    Queue::fake();

    $user = User::factory()->create();
    $game = Game::factory()->create();

    Achievement::factory()->promoted()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
    ]);

    // ACT
    (new UpdateGameMetricsAction())->execute($game);

    // ASSERT
    Queue::assertPushedOn('game-player-games', UpdateGamePlayerGamesJob::class);
});

it('dispatches GameBecamePlayable when a set crosses into playable', function () {
    // ARRANGE
    Queue::fake();
    Event::fake([GameBecamePlayable::class]);
    $game = Game::factory()->create(['achievements_published' => 0]);
    Achievement::factory()->promoted()->for($game)->create(['points' => 5]);

    // ACT
    (new UpdateGameMetricsAction())->execute($game);

    // ASSERT
    Event::assertDispatched(GameBecamePlayable::class);
});

it('does not dispatch GameBecamePlayable when the set is already playable', function () {
    // ARRANGE
    Queue::fake();
    Event::fake([GameBecamePlayable::class]);
    $game = Game::factory()->create(['achievements_published' => 3]);
    Achievement::factory()->promoted()->for($game)->create(['points' => 5]);

    // ACT
    (new UpdateGameMetricsAction())->execute($game);

    // ASSERT
    Event::assertNotDispatched(GameBecamePlayable::class);
});

it('does not dispatch GameBecamePlayable when the set stays WIP', function () {
    // ARRANGE
    Queue::fake();
    Event::fake([GameBecamePlayable::class]);
    $game = Game::factory()->create(['achievements_published' => 0]);
    Achievement::factory()->for($game)->create(['points' => 5]); // unpromoted

    // ACT
    (new UpdateGameMetricsAction())->execute($game);

    // ASSERT
    Event::assertNotDispatched(GameBecamePlayable::class);
});

it('captures the WIP icon as the current badge when the set is re-promoted', function () {
    // ARRANGE
    // 1- Badge X was the current badge while the set was playable.
    // 2- The set was demoted.
    // 3- The badge was changed to Badge Y while everything was demoted.
    // 4- Stuff is promoted and the set is playable again.

    Queue::fake();
    Storage::fake('media');

    $pathY = '/Images/410001.png';
    Storage::disk('media')->put('Images/410001.png', 'badge-content-Y');

    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'achievements_published' => 0, // currently WIP (mid-demote)
        'image_icon_asset_path' => $pathY,
    ]);
    $x = GameBadge::factory()->create([
        'game_id' => $game->id,
        'sha1' => 'x-sha-e2e',
        'image_asset_path' => '/Images/409999.png',
        'attribution_source' => GameBadgeAttribution::Live,
        'became_current_at' => Carbon::parse('2024-01-01 00:00:00'),
        'replaced_at' => null,
    ]);
    Achievement::factory()->promoted()->for($game)->create(['points' => 5]);

    // ACT
    (new UpdateGameMetricsAction())->execute($game);

    // ASSERT
    $badges = GameBadge::where('game_id', $game->id)->get();
    expect($badges)->toHaveCount(2);
    expect($badges->firstWhere('image_asset_path', $pathY)->replaced_at)->toBeNull();
    expect($badges->firstWhere('id', $x->id)->replaced_at)->not->toBeNull();
});
