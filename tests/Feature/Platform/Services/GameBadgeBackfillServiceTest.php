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
function svcBadgeFile(string $numericName): array
{
    $content = "badge-content-{$numericName}";
    Storage::disk('media')->put("Images/{$numericName}.png", $content);

    return ["/Images/{$numericName}.png", sha1($content)];
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
