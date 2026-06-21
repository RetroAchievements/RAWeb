<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
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

it('resets the old type legacy field to placeholder when the last screenshot of that type changes type', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_title_asset_path' => '/Images/099999.png',
    ]);

    $media = createMediaForGame($game, '/Images/099999.png');

    $screenshot = GameScreenshot::factory()->for($game)->title()->primary()->create([
        'media_id' => $media->id,
    ]);

    // ACT
    // ... change the only Title screenshot to In-game ...
    $screenshot->update(['type' => ScreenshotType::Ingame]);

    // ASSERT
    // ... the title legacy field should revert to placeholder since there are no more Title screenshots ...
    expect($game->fresh()->image_title_asset_path)->toEqual('/Images/000002.png');
});

it('does not auto-publish a non-approved screenshot when its type is changed to a type with no primary', function (GameScreenshotStatus $status) {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);

    $media = createMediaForGame($game, '/Images/099999.png');

    $screenshot = GameScreenshot::factory()->for($game)->create([
        'media_id' => $media->id,
        'type' => ScreenshotType::Ingame,
        'is_primary' => false,
        'status' => $status,
    ]);

    // ACT
    $screenshot->update(['type' => ScreenshotType::Title]);

    // ASSERT
    $fresh = $screenshot->fresh();
    expect($fresh->status)->toEqual($status);
    expect($fresh->is_primary)->toBeFalse();
})->with([
    'rejected' => GameScreenshotStatus::Rejected,
    'pending' => GameScreenshotStatus::Pending,
    'replaced' => GameScreenshotStatus::Replaced,
]);

it('promotes the next approved screenshot for the old type when the primary changes type', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
    ]);

    $media1 = createMediaForGame($game, '/Images/099998.png');
    $media2 = createMediaForGame($game, '/Images/099999.png');

    $titlePrimary = GameScreenshot::factory()->for($game)->title()->primary()->create([
        'media_id' => $media1->id,
        'order_column' => 1,
    ]);

    $nextTitle = GameScreenshot::factory()->for($game)->title()->create([
        'media_id' => $media2->id,
        'is_primary' => false,
        'order_column' => 2,
    ]);

    // ACT
    $titlePrimary->update(['type' => ScreenshotType::Ingame]);

    // ASSERT
    expect($nextTitle->fresh()->is_primary)->toBeTrue();
});

it('demotes the changed screenshot when the new type already has a primary', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
    ]);

    $media1 = createMediaForGame($game, '/Images/099998.png');
    $media2 = createMediaForGame($game, '/Images/099999.png');

    $titlePrimary = GameScreenshot::factory()->for($game)->title()->primary()->create([
        'media_id' => $media1->id,
    ]);

    GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $media2->id,
    ]);

    // ACT
    $titlePrimary->update(['type' => ScreenshotType::Ingame]);

    // ASSERT
    expect($titlePrimary->fresh()->is_primary)->toBeFalse();
});

it('moves the auto-promoted screenshot to the top of its new type group on type change', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
    ]);

    $media1 = createMediaForGame($game, '/Images/099998.png');
    $media2 = createMediaForGame($game, '/Images/099997.png');

    // ... an existing In-game screenshot at order 3 ...
    $existingIngame = GameScreenshot::factory()->for($game)->ingame()->create([
        'media_id' => $media1->id,
        'is_primary' => false,
        'order_column' => 3,
    ]);

    // ... a Title screenshot at order 10 that will be changed to In-game ...
    // ... since there's no In-game primary, the observer will auto-promote it ...
    $willChangeType = GameScreenshot::factory()->for($game)->title()->primary()->create([
        'media_id' => $media2->id,
        'order_column' => 10,
    ]);

    // ACT
    $willChangeType->update(['type' => ScreenshotType::Ingame]);

    // ASSERT
    // ... the auto-promoted screenshot should sort before the existing In-game screenshot ...
    $fresh = $willChangeType->fresh();
    expect($fresh->is_primary)->toBeTrue();
    expect($fresh->order_column)->toBeLessThan($existingIngame->fresh()->order_column);
});

it('writes a primaryScreenshotChanged row attributing the deleter when the current primary is deleted and another exists', function () {
    // ARRANGE
    $deleter = User::factory()->create();
    Auth::login($deleter);

    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/000002.png',
    ]);

    $primaryMedia = createMediaForGame($game, '/Images/old-primary.png');
    $nextMedia = createMediaForGame($game, '/Images/promoted.png');

    $primary = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $primaryMedia->id,
        'order_column' => 1,
    ]);

    GameScreenshot::factory()->for($game)->ingame()->create([
        'media_id' => $nextMedia->id,
        'is_primary' => false,
        'order_column' => 2,
    ]);

    Activity::query()->delete();

    // ACT
    $primary->delete();

    // ASSERT
    $row = Activity::where('event', 'primaryScreenshotChanged')->sole();
    expect($row->causer_id)->toEqual($deleter->id);
    expect($row->properties->get('old')['ingame_screenshot'])->toEqual('/Images/old-primary.png');
    expect($row->properties->get('attributes')['ingame_screenshot'])->toEqual('/Images/promoted.png');
});

it('writes a primaryScreenshotChanged row with placeholder new asset when the last primary of a type is deleted', function () {
    // ARRANGE
    $deleter = User::factory()->create();
    Auth::login($deleter);

    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/000002.png',
    ]);

    $primaryMedia = createMediaForGame($game, '/Images/lonely.png');

    $primary = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $primaryMedia->id,
    ]);

    Activity::query()->delete();

    // ACT
    $primary->delete();

    // ASSERT
    $row = Activity::where('event', 'primaryScreenshotChanged')->sole();
    expect($row->properties->get('old')['ingame_screenshot'])->toEqual('/Images/lonely.png');
    expect($row->properties->get('attributes')['ingame_screenshot'])->toEqual(Game::PLACEHOLDER_IMAGE_PATH);
});
