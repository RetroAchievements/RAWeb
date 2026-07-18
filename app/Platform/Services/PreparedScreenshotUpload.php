<?php

declare(strict_types=1);

namespace App\Platform\Services;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

final class PreparedScreenshotUpload
{
    public const PRESERVED_ORIGINAL_FILENAME = 'original-capture.png';

    private function __construct(
        public readonly string $filePath,
        public readonly int $width,
        private readonly ?string $originalContents,
        private readonly ?string $tempPathToCleanup,
    ) {
    }

    public static function passThrough(string $filePath, int $width): self
    {
        return new self($filePath, $width, null, null);
    }

    public static function doubled(string $tempPath, int $width, string $originalContents): self
    {
        return new self($tempPath, $width, $originalContents, $tempPath);
    }

    /**
     * Choose the right file extension for the upload. Doubled captures are
     * always written as PNG by CreateDoubledScreenshotAction, regardless of
     * what the source extension was.
     */
    public function extension(string $sourceExtension): string
    {
        return $this->originalContents !== null ? 'png' : $sourceExtension;
    }

    public function cleanup(): void
    {
        if ($this->tempPathToCleanup !== null) {
            @unlink($this->tempPathToCleanup);
        }
    }

    /**
     * Once the Media row exists, preserve the pre-doubling capture alongside
     * the stored media file so it can be recovered later if needed.
     */
    public function finalize(Media $media): void
    {
        if ($this->originalContents === null) {
            return;
        }

        $directory = PathGeneratorFactory::create($media)->getPath($media);

        Storage::disk($media->disk)->put(
            $directory . self::PRESERVED_ORIGINAL_FILENAME,
            $this->originalContents,
        );

        $media->setCustomProperty('original_capture_path', self::PRESERVED_ORIGINAL_FILENAME);
        $media->save();
    }
}
