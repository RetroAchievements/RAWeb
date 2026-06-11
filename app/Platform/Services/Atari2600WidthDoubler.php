<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\System;
use App\Support\Media\CreateDoubledScreenshotAction;

/**
 * The Atari 2600's TIA outputs frames with non-square pixels. Emulators
 * capture at native resolution, so we double the width server-side to
 * roughly match what a CRT would display.
 */
final class Atari2600WidthDoubler
{
    private const NATIVE_CAPTURE_WIDTH = 160;
    private const DIMENSION_TOLERANCE = 1; // px

    public function prepare(string $sourcePath, int $width, Game $game): PreparedScreenshotUpload
    {
        if (!$this->shouldDouble($width, $game)) {
            return PreparedScreenshotUpload::passThrough($sourcePath, $width);
        }

        $originalContents = file_get_contents($sourcePath);
        $doubledTempPath = (new CreateDoubledScreenshotAction())->execute($originalContents);

        return PreparedScreenshotUpload::doubled($doubledTempPath, $width * 2, $originalContents);
    }

    private function shouldDouble(int $width, Game $game): bool
    {
        if ($game->system_id !== System::Atari2600) {
            return false;
        }

        return abs($width - self::NATIVE_CAPTURE_WIDTH) <= self::DIMENSION_TOLERANCE;
    }
}
