<?php

declare(strict_types=1);

use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use App\Platform\Actions\ApproveGameScreenshotAction;
use App\Platform\Actions\SubmitPendingGameScreenshotAction;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotReviewDecision;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

uses(RefreshDatabase::class);

final class ApproveGameScreenshotActionTestFileManipulator extends FileManipulator
{
    /** @var list<Media> */
    public array $createdDerivedFilesFor = [];

    /** @var list<GameScreenshotStatus|null> */
    public array $screenshotStatusesDuringDerivedFileCreation = [];

    /** @var list<string|null> */
    public array $mediaCollectionNamesDuringDerivedFileCreation = [];

    /** @var list<int> */
    public array $transactionLevelsDuringDerivedFileCreation = [];

    public function createDerivedFiles(
        Media $media,
        array $onlyConversionNames = [],
        bool $onlyMissing = false,
        bool $withResponsiveImages = false,
        bool $queueAll = false,
    ): void {
        $this->createdDerivedFilesFor[] = $media;
        $this->screenshotStatusesDuringDerivedFileCreation[] = GameScreenshot::query()
            ->where('media_id', $media->id)
            ->first()
            ?->status;
        $this->mediaCollectionNamesDuringDerivedFileCreation[] = Media::query()
            ->whereKey($media->id)
            ->value('collection_name');
        $this->transactionLevelsDuringDerivedFileCreation[] = DB::transactionLevel();
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

function fakeApproveScreenshotFileManipulator(): ApproveGameScreenshotActionTestFileManipulator
{
    $fileManipulator = new ApproveGameScreenshotActionTestFileManipulator();
    app()->instance(FileManipulator::class, $fileManipulator);

    return $fileManipulator;
}

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('media');
});

it('carries the Atari 2600 original-capture image through the pending-to-approved transition', function () {
    // ARRANGE
    $system = System::factory()->create([
        'id' => System::Atari2600,
        'screenshot_resolutions' => [['width' => 160, 'height' => 228]],
        'has_analog_tv_output' => true,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $pending = (new SubmitPendingGameScreenshotAction())->execute(
        $game,
        UploadedFile::fake()->image('native.png', 160, 228),
        ScreenshotType::Ingame,
        $submitter,
    );

    $pendingMedia = $pending->fresh()->media;
    $pendingDirectory = PathGeneratorFactory::create($pendingMedia)->getPath($pendingMedia);
    Storage::disk('s3')->assertExists($pendingDirectory . 'original-capture.png');

    $fileManipulator = new ApproveGameScreenshotActionTestFileManipulator();
    app()->instance(FileManipulator::class, $fileManipulator);

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending->fresh(['media']), $reviewer, ScreenshotReviewDecision::Gallery);

    // ASSERT
    $approvedMedia = $pending->fresh()->media;
    expect($approvedMedia->getCustomProperty('original_capture_path'))->toEqual('original-capture.png');

    $approvedDirectory = PathGeneratorFactory::create($approvedMedia)->getPath($approvedMedia);
    Storage::disk('s3')->assertMissing($pendingDirectory . 'original-capture.png');
    Storage::disk('s3')->assertExists($approvedDirectory . 'original-capture.png');
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

    $fileManipulator = fakeApproveScreenshotFileManipulator();
    $ambientTransactionLevel = DB::transactionLevel();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Gallery);

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
    expect($fileManipulator->screenshotStatusesDuringDerivedFileCreation)->toEqual([GameScreenshotStatus::Approved]);
    expect($fileManipulator->mediaCollectionNamesDuringDerivedFileCreation)->toEqual(['screenshots']);
    expect($fileManipulator->transactionLevelsDuringDerivedFileCreation)->toEqual([$ambientTransactionLevel]);

    $delayedSubscription = UserDelayedSubscription::sole(); // only one
    expect($delayedSubscription->user_id)->toEqual($submitter->id);
    expect($delayedSubscription->subject_type)->toEqual(SubscriptionSubjectType::GameScreenshotDecision);
    expect($delayedSubscription->subject_id)->toEqual($fresh->id);
    expect($delayedSubscription->first_update_id)->toEqual($fresh->id);

});

it('dispatches approved media conversions after the approval transaction commits', function () {
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
    $pendingMedia->collection_name = 'screenshots';
    $newPath = $pendingMedia->getPathRelativeToRoot();
    $pendingMedia->collection_name = 'screenshots-pending';

    $fileManipulator = fakeApproveScreenshotFileManipulator();
    $ambientTransactionLevel = DB::transactionLevel();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Gallery);

    $fresh = $pending->fresh(['media']);

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->media->collection_name)->toEqual('screenshots');
    Storage::disk('s3')->assertMissing($oldPath);
    Storage::disk('s3')->assertExists($newPath);
    expect($fileManipulator->createdDerivedFilesFor)->toHaveCount(1);
    expect($fileManipulator->screenshotStatusesDuringDerivedFileCreation)->toEqual([GameScreenshotStatus::Approved]);
    expect($fileManipulator->mediaCollectionNamesDuringDerivedFileCreation)->toEqual(['screenshots']);
    expect($fileManipulator->transactionLevelsDuringDerivedFileCreation)->toEqual([$ambientTransactionLevel]);
});

it('rejects screenshots that have already been reviewed', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $reviewer = User::factory()->create();

    $approved = GameScreenshot::factory()->for($game)->ingame()->create([
        'status' => GameScreenshotStatus::Approved,
    ]);

    // ACT
    $attempt = fn () => (new ApproveGameScreenshotAction())->execute($approved, $reviewer, ScreenshotReviewDecision::Gallery);

    // ASSERT
    expect($attempt)->toThrow(ValidationException::class, 'This screenshot has already been reviewed.');
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

    $fileManipulator = fakeApproveScreenshotFileManipulator();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Primary);

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->is_primary)->toBeTrue();
    expect($existing->fresh()->status)->toEqual(GameScreenshotStatus::Replaced);
    expect($existing->fresh()->is_primary)->toBeFalse();
    expect($fileManipulator->createdDerivedFilesFor)->toHaveCount(1);
});

it('promotes the first approved ingame screenshot to primary when the game has none yet', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $pending = createPendingScreenshotForApprovalTest(
        $game,
        $submitter,
        ScreenshotType::Ingame,
        withLegacyPath: true,
    );

    $fileManipulator = fakeApproveScreenshotFileManipulator();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Primary);

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->is_primary)->toBeTrue();
});

it('orders a newly promoted ingame primary ahead of pre-existing approved gallery screenshots that lack a primary', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    // Three pre-existing approved in-game screenshots, none marked primary
    // (the data shape left behind by the original is_primary regression).
    $sibling1 = GameScreenshot::factory()->for($game)->ingame()->create([
        'status' => GameScreenshotStatus::Approved,
        'is_primary' => false,
        'order_column' => 1,
    ]);
    $sibling2 = GameScreenshot::factory()->for($game)->ingame()->create([
        'status' => GameScreenshotStatus::Approved,
        'is_primary' => false,
        'order_column' => 2,
    ]);
    $sibling3 = GameScreenshot::factory()->for($game)->ingame()->create([
        'status' => GameScreenshotStatus::Approved,
        'is_primary' => false,
        'order_column' => 3,
    ]);

    $pending = createPendingScreenshotForApprovalTest(
        $game,
        $submitter,
        ScreenshotType::Ingame,
        withLegacyPath: true,
    );

    $fileManipulator = fakeApproveScreenshotFileManipulator();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Primary);

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->is_primary)->toBeTrue();
    expect($fresh->order_column)->toBeLessThan($sibling1->fresh()->order_column);
    expect($fresh->order_column)->toBeLessThan($sibling2->fresh()->order_column);
    expect($fresh->order_column)->toBeLessThan($sibling3->fresh()->order_column);
});

it('does not promote a newly approved ingame screenshot when a valid primary already exists', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 256, 'height' => 224]],
        'has_analog_tv_output' => false,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $existingPrimary = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'width' => 256,
        'height' => 224,
        'order_column' => 1,
    ]);

    $pending = createPendingScreenshotForApprovalTest(
        $game,
        $submitter,
        ScreenshotType::Ingame,
        width: 256,
        height: 224,
    );

    $fileManipulator = fakeApproveScreenshotFileManipulator();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Gallery);

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->is_primary)->toBeFalse();
    expect($existingPrimary->fresh()->is_primary)->toBeTrue();
});

it('can explicitly approve an ingame screenshot as the replacement primary', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 512, 'height' => 384]],
        'has_analog_tv_output' => false,
        'supports_upscaled_screenshots' => true,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $existingPrimary = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'width' => 512,
        'height' => 384,
        'order_column' => 1,
    ]);

    GameScreenshot::factory()->count(9)->for($game)->ingame()->create([
        'width' => 512,
        'height' => 384,
        'is_primary' => false,
    ]);

    $pending = createPendingScreenshotForApprovalTest(
        $game,
        $submitter,
        ScreenshotType::Ingame,
        width: 1024,
        height: 768,
        withLegacyPath: true,
    );

    $fileManipulator = fakeApproveScreenshotFileManipulator();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Primary);

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->is_primary)->toBeTrue();
    expect($fresh->order_column)->toBeLessThan($existingPrimary->fresh()->order_column);

    expect($existingPrimary->fresh()->status)->toEqual(GameScreenshotStatus::Replaced);
    expect($existingPrimary->fresh()->is_primary)->toBeFalse();
    expect($game->gameScreenshots()->ofType(ScreenshotType::Ingame)->approved()->count())->toEqual(10);
    expect($fileManipulator->createdDerivedFilesFor)->toHaveCount(1);
});

it('does not explicitly replace an ingame primary with an invalid resolution screenshot', function () {
    // ARRANGE
    $system = System::factory()->create([
        'screenshot_resolutions' => [['width' => 512, 'height' => 384]],
        'has_analog_tv_output' => false,
        'supports_upscaled_screenshots' => true,
    ]);
    $game = Game::factory()->create(['system_id' => $system->id]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $existingPrimary = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'width' => 512,
        'height' => 384,
        'order_column' => 1,
    ]);

    $pending = createPendingScreenshotForApprovalTest(
        $game,
        $submitter,
        ScreenshotType::Ingame,
        width: 1024,
        height: 768,
        withLegacyPath: true,
    );
    $system->update(['supports_upscaled_screenshots' => false]);

    $fileManipulator = fakeApproveScreenshotFileManipulator();

    // ACT
    $attempt = fn () => (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Primary);

    // ASSERT
    expect($attempt)->toThrow(
        ValidationException::class,
        'This screenshot has an unsupported resolution and cannot replace the primary.',
    );

    expect($pending->fresh()->status)->toEqual(GameScreenshotStatus::Pending);
    expect($pending->fresh()->is_primary)->toBeFalse();
    expect($existingPrimary->fresh()->status)->toEqual(GameScreenshotStatus::Approved);
    expect($existingPrimary->fresh()->is_primary)->toBeTrue();
    expect($fileManipulator->createdDerivedFilesFor)->toBeEmpty();
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

    $fileManipulator = fakeApproveScreenshotFileManipulator();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Primary);

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->is_primary)->toBeTrue();
    expect($existingPrimary->fresh()->status)->toEqual(GameScreenshotStatus::Replaced);
    expect($existingPrimary->fresh()->is_primary)->toBeFalse();
    expect($fileManipulator->createdDerivedFilesFor)->toHaveCount(1);
});

it('promotes a new ingame primary while keeping the current primary in the gallery', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $existingPrimary = GameScreenshot::factory()->for($game)->ingame()->primary()->create([
        'order_column' => 1,
    ]);

    $pending = createPendingScreenshotForApprovalTest(
        $game,
        $submitter,
        ScreenshotType::Ingame,
        withLegacyPath: true,
    );

    $fileManipulator = fakeApproveScreenshotFileManipulator();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::PrimaryKeepGallery);

    $fresh = $pending->fresh();

    // ASSERT
    expect($fresh->status)->toEqual(GameScreenshotStatus::Approved);
    expect($fresh->is_primary)->toBeTrue();

    // the old primary is demoted but stays visible as a non-primary gallery image, not retired
    expect($existingPrimary->fresh()->status)->toEqual(GameScreenshotStatus::Approved);
    expect($existingPrimary->fresh()->is_primary)->toBeFalse();

    expect($game->gameScreenshots()->ofType(ScreenshotType::Ingame)->approved()->count())->toEqual(2);
    expect($fileManipulator->createdDerivedFilesFor)->toHaveCount(1);
});

it('does not allow keeping a gallery copy when approving a title screenshot', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    GameScreenshot::factory()->for($game)->title()->primary()->create([
        'order_column' => 1,
    ]);

    $pending = createPendingScreenshotForApprovalTest($game, $submitter, ScreenshotType::Title);

    // ACT
    $attempt = fn () => (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::PrimaryKeepGallery);

    // ASSERT
    expect($attempt)->toThrow(
        ValidationException::class,
        'Title and completion screenshots must be approved as primary.',
    );

    expect($pending->fresh()->status)->toEqual(GameScreenshotStatus::Pending);
});

it('enforces the 10 approved ingame screenshot cap during approval', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    GameScreenshot::factory()->count(10)->for($game)->ingame()->create();

    $pending = createPendingScreenshotForApprovalTest($game, $submitter, ScreenshotType::Ingame);

    $fileManipulator = fakeApproveScreenshotFileManipulator();

    // ASSERT
    expect(fn () => (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Gallery))
        ->toThrow(ValidationException::class);

    expect($fileManipulator->createdDerivedFilesFor)->toBeEmpty();
});

it('writes a primaryScreenshotChanged audit row when approving a title replacement', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $existing = GameScreenshot::factory()->for($game)->title()->primary()->create([
        'order_column' => 1,
    ]);
    $existing->media->setCustomProperty('legacy_path', '/Images/title-old.png');
    $existing->media->save();

    $pending = createPendingScreenshotForApprovalTest($game, $submitter, ScreenshotType::Title, withLegacyPath: true);
    fakeApproveScreenshotFileManipulator();

    Activity::query()->delete();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Primary);

    // ASSERT
    $row = Activity::where('event', 'primaryScreenshotChanged')->sole();
    expect($row->causer_id)->toEqual($reviewer->id);
    expect($row->subject_id)->toEqual($game->id);
    expect($row->properties->get('old')['title_screenshot'])->toEqual('/Images/title-old.png');
    expect($row->properties->get('attributes'))->toHaveKey('title_screenshot');
});

it('writes a primaryScreenshotChanged row with placeholder old asset for the first ingame primary', function () {
    // ARRANGE
    $game = Game::factory()->create(['system_id' => System::factory()]);
    $submitter = User::factory()->create();
    $reviewer = User::factory()->create();

    $pending = createPendingScreenshotForApprovalTest($game, $submitter, ScreenshotType::Ingame, withLegacyPath: true);
    fakeApproveScreenshotFileManipulator();

    Activity::query()->delete();

    // ACT
    (new ApproveGameScreenshotAction())->execute($pending, $reviewer, ScreenshotReviewDecision::Primary);

    // ASSERT
    $row = Activity::where('event', 'primaryScreenshotChanged')->sole();
    expect($row->properties->get('old')['ingame_screenshot'])->toEqual(Game::PLACEHOLDER_IMAGE_PATH);
    expect($row->properties->get('attributes')['ingame_screenshot'])->not->toEqual(Game::PLACEHOLDER_IMAGE_PATH);
});
