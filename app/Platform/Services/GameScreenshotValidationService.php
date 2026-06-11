<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
use App\Rules\DisallowAnimatedImageRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GameScreenshotValidationService
{
    /**
     * @throws ValidationException
     */
    public function validateFile(UploadedFile $file, Game $game): void
    {
        $allowedMimes = $game->system?->supports_upscaled_screenshots
            ? 'mimes:png,jpg,jpeg,webp'
            : 'mimes:png';

        $validator = Validator::make(
            ['screenshot' => $file],
            ['screenshot' => [
                'image',
                $allowedMimes,
                'max:6144',
                'dimensions:min_width=64,min_height=64,max_width=3840,max_height=2160',
                new DisallowAnimatedImageRule(),
            ]],
        );

        $validator->validate();
    }

    /**
     * @throws ValidationException
     */
    public function validateResolution(int $width, int $height, Game $game): void
    {
        $system = $game->system;
        if (!$system) {
            return;
        }

        $service = new ScreenshotResolutionService();
        if ($service->isValidResolution($width, $height, $system)) {
            return;
        }

        $resolutions = $system->screenshot_resolutions;
        $formatted = collect($resolutions)
            ->map(fn (array $r) => "{$r['width']}x{$r['height']}")
            ->join(', ');

        $smpteNote = '';
        if ($system->has_analog_tv_output) {
            $smpteNote = ' SMPTE 601 capture resolutions (704x480, 720x480, 720x486, 704x576, 720x576) are also accepted.';
        }

        throw ValidationException::withMessages([
            'screenshot' => "This screenshot's dimensions ({$width}x{$height}) don't match the expected resolutions for {$system->name}: {$formatted} (or 2x/3x integer multiples).{$smpteNote}",
        ]);
    }

    /**
     * @return string the SHA1 hash of the file
     * @throws ValidationException
     */
    public function validateHash(UploadedFile $file, Game $game): string
    {
        $hash = sha1_file($file->getRealPath());

        // Reject duplicates based on SHA1 across this game's screenshots so a
        // rejection cannot be bypassed by re-uploading the same image. The one
        // exception is MissingMatchingCompanion: the image itself was fine and
        // was only rejected pending a paired companion, so resubmission with
        // the missing companion is the intended way to clear that decision.
        $isDuplicate = $game->gameScreenshots()
            ->whereHas('media', function ($query) use ($hash) {
                $query->where('custom_properties->sha1', $hash);
            })
            ->where(function ($query) {
                $query
                    ->where('status', '!=', GameScreenshotStatus::Rejected)
                    ->orWhere('rejection_reason', '!=', GameScreenshotRejectionReason::MissingMatchingCompanion)
                    ->orWhereNull('rejection_reason');
            })
            ->exists();

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'screenshot' => 'This image has already been uploaded for this game.',
            ]);
        }

        return $hash;
    }
}
