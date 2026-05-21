<?php

declare(strict_types=1);

use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\System;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->system = System::factory()->create();
});

it('stores parent_game_id from achievement set links', function () {
    // ARRANGE
    $achievementSet = AchievementSet::factory()->create();
    $parentGame = Game::factory()->create(['system_id' => $this->system->id]);
    $subsetGame = Game::factory()->create(['system_id' => $this->system->id]);

    // ACT
    GameAchievementSet::factory()->create([
        'game_id' => $subsetGame->id,
        'achievement_set_id' => $achievementSet->id,
        'type' => AchievementSetType::Core,
    ]);

    GameAchievementSet::factory()->create([
        'game_id' => $parentGame->id,
        'achievement_set_id' => $achievementSet->id,
        'type' => AchievementSetType::Bonus,
    ]);

    // ASSERT
    expect($subsetGame->refresh()->parent_game_id)->toEqual($parentGame->id);
    expect($parentGame->refresh()->parent_game_id)->toBeNull();
});

it('clears parent_game_id when the achievement set link is removed', function () {
    // ARRANGE
    $achievementSet = AchievementSet::factory()->create();
    $parentGame = Game::factory()->create(['system_id' => $this->system->id]);
    $subsetGame = Game::factory()->create(['system_id' => $this->system->id]);

    GameAchievementSet::factory()->create([
        'game_id' => $subsetGame->id,
        'achievement_set_id' => $achievementSet->id,
        'type' => AchievementSetType::Core,
    ]);

    $parentLink = GameAchievementSet::factory()->create([
        'game_id' => $parentGame->id,
        'achievement_set_id' => $achievementSet->id,
        'type' => AchievementSetType::Bonus,
    ]);

    expect($subsetGame->refresh()->parent_game_id)->toEqual($parentGame->id);

    // ACT
    $parentLink->delete();

    // ASSERT
    expect($subsetGame->refresh()->parent_game_id)->toBeNull();
});

it('keeps the earliest parent when multiple parents use the same set', function () {
    // ARRANGE
    $achievementSet = AchievementSet::factory()->create();
    $subsetGame = Game::factory()->create(['system_id' => $this->system->id]);
    $secondParentGame = Game::factory()->create(['system_id' => $this->system->id]);
    $firstParentGame = Game::factory()->create(['system_id' => $this->system->id]);

    GameAchievementSet::factory()->create([
        'game_id' => $subsetGame->id,
        'achievement_set_id' => $achievementSet->id,
        'type' => AchievementSetType::Core,
    ]);

    // ACT
    GameAchievementSet::factory()->create([
        'game_id' => $secondParentGame->id,
        'achievement_set_id' => $achievementSet->id,
        'type' => AchievementSetType::Bonus,
        'created_at' => now()->addMinute(),
    ]);

    GameAchievementSet::factory()->create([
        'game_id' => $firstParentGame->id,
        'achievement_set_id' => $achievementSet->id,
        'type' => AchievementSetType::Bonus,
        'created_at' => now(),
    ]);

    // ASSERT
    expect($subsetGame->refresh()->parent_game_id)->toEqual($firstParentGame->id);
});

it('clears an existing title-fallback subset when its parent game is renamed', function () {
    // ARRANGE
    $parentGame = Game::factory()->create([
        'title' => 'Mega Man 2',
        'system_id' => $this->system->id,
    ]);

    $subsetGame = Game::factory()->create([
        'title' => 'Mega Man 2 [Subset - Bonus]',
        'system_id' => $this->system->id,
    ]);

    GameAchievementSet::factory()->create([
        'game_id' => $subsetGame->id,
        'achievement_set_id' => AchievementSet::factory(),
        'type' => AchievementSetType::Core,
    ]);

    expect($subsetGame->refresh()->parent_game_id)->toEqual($parentGame->id);

    // ACT
    $parentGame->update(['title' => 'Mega Man II']);

    // ASSERT
    expect($subsetGame->refresh()->parent_game_id)->toBeNull();
});
