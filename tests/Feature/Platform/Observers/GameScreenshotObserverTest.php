<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

function createMediaForGame(Game $game, string $legacyPath): Media
{
    return Media::create([
        'model_type' => Game::class,
        'model_id' => $game->id,
        'uuid' => (string) Str::uuid(),
        'collection_name' => 'screenshots',
        'name' => 'screenshot',
        'file_name' => 'screenshot.png',
        'mime_type' => 'image/png',
        'disk' => 's3',
        'size' => 1024,
        'manipulations' => [],
        'custom_properties' => ['sha1' => sha1(uniqid()), 'legacy_path' => $legacyPath],
        'generated_conversions' => [],
        'responsive_images' => [],
    ]);
}

it('syncs legacy screenshot fields when a screenshot becomes primary', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/000002.png',
    ]);

    $media = createMediaForGame($game, '/Images/099999.png');

    $screenshot = GameScreenshot::factory()->for($game)->ingame()->create([
        'media_id' => $media->id,
        'is_primary' => false,
    ]);

    // ACT
    $screenshot->update(['is_primary' => true]);

    // ASSERT
    expect($game->fresh()->image_ingame_asset_path)->toEqual('/Images/099999.png');
});

it('does not sync legacy fields when a non-primary screenshot is saved', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/000002.png',
    ]);

    $media = createMediaForGame($game, '/Images/099999.png');

    $screenshot = GameScreenshot::factory()->for($game)->ingame()->create([
        'media_id' => $media->id,
        'is_primary' => false,
    ]);

    // ACT
    $screenshot->update(['description' => 'Updated description']);

    // ASSERT
    expect($game->fresh()->image_ingame_asset_path)->toEqual('/Images/000002.png');
});

it('promotes the next approved screenshot when the primary is deleted', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/000002.png',
    ]);

    $media1 = createMediaForGame($game, '/Images/099998.png');
    $media2 = createMediaForGame($game, '/Images/099999.png');

    $primary = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $media1->id,
        'order_column' => 1,
    ]);

    $next = GameScreenshot::factory()->for($game)->ingame()->create([
        'media_id' => $media2->id,
        'is_primary' => false,
        'order_column' => 2,
    ]);

    // ACT
    $primary->delete();

    // ASSERT
    expect($next->fresh()->is_primary)->toBeTrue();
});

it('resets to placeholder when the last screenshot of a type is deleted', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/099999.png',
    ]);

    $media = createMediaForGame($game, '/Images/099999.png');

    $screenshot = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $media->id,
    ]);

    // ACT
    $screenshot->delete();

    // ASSERT
    expect($game->fresh()->image_ingame_asset_path)->toEqual('/Images/000002.png');
});

it('preserves the title path when syncing an ingame screenshot', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_title_asset_path' => '/Images/088888.png',
        'image_ingame_asset_path' => '/Images/000002.png',
    ]);

    $media = createMediaForGame($game, '/Images/099999.png');

    // ACT
    GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $media->id,
    ]);

    // ASSERT
    $fresh = $game->fresh();
    expect($fresh->image_ingame_asset_path)->toEqual('/Images/099999.png');
    expect($fresh->image_title_asset_path)->toEqual('/Images/088888.png');
});

it('preserves the ingame path when deleting the last title screenshot', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/088888.png',
        'image_title_asset_path' => '/Images/099999.png',
    ]);

    $media = createMediaForGame($game, '/Images/099999.png');

    $screenshot = GameScreenshot::factory()->for($game)->title()->primary()->create([
        'media_id' => $media->id,
    ]);

    // ACT
    $screenshot->delete();

    // ASSERT
    $fresh = $game->fresh();
    expect($fresh->image_title_asset_path)->toEqual('/Images/000002.png');
    expect($fresh->image_ingame_asset_path)->toEqual('/Images/088888.png');
});
