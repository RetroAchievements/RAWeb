<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\User;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Support\Facades\Auth;

/**
 * Writes one `audit_log` row per primary screenshot promotion or demotion,
 * pairing the previous and new primary in a single payload so the audit log
 * page renders an image diff.
 */
class LogPrimaryScreenshotChangeAction
{
    public function execute(
        Game $game,
        ScreenshotType $type,
        ?GameScreenshot $previousPrimary,
        ?GameScreenshot $newPrimary,
        ?User $causer = null,
    ): void {
        /** @var User|null $resolvedCauser */
        $resolvedCauser = $causer ?? Auth::user();

        $previousPath = $this->resolveAssetPath($previousPrimary);
        $newPath = $this->resolveAssetPath($newPrimary);
        $field = $this->payloadField($type);

        $properties = [
            'old' => [
                $field => $previousPath,
            ],
            'attributes' => [
                $field => $newPath,
            ],
        ];

        activity()
            ->causedBy($resolvedCauser)
            ->performedOn($game)
            ->withProperties($properties)
            ->event('primaryScreenshotChanged')
            ->log('primaryScreenshotChanged');
    }

    private function payloadField(ScreenshotType $type): string
    {
        return match ($type) {
            ScreenshotType::Title => 'title_screenshot',
            ScreenshotType::Ingame => 'ingame_screenshot',
            ScreenshotType::Completion => 'completion_screenshot',
        };
    }

    private function resolveAssetPath(?GameScreenshot $screenshot): string
    {
        if (!$screenshot) {
            return Game::PLACEHOLDER_IMAGE_PATH;
        }

        $screenshot->loadMissing('media');

        $legacyPath = $screenshot->media?->getCustomProperty('legacy_path');

        return is_string($legacyPath) && $legacyPath !== ''
            ? $legacyPath
            : Game::PLACEHOLDER_IMAGE_PATH;
    }
}
