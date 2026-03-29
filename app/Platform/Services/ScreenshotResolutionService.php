<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\System;
use App\Platform\Enums\GameScreenshotStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScreenshotResolutionService
{
    private const MAX_SCALE_FACTOR_UPSCALED = 3;
    private const MAX_SCALE_FACTOR_DEFAULT = 1;

    /**
     * Emulators can produce captures off by 1px due to rounding
     * during scaling, overscan cropping, or framebuffer alignment.
     */
    private const DIMENSION_TOLERANCE = 1;

    /**
     * SMPTE 601 analog capture resolutions are accepted for any system
     * with analog TV output. These represent standard digitization of
     * analog video signals and do not get integer-multiple scaling.
     */
    private const SMPTE_601_RESOLUTIONS = [
        ['width' => 704, 'height' => 480],  // NTSC
        ['width' => 720, 'height' => 480],  // NTSC
        ['width' => 720, 'height' => 486],  // NTSC
        ['width' => 704, 'height' => 576],  // PAL
        ['width' => 720, 'height' => 576],  // PAL
    ];

    /**
     * Constrain a query to only game_screenshots whose media dimensions
     * don't match any valid resolution for the given system.
     *
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function buildWrongResolutionQuery(Builder $query, System $system): Builder
    {
        $resolutions = $system->screenshot_resolutions;
        if (empty($resolutions)) {
            // no resolutions defined means all resolutions are acceptable
            return $query->whereRaw('1 = 0');
        }

        // Build `BETWEEN` conditions directly from base resolutions and their
        // integer multiples. Each target uses +/- 1px tolerance via `BETWEEN`.
        $conditions = [];
        $bindings = [];

        $tolerance = self::DIMENSION_TOLERANCE;
        $maxScale = $this->getMaxScaleFactor($system);

        foreach ($resolutions as $resolution) {
            for ($scale = 1; $scale <= $maxScale; $scale++) {
                $w = $resolution['width'] * $scale;
                $h = $resolution['height'] * $scale;

                $conditions[] = '(game_screenshots.width BETWEEN ? AND ? AND game_screenshots.height BETWEEN ? AND ?)';
                $bindings[] = $w - $tolerance;
                $bindings[] = $w + $tolerance;
                $bindings[] = $h - $tolerance;
                $bindings[] = $h + $tolerance;
            }
        }

        if ($system->has_analog_tv_output) {
            foreach (self::SMPTE_601_RESOLUTIONS as $smpte) {
                $conditions[] = '(game_screenshots.width = ? AND game_screenshots.height = ?)';
                $bindings[] = $smpte['width'];
                $bindings[] = $smpte['height'];
            }
        }

        $matchCondition = implode(' OR ', $conditions);

        return $query
            ->where('game_screenshots.status', GameScreenshotStatus::Approved)
            ->whereNotNull('game_screenshots.width')
            ->whereRaw("NOT ({$matchCondition})", $bindings);
    }

    /**
     * Check if specific dimensions match any valid resolution for a system.
     */
    public function isValidResolution(int $width, int $height, System $system): bool
    {
        $resolutions = $system->screenshot_resolutions;
        if (empty($resolutions)) {
            return true;
        }

        $tolerance = self::DIMENSION_TOLERANCE;

        $maxScale = $this->getMaxScaleFactor($system);

        foreach ($resolutions as $resolution) {
            $baseW = $resolution['width'];
            $baseH = $resolution['height'];

            for ($scale = 1; $scale <= $maxScale; $scale++) {
                $expectedW = $baseW * $scale;
                $expectedH = $baseH * $scale;

                if (
                    abs($width - $expectedW) <= $tolerance
                    && abs($height - $expectedH) <= $tolerance
                ) {
                    return true;
                }
            }
        }

        // SMPTE 601 resolutions are checked for an exact match only (no scaling).
        if ($system->has_analog_tv_output) {
            foreach (self::SMPTE_601_RESOLUTIONS as $smpte) {
                if ($width === $smpte['width'] && $height === $smpte['height']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function buildResolutionMismatchMessage(
        string $subject,
        int $width,
        int $height,
        System $system,
    ): string {
        $formatted = collect($system->screenshot_resolutions)
            ->map(fn (array $r) => "{$r['width']}x{$r['height']}")
            ->join(', ');

        $multiplesNote = $system->supports_upscaled_screenshots ? ' (or 2x/3x integer multiples)' : '';

        $smpteNote = '';
        if ($system->has_analog_tv_output) {
            $smpteNote = ' SMPTE 601 capture resolutions (704x480, 720x480, 720x486, 704x576, 720x576) are also accepted.';
        }

        return "{$subject} ({$width}x{$height}) doesn't match the expected resolutions for {$system->name}: {$formatted}{$multiplesNote}.{$smpteNote}";
    }

    private function getMaxScaleFactor(System $system): int
    {
        return $system->supports_upscaled_screenshots
            ? self::MAX_SCALE_FACTOR_UPSCALED
            : self::MAX_SCALE_FACTOR_DEFAULT;
    }
}
