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

        // Clean up the temporary file.
        Storage::disk('local')->delete($tempImagePath);

        return $imagePath;
    }
}
