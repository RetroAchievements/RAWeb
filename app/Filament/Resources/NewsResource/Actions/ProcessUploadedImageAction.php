<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsResource\Actions;

use Illuminate\Support\Facades\Storage;

class ProcessUploadedImageAction
{
    public function execute(string $tempImagePath): string
    {
        $file = Storage::disk('local')->get($tempImagePath);
        $base64 = base64_encode($file);
        $mimeType = Storage::disk('local')->mimeType($tempImagePath);
        $dataUrl = "data:{$mimeType};base64,{$base64}";

        $imagePath = UploadNewsImage($dataUrl);

        // Clean up all temp files.
        $this->cleanUpTempFiles();

        return $imagePath;
    }

    private function cleanUpTempFiles(): void
    {
        $tempFiles = Storage::disk('local')->files('temp');

        foreach ($tempFiles as $tempFile) {
            Storage::disk('local')->delete($tempFile);
        }
    }
}
