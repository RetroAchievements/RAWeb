<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class DisallowAnimatedImageRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('The :attribute must be an uploaded file.');

            return;
        }

        $imagePath = $value->getRealPath();
        if ($imagePath === false) {
            $fail('The :attribute must be a valid file.');

            return;
        }

        $content = file_get_contents($imagePath);
        if ($content === false) {
            $fail('The :attribute could not be read.');

            return;
        }

        $mimeType = $value->getMimeType();
        if (
            ($mimeType === 'image/png' && $this->isAnimatedPng($content))
            || ($mimeType === 'image/webp' && $this->isAnimatedWebp($content))
        ) {
            $fail('Animated images are not allowed. Please upload a static image.');
        }
    }

    /**
     * APNG files contain an 'acTL' (animation control) chunk that static PNGs lack.
     *
     * @see https://wiki.mozilla.org/APNG_Specification#.60acTL.60:_The_Animation_Control_Chunk
     */
    private function isAnimatedPng(string $content): bool
    {
        return str_contains($content, 'acTL');
    }

    /**
     * Animated WebPs use either an 'ANIM' chunk or the VP8X animation flag.
     * VP8X is the extended format header where bit 1 (0x02) indicates animation.
     *
     * @see https://developers.google.com/speed/webp/docs/riff_container#extended_file_format
     */
    private function isAnimatedWebp(string $content): bool
    {
        if (str_contains($content, 'ANIM')) {
            return true;
        }

        // The flags byte follows immediately after 'VP8X' in the extended header.
        $vp8xPos = strpos($content, 'VP8X');
        if ($vp8xPos !== false && strlen($content) > $vp8xPos + 8) {
            $flagsByte = ord($content[$vp8xPos + 4]);

            return ($flagsByte & 0x02) !== 0;
        }

        return false;
    }
}
