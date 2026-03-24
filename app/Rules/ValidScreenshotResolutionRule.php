<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\System;
use App\Platform\Services\ScreenshotResolutionService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidScreenshotResolutionRule implements ValidationRule
{
    public function __construct(
        private readonly System $system,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $resolutions = $this->system->screenshot_resolutions;
        if (empty($resolutions)) {
            return;
        }

        if (!$value instanceof UploadedFile) {
            $fail('The :attribute must be an uploaded file.');

            return;
        }

        $imagePath = $value->getRealPath();
        if ($imagePath === false) {
            $fail('The :attribute must be a valid file.');

            return;
        }

        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            $fail('Unable to read image dimensions. The file may be corrupt.');

            return;
        }

        [$width, $height] = $imageInfo;

        $service = new ScreenshotResolutionService();
        if ($service->isValidResolution($width, $height, $this->system)) {
            return;
        }

        $formatted = collect($resolutions)
            ->map(fn (array $r) => "{$r['width']}x{$r['height']}")
            ->join(', ');

        $smpteNote = '';
        if ($this->system->has_analog_tv_output) {
            $smpteNote = ' SMPTE 601 capture resolutions (704x480, 720x480, 720x486, 704x576, 720x576) are also accepted.';
        }

        $filename = $value->getClientOriginalName();

        $fail("{$filename} ({$width}x{$height}) doesn't match the expected resolutions for {$this->system->name}: {$formatted} (or 2x/3x integer multiples).{$smpteNote}");
    }
}
