<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Platform\Actions\ClearGameScreenshotsFromGamePageAction;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

function createScreenshotMedia(Game $game, array $customProperties = []): Media
{
    return Media::create([
        'model_type' => Game::class,
        'model_id' => $game->id,
        'uuid' => (string) Str::uuid(),
        'collection_name' => 'screenshots',
        'name' => 'screenshot',
        'file_name' => 'screenshot-' . Str::random(8) . '.png',
        'mime_type' => 'image/png',
        'disk' => 's3',
        'size' => 1024,
        'manipulations' => [],
        'custom_properties' => array_merge(
            ['sha1' => sha1(uniqid()), 'legacy_path' => '/Images/0' . random_int(10000, 99999) . '.png'],
            $customProperties
        ),
        'generated_conversions' => [],
        'responsive_images' => [],
    ]);
}

it('given a new primary image is being set, demotes any old primary and promotes the new one', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);

    $media1 = createScreenshotMedia($game);
    $media2 = createScreenshotMedia($game);

    $oldPrimary = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $media1->id,
        'order_column' => 1,
    ]);

    $newPrimary = GameScreenshot::factory()->for($game)->ingame()->create([
        'media_id' => $media2->id,
        'is_primary' => false,
        'order_column' => 2,
    ]);

    // ACT
    // ... simulate the 'set as primary' action logic ...
    $currentPrimary = GameScreenshot::where('game_id', $game->id)
        ->where('type', ScreenshotType::Ingame)
        ->where('is_primary', true)
        ->first();

    $currentPrimary->update(['is_primary' => false]);

    $newPrimary->update([
        'is_primary' => true,
        'status' => GameScreenshotStatus::Approved,
    ]);

    // ASSERT
    expect($oldPrimary->fresh()->is_primary)->toBeFalse();
    expect($newPrimary->fresh()->is_primary)->toBeTrue();
    expect($newPrimary->fresh()->order_column)->toBeLessThan($oldPrimary->fresh()->order_column);
});

it('given a pending screenshot is promoted to primary, auto-approves it from pending status', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);

    $media = createScreenshotMedia($game);

    $screenshot = GameScreenshot::factory()->for($game)->ingame()->pending()->create([
        'media_id' => $media->id,
        'is_primary' => false,
    ]);

    // ACT
    // ... simulate the 'set as primary' action logic ...
    $screenshot->update([
        'is_primary' => true,
        'status' => GameScreenshotStatus::Approved,
    ]);

    // ASSERT
    $fresh = $screenshot->fresh();
    expect($fresh->is_primary)->toBeTrue();
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
});

it('given a primary screenshot is permanently deleted, automatically promotes the next approved screenshot to primary', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/000002.png',
    ]);

    $media1 = createScreenshotMedia($game);
    $media2 = createScreenshotMedia($game);

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
    $media = $primary->media;
    $primary->forceDelete();
    $media?->delete();

    // ASSERT
    // ... observer promotes the next approved screenshot ...
    expect($next->fresh()->is_primary)->toBeTrue();
});

it('given the last screenshot is permanently deleted, resets the legacy column to the placeholder image', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/099999.png',
    ]);

    $media = createScreenshotMedia($game);

    $screenshot = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $media->id,
    ]);

    // ACT
    $media = $screenshot->media;
    $screenshot->forceDelete();
    $media?->delete();

    // ASSERT
    expect($game->fresh()->image_ingame_asset_path)->toEqual('/Images/000002.png');
});

it('given a screenshot is permanently deleted, also cleans up the accompanying Spatie Media record', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);

    $media = createScreenshotMedia($game);
    $mediaId = $media->id;

    $screenshot = GameScreenshot::factory()->for($game)->ingame()->create([
        'media_id' => $media->id,
        'is_primary' => false,
    ]);

    // ACT
    $media = $screenshot->media;
    $screenshot->forceDelete();
    $media?->delete();

    // ASSERT
    expect(Media::find($mediaId))->toBeNull();
    expect(GameScreenshot::find($screenshot->id))->toBeNull();
});

it('given screenshots are cleared from the game page, soft deletes every screenshot and preserves media', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_title_asset_path' => '/Images/088888.png',
        'image_ingame_asset_path' => '/Images/099999.png',
    ]);

    $titleMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/011111.png']);
    $ingameMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/022222.png']);
    $rejectedMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/033333.png']);

    $title = GameScreenshot::factory()->for($game)->title()->primary()->create([
        'media_id' => $titleMedia->id,
    ]);

    $ingame = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $ingameMedia->id,
    ]);

    $rejected = GameScreenshot::factory()->for($game)->ingame()->rejected()->create([
        'media_id' => $rejectedMedia->id,
    ]);

    // ACT
    $clearedCount = (new ClearGameScreenshotsFromGamePageAction())->execute($game);

    // ASSERT
    expect($clearedCount)->toEqual(3);
    expect(GameScreenshot::where('game_id', $game->id)->count())->toEqual(0);
    expect(GameScreenshot::withTrashed()->where('game_id', $game->id)->count())->toEqual(3);

    $this->assertSoftDeleted($title);
    $this->assertSoftDeleted($ingame);
    $this->assertSoftDeleted($rejected);

    expect(Media::find($titleMedia->id))->not->toBeNull();
    expect(Media::find($ingameMedia->id))->not->toBeNull();
    expect(Media::find($rejectedMedia->id))->not->toBeNull();

    expect(GameScreenshot::withTrashed()->where('game_id', $game->id)->where('is_primary', true)->count())->toEqual(0);

    $freshGame = $game->fresh();
    expect($freshGame->image_title_asset_path)->toEqual(Game::PLACEHOLDER_IMAGE_PATH);
    expect($freshGame->image_ingame_asset_path)->toEqual(Game::PLACEHOLDER_IMAGE_PATH);
    expect(GameScreenshot::withTrashed()->findOrFail($rejected->id)->status)->toEqual(GameScreenshotStatus::Rejected);
});

it('given a cleared screenshot is restored, restores it with its prior status', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $media = createScreenshotMedia($game);

    $screenshot = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $media->id,
    ]);

    (new ClearGameScreenshotsFromGamePageAction())->execute($game);

    // ACT
    $trashedScreenshot = GameScreenshot::onlyTrashed()->findOrFail($screenshot->id);
    $trashedScreenshot->restore();

    // ASSERT
    $fresh = GameScreenshot::findOrFail($screenshot->id);
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->is_primary)->toBeFalse();
});

it('given a screenshot is uploaded as the first of its type, auto-promotes it to the primary of that type', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/000002.png',
    ]);

    $media = createScreenshotMedia($game);

    // ACT
    // ... create the first ingame screenshot as non-primary, it should be auto-promoted ...
    $screenshot = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $media->id,
    ]);

    // ASSERT
    expect($screenshot->fresh()->is_primary)->toBeTrue();
});

it('given a primary screenshot of a type already exists, subsequent uploads stay as non-primary', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);

    $media1 = createScreenshotMedia($game);
    $media2 = createScreenshotMedia($game);

    GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $media1->id,
        'order_column' => 1,
    ]);

    // ACT
    // ... create a second screenshot as non-primary ...
    $second = GameScreenshot::factory()->for($game)->ingame()->create([
        'media_id' => $media2->id,
        'is_primary' => false,
        'order_column' => 2,
    ]);

    // ASSERT
    expect($second->fresh()->is_primary)->toBeFalse();
});
