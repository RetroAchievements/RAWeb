<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Enums\ImageUploadType;
use App\Platform\Enums\ImageType;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedImageAction
{
    public function execute(string $tempImagePath, ImageUploadType $imageUploadType): ?string
    {
        try {
            /** @var Filesystem $disk */
            $disk = Storage::disk('livewire-tmp');

            // Check if the temporary file still exists.
            // If it doesn't, notify the user.
            if (!$disk->exists($tempImagePath)) {
                Notification::make()
                    ->title('Image upload expired')
                    ->body('Your image upload has expired. Uploaded files are only kept for 10 minutes. Please re-upload the image and save promptly.')
                    ->danger()
                    ->persistent()
                    ->send();

                return null;
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
            } elseif ($imageUploadType === ImageUploadType::AchievementBadge) {
                $file = createFileArrayFromDataUrl($dataUrl);
                $imagePath = UploadBadgeImage($file);
            } else {
                $imageType = match ($imageUploadType) {
                    ImageUploadType::HubBadge => ImageType::GameIcon,
                    ImageUploadType::GameBadge => ImageType::GameIcon,
                    ImageUploadType::GameBoxArt => ImageType::GameBoxArt,
                    ImageUploadType::GameTitle => ImageType::GameTitle,
                    ImageUploadType::GameInGame => ImageType::GameInGame,
                    ImageUploadType::EventAward => ImageType::GameIcon,
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
