<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameBadge;
use App\Models\System;
use App\Platform\Enums\GameBadgeAttribution;
use App\Platform\Services\GameBadgeBackfillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    $this->service = app(GameBadgeBackfillService::class);
});

/** @return array{0: string, 1: string} [assetPath, sha1] */
function svcBadgeFile(string $numericName, int $width = 96, int $height = 96): array
{
    // the dimension guard reads real pixel data, so we have to write a genuine PNG.
    $content = svcPngBytes($numericName, $width, $height);
    Storage::disk('media')->put("Images/{$numericName}.png", $content);

    return ["/Images/{$numericName}.png", sha1($content)];
}

function svcPngBytes(string $seed, int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);

    // tint by the seed so distinct files produce distinct bytes (and distinct sha1s).
    $hash = crc32($seed);
    $color = imagecolorallocate($image, $hash & 0xFF, ($hash >> 8) & 0xFF, ($hash >> 16) & 0xFF);
    imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $color);

    ob_start();
    imagepng($image);

    return (string) ob_get_clean();
}

it("does not move a Live row's became_current_at during markAsCurrent", function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory(), 'achievements_published' => 5]);
    [$path, $sha1] = svcBadgeFile('300001');
    $liveAt = Carbon::parse('2025-01-01 00:00:00');
    $live = GameBadge::factory()->create([
        'game_id' => $game->id,
        'sha1' => $sha1,
        'image_asset_path' => $path,
        'attribution_source' => GameBadgeAttribution::Live,
        'became_current_at' => $liveAt,
        'replaced_at' => null,
    ]);

    // ACT
    // ... a later backfill touch of the same (game, sha1) ...
    $this->service->markAsCurrent($game->id, $path, Carbon::parse('2026-01-01 00:00:00'), GameBadgeAttribution::BackfillAuditLog);

    // ASSERT
    expect($live->fresh()->became_current_at->equalTo($liveAt))->toBeTrue();
});

it("preserves a Live current row's became_current_at during reconcileCurrentCanonical", function () {
    // ARRANGE
    [$path, $sha1] = svcBadgeFile('300002');
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'achievements_published' => 5,
        'image_icon_asset_path' => $path,
    ]);
    $liveAt = Carbon::parse('2025-06-15 00:00:00');
    $live = GameBadge::factory()->create([
        'game_id' => $game->id,
        'sha1' => $sha1,
        'image_asset_path' => $path,
        'attribution_source' => GameBadgeAttribution::Live,
        'became_current_at' => $liveAt,
        'replaced_at' => null,
    ]);

    // ACT
    $this->service->reconcileCurrentCanonical($game);

    // ASSERT
    $fresh = $live->fresh();
    expect($fresh->became_current_at->equalTo($liveAt))->toBeTrue();
    expect($fresh->replaced_at)->toBeNull();
});

it('does not record a non-96x96 image during markAsCurrent', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory(), 'achievements_published' => 5]);
    // ... a forum post linked a 256x224 screenshot instead of the badge ...
    [$screenshotPath] = svcBadgeFile('300010', width: 256, height: 224);

    // ACT
    $this->service->markAsCurrent($game->id, $screenshotPath, Carbon::parse('2025-01-01 00:00:00'), GameBadgeAttribution::BackfillForumComment);

    // ASSERT
    expect(GameBadge::where('game_id', $game->id)->count())->toEqual(0);
});

it('still records a genuine 96x96 image during markAsCurrent', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory(), 'achievements_published' => 5]);
    [$badgePath] = svcBadgeFile('300011');

    // ACT
    $this->service->markAsCurrent($game->id, $badgePath, Carbon::parse('2025-01-01 00:00:00'), GameBadgeAttribution::BackfillForumComment);

    // ASSERT
    expect(GameBadge::where('game_id', $game->id)->where('image_asset_path', $badgePath)->count())->toEqual(1);
});

it('does not create a canonical row when the current icon is not 96x96', function () {
    // ARRANGE
    [$screenshotPath] = svcBadgeFile('300012', width: 320, height: 240);
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'achievements_published' => 5,
        'image_icon_asset_path' => $screenshotPath,
    ]);

    // ACT
    $this->service->reconcileCurrentCanonical($game);

    // ASSERT
    expect(GameBadge::where('game_id', $game->id)->count())->toEqual(0);
});

it('does not create a canonical row for a currently-demoted game', function () {
    // ARRANGE
    // ... previously published, but currently WIP ...
    $game = Game::factory()->create([
        'system_id' => System::factory(),
        'achievements_published' => 0,
        'image_icon_asset_path' => '/Images/300003.png',
    ]);

    // ACT
    $this->service->reconcileCurrentCanonical($game);

    // ASSERT
    expect(GameBadge::where('game_id', $game->id)->count())->toEqual(0);
});
