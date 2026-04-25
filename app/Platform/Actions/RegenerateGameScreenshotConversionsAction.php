<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\Conversions\Actions\PerformConversionAction;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\TemporaryDirectory;

class RegenerateGameScreenshotConversionsAction
{
    /**
     * Regenerate all media conversions for a screenshot by downloading
     * the original from the CDN rather than pulling directly from S3.
     * S3 egress is expensive. Fall back to S3 only if the CDN request fails.
     */
    public function execute(Media $media): void
    {
        // Only do work for a collection that's pending.
        $conversions = ConversionCollection::createForMedia($media)
            ->filter(fn ($conversion) => $conversion->shouldBePerformedOn($media->collection_name))
            ->reject(fn ($conversion) => $media->hasGeneratedConversion($conversion->getName()));

        if ($conversions->isEmpty()) {
            return;
        }

        $temporaryDirectory = TemporaryDirectory::create();
        $tempPath = $temporaryDirectory->path(Str::random(32) . '.' . $media->extension);

        try {
            $this->downloadOriginal($media, $tempPath);

            if (!file_exists($tempPath) || filesize($tempPath) === 0) {
                Log::warning("RegenerateGameScreenshotConversions: could not obtain original for media {$media->id}");

                return;
            }

            foreach ($conversions as $conversion) {
                (new PerformConversionAction())->execute($conversion, $media, $tempPath);
            }
        } finally {
            $temporaryDirectory->delete();
        }
    }

    private function downloadOriginal(Media $media, string $targetPath): void
    {
        try {
            $cdnUrl = $media->getUrl();
            $response = Http::timeout(60)->sink($targetPath)->get($cdnUrl);

            if ($response->successful() && file_exists($targetPath) && filesize($targetPath) > 0) {
                return;
            }
        } catch (ConnectionException) {
            // CDN is unreachable. Fall through to S3.
        }

        Log::info("RegenerateGameScreenshotConversions: CDN unavailable for media {$media->id}, falling back to S3.");

        app(Filesystem::class)->copyFromMediaLibrary($media, $targetPath);
    }
}
