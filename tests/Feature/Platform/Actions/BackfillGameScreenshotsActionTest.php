<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Platform\Actions\BackfillGameScreenshotsAction;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('media');
});

function createRealPngBytes(int $width = 64, int $height = 64): string
{
    $file = UploadedFile::fake()->image('temp.png', $width, $height);

    return file_get_contents($file->getRealPath());
}

it('backfills both ingame and title screenshots from legacy asset paths', function () {
    // ARRANGE
    Storage::disk('media')->put('/Images/012345.png', createRealPngBytes());
    Storage::disk('media')->put('/Images/012346.png', createRealPngBytes(128, 128));

    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/012345.png',
        'image_title_asset_path' => '/Images/012346.png',
    ]);

    // ACT
    (new BackfillGameScreenshotsAction())->execute($game);

    // ASSERT
    $screenshots = GameScreenshot::where('game_id', $game->id)->get();
    expect($screenshots)->toHaveCount(2);

    $ingame = $screenshots->firstWhere('type', ScreenshotType::Ingame);
    expect($ingame->is_primary)->toBeTrue();
    expect($ingame->status)->toEqual(GameScreenshotStatus::Approved);

    $title = $screenshots->firstWhere('type', ScreenshotType::Title);
    expect($title->is_primary)->toBeTrue();
    expect($title->status)->toEqual(GameScreenshotStatus::Approved);
});

it('skips placeholder paths', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/000002.png',
        'image_title_asset_path' => '/Images/000002.png',
    ]);

    // ACT
    (new BackfillGameScreenshotsAction())->execute($game);

    // ASSERT
    expect(GameScreenshot::where('game_id', $game->id)->count())->toEqual(0);
});

it('skips when the file does not exist on the media disk', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/999999.png',
        'image_title_asset_path' => '/Images/999998.png',
    ]);

    // ACT
    (new BackfillGameScreenshotsAction())->execute($game);

    // ASSERT
    expect(GameScreenshot::where('game_id', $game->id)->count())->toEqual(0);
});

it('is idempotent and does not duplicate when the SHA1 already exists', function () {
    // ARRANGE
    Storage::disk('media')->put('/Images/012345.png', createRealPngBytes());

    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/012345.png',
        'image_title_asset_path' => '/Images/000002.png',
    ]);

    $action = new BackfillGameScreenshotsAction();

    // ACT
    $action->execute($game);
    $action->execute($game->fresh());

    // ASSERT
    expect(GameScreenshot::where('game_id', $game->id)->count())->toEqual(1);
});

it('sets the legacy_path custom property to the original asset path', function () {
    // ARRANGE
    Storage::disk('media')->put('/Images/012345.png', createRealPngBytes());

    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/012345.png',
        'image_title_asset_path' => '/Images/000002.png',
    ]);

    // ACT
    (new BackfillGameScreenshotsAction())->execute($game);

    // ASSERT
    $media = $game->fresh()->getMedia('screenshots')->first();
    expect($media->getCustomProperty('legacy_path'))->toEqual('/Images/012345.png');
    expect($media->getCustomProperty('sha1'))->not->toBeNull();
    expect($media->getCustomProperty('width'))->toEqual(64);
    expect($media->getCustomProperty('height'))->toEqual(64);
});

it('creates both records when the title and ingame images use identical content', function () {
    // ARRANGE
    $pngBytes = createRealPngBytes();
    Storage::disk('media')->put('/Images/012345.png', $pngBytes);
    Storage::disk('media')->put('/Images/012346.png', $pngBytes);

    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => '/Images/012345.png',
        'image_title_asset_path' => '/Images/012346.png',
    ]);

    // ACT
    (new BackfillGameScreenshotsAction())->execute($game);

    // ASSERT
    $screenshots = GameScreenshot::where('game_id', $game->id)->get();
    expect($screenshots)->toHaveCount(2);
    expect($screenshots->firstWhere('type', ScreenshotType::Ingame))->not->toBeNull();
    expect($screenshots->firstWhere('type', ScreenshotType::Title))->not->toBeNull();
});

it('handles null asset paths gracefully', function () {
    // ARRANGE
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'image_ingame_asset_path' => null,
        'image_title_asset_path' => null,
    ]);

    // ACT
    (new BackfillGameScreenshotsAction())->execute($game);

    // ASSERT
    expect(GameScreenshot::where('game_id', $game->id)->count())->toEqual(0);
});
