<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\ClearGameScreenshotsFromGamePageAction;
use App\Platform\Actions\LogPrimaryScreenshotChangeAction;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('media');
});

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
    $primary->delete();
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
    $screenshot->delete();
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
    $screenshot->delete();
    $media?->delete();

    // ASSERT
    expect(Media::find($mediaId))->toBeNull();
    expect(GameScreenshot::find($screenshot->id))->toBeNull();
});

it('given screenshots are cleared from the game page, moves published and pending rows to Rejected and resets legacy paths', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_title_asset_path' => '/Images/088888.png',
        'image_ingame_asset_path' => '/Images/099999.png',
    ]);

    $titleMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/011111.png']);
    $ingameMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/022222.png']);
    $pendingMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/044444.png']);
    $rejectedMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/033333.png']);
    $replacedMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/055555.png']);

    $title = GameScreenshot::factory()->for($game)->title()->primary()->create([
        'media_id' => $titleMedia->id,
    ]);

    $ingame = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $ingameMedia->id,
    ]);

    $pending = GameScreenshot::factory()->for($game)->ingame()->pending()->create([
        'media_id' => $pendingMedia->id,
        'is_primary' => false,
    ]);

    $rejected = GameScreenshot::factory()->for($game)->ingame()->rejected()->create([
        'media_id' => $rejectedMedia->id,
    ]);

    $replaced = GameScreenshot::factory()->for($game)->ingame()->create([
        'media_id' => $replacedMedia->id,
        'is_primary' => false,
        'status' => GameScreenshotStatus::Replaced,
    ]);

    // ACT
    $clearedCount = (new ClearGameScreenshotsFromGamePageAction())->execute($game);

    // ASSERT
    // ... only the previously approved (2) + pending (1) rows count as cleared ...
    expect($clearedCount)->toEqual(3);
    expect(GameScreenshot::where('game_id', $game->id)->count())->toEqual(5);

    expect($title->fresh()->status)->toEqual(GameScreenshotStatus::Rejected);
    expect($title->fresh()->is_primary)->toBeFalse();
    expect($ingame->fresh()->status)->toEqual(GameScreenshotStatus::Rejected);
    expect($ingame->fresh()->is_primary)->toBeFalse();
    expect($pending->fresh()->status)->toEqual(GameScreenshotStatus::Rejected);
    expect($pending->fresh()->is_primary)->toBeFalse();

    // ... already-rejected and replaced rows are left alone ...
    expect($rejected->fresh()->status)->toEqual(GameScreenshotStatus::Rejected);
    expect($replaced->fresh()->status)->toEqual(GameScreenshotStatus::Replaced);

    // ... media survives so the rows are recoverable via re-approve ...
    expect(Media::find($titleMedia->id))->not->toBeNull();
    expect(Media::find($ingameMedia->id))->not->toBeNull();
    expect(Media::find($pendingMedia->id))->not->toBeNull();

    $freshGame = $game->fresh();
    expect($freshGame->image_title_asset_path)->toEqual(Game::PLACEHOLDER_IMAGE_PATH);
    expect($freshGame->image_ingame_asset_path)->toEqual(Game::PLACEHOLDER_IMAGE_PATH);
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

it('writes a primaryScreenshotChanged audit row with old and new asset paths when promoting a screenshot to primary via the relation manager flow', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $causer = User::factory()->create();
    Auth::login($causer);

    $media1 = createScreenshotMedia($game, ['legacy_path' => '/Images/promote-old.png']);
    $media2 = createScreenshotMedia($game, ['legacy_path' => '/Images/promote-new.png']);

    $previousPrimary = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $media1->id,
        'order_column' => 1,
    ]);

    $newPrimary = GameScreenshot::factory()->for($game)->ingame()->create([
        'media_id' => $media2->id,
        'is_primary' => false,
        'order_column' => 2,
    ]);

    $previousPrimary->update(['is_primary' => false]);
    $newPrimary->update([
        'is_primary' => true,
        'status' => GameScreenshotStatus::Approved,
    ]);

    Activity::query()->delete();

    // ACT
    (new LogPrimaryScreenshotChangeAction())->execute(
        $game,
        $newPrimary->type,
        $previousPrimary,
        $newPrimary,
    );

    // ASSERT
    $row = Activity::sole();
    expect($row->event)->toEqual('primaryScreenshotChanged');
    expect($row->causer_id)->toEqual($causer->id);
    expect($row->properties->get('old')['ingame_screenshot'])->toEqual('/Images/promote-old.png');
    expect($row->properties->get('attributes')['ingame_screenshot'])->toEqual('/Images/promote-new.png');
});

it('writes one primaryScreenshotChanged row per type that had a primary when clearing screenshots', function () {
    // ARRANGE
    $causer = User::factory()->create();
    Auth::login($causer);

    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_title_asset_path' => '/Images/title-pre.png',
        'image_ingame_asset_path' => '/Images/ingame-pre.png',
    ]);

    $titleMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/title-pre.png']);
    $ingameMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/ingame-pre.png']);
    $completionMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/completion-pre.png']);

    GameScreenshot::factory()->for($game)->title()->primary()->create([
        'media_id' => $titleMedia->id,
    ]);
    GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'media_id' => $ingameMedia->id,
    ]);
    GameScreenshot::factory()->for($game)->completion()->primary()->create([
        'media_id' => $completionMedia->id,
    ]);

    Activity::query()->delete();

    // ACT
    (new ClearGameScreenshotsFromGamePageAction())->execute($game);

    // ASSERT
    $rows = Activity::where('event', 'primaryScreenshotChanged')->get();
    expect($rows)->toHaveCount(3);

    $titleRow = $rows->first(fn ($row) => array_key_exists('title_screenshot', $row->properties->get('old')));
    expect($titleRow->properties->get('old')['title_screenshot'])->toEqual('/Images/title-pre.png');
    expect($titleRow->properties->get('attributes')['title_screenshot'])->toEqual(Game::PLACEHOLDER_IMAGE_PATH);

    $ingameRow = $rows->first(fn ($row) => array_key_exists('ingame_screenshot', $row->properties->get('old')));
    expect($ingameRow->properties->get('old')['ingame_screenshot'])->toEqual('/Images/ingame-pre.png');
    expect($ingameRow->properties->get('attributes')['ingame_screenshot'])->toEqual(Game::PLACEHOLDER_IMAGE_PATH);

    $completionRow = $rows->first(fn ($row) => array_key_exists('completion_screenshot', $row->properties->get('old')));
    expect($completionRow->properties->get('old')['completion_screenshot'])->toEqual('/Images/completion-pre.png');
    expect($completionRow->properties->get('attributes')['completion_screenshot'])->toEqual(Game::PLACEHOLDER_IMAGE_PATH);
});

it('writes only the title primaryScreenshotChanged row when only a title primary existed', function () {
    // ARRANGE
    $causer = User::factory()->create();
    Auth::login($causer);

    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_title_asset_path' => '/Images/title-only.png',
    ]);

    $titleMedia = createScreenshotMedia($game, ['legacy_path' => '/Images/title-only.png']);

    GameScreenshot::factory()->for($game)->title()->primary()->create([
        'media_id' => $titleMedia->id,
    ]);

    Activity::query()->delete();

    // ACT
    (new ClearGameScreenshotsFromGamePageAction())->execute($game);

    // ASSERT
    expect(Activity::where('event', 'primaryScreenshotChanged')->count())->toEqual(1);
    $row = Activity::where('event', 'primaryScreenshotChanged')->sole();
    expect($row->properties->get('old'))->toHaveKey('title_screenshot');
});
