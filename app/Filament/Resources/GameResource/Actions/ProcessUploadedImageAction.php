<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Actions;

use App\Platform\Enums\ImageType;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedImageAction
{
    public function execute(string $tempImagePath): string
    {
        $file = Storage::disk('local')->get($tempImagePath);
        $base64 = base64_encode($file);
        $mimeType = Storage::disk('local')->mimeType($tempImagePath);
        $dataUrl = "data:{$mimeType};base64,{$base64}";

        $imagePath = UploadGameImage(createFileArrayFromDataUrl($dataUrl), ImageType::GameIcon);

        $this->cleanUpTempFiles();

        return $imagePath;
    }

    private function cleanUpTempFiles(): void
    {
        // We don't want to accidentally clear out a temp file someone else
        // may be working on.
        $threeHoursAgo = now()->subHours(6)->timestamp;

        $tempFiles = Storage::disk('local')->files('temp');

        foreach ($tempFiles as $tempFile) {
            if (Storage::disk('local')->lastModified($tempFile) < $threeHoursAgo) {
                Storage::disk('local')->delete($tempFile);
            }
        }
    }
}
