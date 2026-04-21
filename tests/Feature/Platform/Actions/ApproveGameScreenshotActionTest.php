<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\ApproveGameScreenshotAction;
use App\Platform\Actions\SubmitPendingGameScreenshotAction;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

final class ApproveGameScreenshotActionTestFileManipulator extends FileManipulator
{
    /** @var list<Media> */
    public array $createdDerivedFilesFor = [];

    public function createDerivedFiles(
        Media $media,
        array $onlyConversionNames = [],
        bool $onlyMissing = false,
        bool $withResponsiveImages = false,
        bool $queueAll = false,
    ): void {
        $this->createdDerivedFilesFor[] = $media;
    }
}

function createPendingScreenshotForApprovalTest(
    Game $game,
    User $submitter,
    ScreenshotType $type,
    int $width = 256,
    int $height = 224,
    bool $withLegacyPath = false,
): GameScreenshot {
    $screenshot = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        UploadedFile::fake()->image('pending.png', $width, $height),
        $type,
        $submitter,
    );

    if ($withLegacyPath) {
        $media = $screenshot->media;
        $media->setCustomProperty('legacy_path', '/Images/099999.png');
        $media->save();
    }

    return $screenshot->fresh(['media']);
}

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('media');
});

it('approves a pending screenshot, moves its media, and records review metadata', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'order_column' => 1,
    ]);

    $pending = createPendingScreenshotForApprovalTest($game, $submitter, ScreenshotType::Ingame);
    $pendingMedia = $pending->media;
    $oldPath = $pendingMedia->getPathRelativeToRoot();

    $fileManipulator = new ApproveGameScreenshotActionTestFileManipulator();
    app()->instance(FileManipulator::class, $fileManipulator);

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer);

    $fresh = $pending->fresh(['media']);

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->reviewed_by_user_id)->toEqual($reviewer->id);
    expect($fresh->reviewed_at)->not->toBeNull();
    expect($fresh->is_primary)->toBeFalse();
    expect($fresh->order_column)->toEqual(2);
    expect($fresh->media->collection_name)->toEqual('screenshots');
    expect($fresh->media->getPathRelativeToRoot())->not->toEqual($oldPath);

    Storage::disk('s3')->assertMissing($oldPath);
    Storage::disk('s3')->assertExists($fresh->media->getPathRelativeToRoot());
    expect($fileManipulator->createdDerivedFilesFor)->toHaveCount(1);
    expect($fileManipulator->createdDerivedFilesFor[0]->id)->toEqual($fresh->media->id);
    expect(App\Models\UserDelayedSubscription::count())->toEqual(0);
    expect(App\Models\PlayerBadge::count())->toEqual(0);
});

it('replaces the existing approved title screenshot when a new one is approved', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $existing = GameScreenshot::factory()->for($game)->title()->primary()->create([
        'order_column' => 1,
    ]);

    $pending = createPendingScreenshotForApprovalTest(
        $game,
        $submitter,
        ScreenshotType::Title,
        withLegacyPath: true,
    );

    $fileManipulator = new ApproveGameScreenshotActionTestFileManipulator();
    app()->instance(FileManipulator::class, $fileManipulator);

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer);

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->is_primary)->toBeTrue();
    expect($existing->fresh()->status)->toEqual(GameScreenshotStatus::Replaced);
    expect($existing->fresh()->is_primary)->toBeFalse();
    expect($fileManipulator->createdDerivedFilesFor)->toHaveCount(1);
});

it('promotes a newly approved ingame screenshot when the current primary has an invalid resolution', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 256, 'height' => 224]],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $existingPrimary = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'width' => 320,
        'height' => 240,
        'order_column' => 1,
    ]);

    $pending = createPendingScreenshotForApprovalTest(
        $game,
        $submitter,
        ScreenshotType::Ingame,
        width: 256,
        height: 224,
        withLegacyPath: true,
    );

    $fileManipulator = new ApproveGameScreenshotActionTestFileManipulator();
    app()->instance(FileManipulator::class, $fileManipulator);

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer);

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->is_primary)->toBeTrue();
    expect($existingPrimary->fresh()->status)->toEqual(GameScreenshotStatus::Replaced);
    expect($existingPrimary->fresh()->is_primary)->toBeFalse();
    expect($fileManipulator->createdDerivedFilesFor)->toHaveCount(1);
});

it('enforces the 20 approved ingame screenshot cap during approval', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    GameScreenshot::factory()->count(20)->for($game)->ingame()->create();

    $pending = createPendingScreenshotForApprovalTest($game, $submitter, ScreenshotType::Ingame);

    $fileManipulator = new ApproveGameScreenshotActionTestFileManipulator();
    app()->instance(FileManipulator::class, $fileManipulator);

    // ASSERT
    expect(fn () => (new ApproveGameScreenshotAction())->execute($pending, $reviewer))
        ->toThrow(ValidationException::class);

    expect($fileManipulator->createdDerivedFilesFor)->toBeEmpty();
});
