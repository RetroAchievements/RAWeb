<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\System;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidScreenshotResolutionRule implements ValidationRule
{
    private const MAX_SCALE_FACTOR = 3;

    /**
     * SMPTE 601 analog capture resolutions accepted for any system with
     * analog TV output. These represent standard digitization of analog
     * video signals and do not get integer-multiple scaling.
     */
    private const SMPTE_601_RESOLUTIONS = [
        ['width' => 704, 'height' => 480],  // NTSC
        ['width' => 720, 'height' => 480],  // NTSC
        ['width' => 720, 'height' => 486],  // NTSC
        ['width' => 704, 'height' => 576],  // PAL
        ['width' => 720, 'height' => 576],  // PAL
    ];

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

        if ($this->isValidResolution($width, $height, $resolutions)) {
            return;
        }

        $formatted = collect($resolutions)
            ->map(fn (array $r) => "{$r['width']}x{$r['height']}")
            ->join(', ');

        $smpteNote = '';
        if ($this->system->has_analog_tv_output) {
            $smpteFormatted = collect(self::SMPTE_601_RESOLUTIONS)
                ->map(fn (array $r) => "{$r['width']}x{$r['height']}")
                ->join(', ');

            $smpteNote = " SMPTE 601 capture resolutions ({$smpteFormatted}) are also accepted.";
        }

        $fail("This screenshot's dimensions ({$width}x{$height}) don't match the expected resolutions for {$this->system->name}: {$formatted} (or 2x/3x integer multiples).{$smpteNote}");
    }

    /**
     * @param array<int, array{width: int, height: int}> $resolutions
     */
    private function isValidResolution(int $width, int $height, array $resolutions): bool
    {
        foreach ($resolutions as $resolution) {
            $baseW = $resolution['width'];
            $baseH = $resolution['height'];

            if ($width === $baseW && $height === $baseH) {
                return true;
            }

            // Both axes must scale by the same integer factor.
            if ($width % $baseW === 0 && $height % $baseH === 0) {
                $scaleX = (int) ($width / $baseW);
                $scaleY = (int) ($height / $baseH);

                if ($scaleX === $scaleY && $scaleX >= 2 && $scaleX <= self::MAX_SCALE_FACTOR) {
                    return true;
                }
            }
        }

        // SMPTE 601 resolutions are checked for an exact match only (no scaling).
        if ($this->system->has_analog_tv_output) {
            foreach (self::SMPTE_601_RESOLUTIONS as $smpte) {
                if ($width === $smpte['width'] && $height === $smpte['height']) {
                    return true;
                }
            }
        }

        return false;
    }
}
