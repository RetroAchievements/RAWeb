<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Enums\ImageUploadType;
use App\Platform\Enums\ImageType;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedImageAction
{
    public function execute(string $tempImagePath, ImageUploadType $imageUploadType): string
    {
        try {
            /** @var Filesystem $disk */
            $disk = Storage::disk('livewire-tmp');

            // Ensure the Livewire temporary image file exists.
            if (!$disk->exists($tempImagePath)) {
                throw new Exception("Temporary image file does not exist: {$tempImagePath}");
            }

            // Read the file content.
            $fileContent = $disk->get($tempImagePath);
            $base64 = base64_encode($fileContent);
            $mimeType = $disk->mimeType($tempImagePath);
            $dataUrl = "data:{$mimeType};base64,{$base64}";

            // Upload the image and get the final path.
            $imagePath = null;
            if ($imageUploadType === ImageUploadType::News) {
                $imagePath = UploadNewsImage($dataUrl);
            } else {
                $imageType = match ($imageUploadType) {
                    ImageUploadType::HubBadge => ImageType::GameIcon,
                    ImageUploadType::GameBadge => ImageType::GameIcon,
                    ImageUploadType::GameBoxArt => ImageType::GameBoxArt,
                    ImageUploadType::GameTitle => ImageType::GameTitle,
                    ImageUploadType::GameInGame => ImageType::GameInGame,
                };

                $file = createFileArrayFromDataUrl($dataUrl);
                $imagePath = UploadGameImage($file, $imageType);
            }

            // Livewire auto-deletes these temp files after 24 hours, however
            // we're certain that we don't need it anymore. Optimistically delete.
            $disk->delete($tempImagePath);

            return $imagePath;
        } catch (Exception $e) {
            Log::error("Error processing uploaded image from {$tempImagePath}: " . $e->getMessage());

            throw $e;
        }
    }
}
