<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameScreenshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

class PromoteGameScreenshotMediaToApprovedAction
{
    /**
     * Migrate a screenshot's Media record from the screenshots-pending collection
     * to the screenshots collection. Moves the underlying S3 files and queues
     * conversion generation (thumbnails, webp variants) once the current
     * transaction commits.
     *
     * No-op when the media is already in the screenshots collection.
     */
    public function execute(GameScreenshot $screenshot): void
    {
        $media = $screenshot->media;
        if (!$media || $media->collection_name !== 'screenshots-pending') {
            return;
        }

        $pathGenerator = PathGeneratorFactory::create($media);
        $oldPath = $pathGenerator->getPath($media);

        $media->collection_name = 'screenshots';
        $newPath = $pathGenerator->getPath($media);

        $this->moveApprovedScreenshotMedia(
            media: $media,
            oldPath: $oldPath,
            newPath: $newPath,
        );

        $media->save();
        $this->queueApprovedScreenshotConversions($media);
    }

    private function moveApprovedScreenshotMedia(Media $media, string $oldPath, string $newPath): void
    {
        $disk = Storage::disk($media->disk);

        foreach ($disk->allFiles($oldPath) as $file) {
            $newFile = $newPath . Str::after($file, $oldPath);

            $disk->move($file, $newFile);
        }
    }

    private function queueApprovedScreenshotConversions(Media $media): void
    {
        // defer medialibrary conversion generation until after the DB transaction commits.
        // we don't want to generate thumbnails for a screenshot whose transaction failed.
        DB::afterCommit(function () use ($media): void {
            app(FileManipulator::class)->createDerivedFiles($media);
        });
    }
}
