<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Platform\Services\VirtualGameIdService;

class BuildIncompatiblePatchDataAction
{
    public function execute(Game $game, GameHashCompatibility $gameHashCompatibility): array
    {
        $seeSupportedGameFiles = 'See the Supported Game Files page for this game to find a compatible version.';

        return [
            'Success' => true,
            'PatchData' => [
                'ID' => VirtualGameIdService::encodeVirtualGameId($game->id, $gameHashCompatibility),
                'Title' => 'Unsupported Game Version',
                'ConsoleID' => $game->ConsoleID,
                'ImageIcon' => $game->ImageIcon,
                'ImageIconURL' => media_asset($game->ImageIcon),
                'Achievements' => [
                    (new CreateWarningAchievementAction())->execute(
                        title: 'Unsupported Game Version',
                        description: match ($gameHashCompatibility) {
                            GameHashCompatibility::Incompatible => "This version of the game is known to not work with the defined achievements. {$seeSupportedGameFiles}",
                            GameHashCompatibility::Untested => "This version of the game has not been tested to see if it works with the defined achievements. {$seeSupportedGameFiles}",
                            GameHashCompatibility::PatchRequired => "This version of the game requires a patch to support achievements. {$seeSupportedGameFiles}",
                            default => $seeSupportedGameFiles,
                        }),
                ],
                'Leaderboards' => [],
            ],
        ];
    }
}
