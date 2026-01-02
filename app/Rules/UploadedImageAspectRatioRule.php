<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class UploadedImageAspectRatioRule implements ValidationRule
{
    public function __construct(
        private float $targetRatio,
        private float $tolerance = 0.05,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('The :attribute must be an uploaded file.');

            return;
        }

        $imagePath = $value->getRealPath();

        try {
            $imageInfo = getimagesize($imagePath);
        } catch (Exception) {
            $fail('The :attribute must be a valid image.');

            return;
        }

        if (!$imageInfo) {
            $fail('The :attribute must be a valid image.');

            return;
        }

        [$width, $height] = $imageInfo;

        if ($height === 0) {
            $fail('The :attribute has invalid dimensions.');

            return;
        }

        $actualRatio = $width / $height;
        $difference = abs($actualRatio - $this->targetRatio);
        $allowedDifference = $this->targetRatio * $this->tolerance;

        if ($difference > $allowedDifference) {
            $targetWidth = (int) ($this->targetRatio * 100);
            $targetHeight = 100;

            $fail("The :attribute must be approximately {$targetWidth}:{$targetHeight} aspect ratio. Uploaded image is {$width}x{$height}.");
        }
    }
}
