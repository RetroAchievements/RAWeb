<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameBadge;
use App\Models\System;
use App\Platform\Actions\RecordGameBadgeChangeAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('s3');
});

function recordBadgePath(string $numericName): string
{
    Storage::disk('media')->put("Images/{$numericName}.png", "badge-content-{$numericName}");

    return "/Images/{$numericName}.png";
}

it('writes a badge row when the game is playable', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'achievements_published' => 5,
    ]);
    $path = recordBadgePath('200001');

    // ACT
    $badge = (new RecordGameBadgeChangeAction())->execute($game, $path);

    // ASSERT
    expect($badge)->not->toBeNull();
    expect(GameBadge::where('game_id', $game->id)->count())->toEqual(1);
    expect($game->badges()->first()->image_asset_path)->toEqual($path);
});

it('writes a badge row from s3 when the media disk is missing the badge file', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'achievements_published' => 5,
    ]);
    $path = '/Images/200099.png';
    Storage::disk('s3')->put('Images/200099.png', 'badge-content-200099');

    // ACT
    $badge = (new RecordGameBadgeChangeAction())->execute($game, $path);

    // ASSERT
    expect($badge)->not->toBeNull();
    expect($badge->sha1)->toEqual(sha1('badge-content-200099'));
    expect($game->badges()->first()->image_asset_path)->toEqual($path);
});

it('does not write a badge row while the set is WIP', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'achievements_published' => 0,
    ]);
    $path = recordBadgePath('200002');

    // ACT
    $badge = (new RecordGameBadgeChangeAction())->execute($game, $path);

    // ASSERT
    expect($badge)->toBeNull();
    expect(GameBadge::where('game_id', $game->id)->count())->toEqual(0);
});

it('keeps real badges and ignores WIP churn across a demote and republish', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'achievements_published' => 5,
    ]);
    $action = new RecordGameBadgeChangeAction();

    $pathA = recordBadgePath('200010');
    $pathB = recordBadgePath('200011');
    $pathC = recordBadgePath('200012');

    // ACT + ASSERT

    // playable, so badge A is recorded and current
    $action->execute($game, $pathA);
    expect(GameBadge::where('game_id', $game->id)->count())->toEqual(1);

    // demoted to WIP, so a churn icon B is not recorded and A stays
    $game->achievements_published = 0;
    $game->save();
    $action->execute($game, $pathB);
    expect(GameBadge::where('game_id', $game->id)->count())->toEqual(1);
    expect(GameBadge::where('game_id', $game->id)->where('image_asset_path', $pathB)->exists())->toBeFalse();

    // republished, so badge C is recorded and current
    // A is retired but still present/selectable
    $game->achievements_published = 7;
    $game->save();
    $action->execute($game, $pathC);

    $badges = GameBadge::where('game_id', $game->id)->get();
    expect($badges)->toHaveCount(2);
    expect($badges->firstWhere('image_asset_path', $pathC)->replaced_at)->toBeNull();
    expect($badges->firstWhere('image_asset_path', $pathA)->replaced_at)->not->toBeNull();

    // reverting to A reactivates the same row rather than creating a duplicate
    $action->execute($game, $pathA);
    $badges = GameBadge::where('game_id', $game->id)->get();
    expect($badges)->toHaveCount(2);
    expect($badges->firstWhere('image_asset_path', $pathA)->replaced_at)->toBeNull();
    expect($badges->firstWhere('image_asset_path', $pathC)->replaced_at)->not->toBeNull();
});

it('revives a soft-deleted badge row when its image is uploaded again', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'achievements_published' => 5,
    ]);
    $action = new RecordGameBadgeChangeAction();
    $path = recordBadgePath('200020');

    $badge = $action->execute($game, $path);

    $badge->delete();
    expect(GameBadge::withTrashed()->where('game_id', $game->id)->count())->toEqual(1);
    expect(GameBadge::where('game_id', $game->id)->count())->toEqual(0);

    // ACT
    // ... the same image is uploaded again ...
    $revived = $action->execute($game, $path);

    // ASSERT
    // ... the original row is restored rather than a duplicate inserted ...
    expect($revived->id)->toEqual($badge->id);
    expect(GameBadge::withTrashed()->where('game_id', $game->id)->count())->toEqual(1);
    expect($revived->trashed())->toBeFalse();
    expect($revived->replaced_at)->toBeNull();
});
