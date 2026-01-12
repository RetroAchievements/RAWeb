<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Models\Game;
use App\Platform\Services\VirtualGameIdService;

class LegacyGetPatchAction extends GetAchievementSetsAction
{
    protected function process(): array
    {
        // The legacy endpoint only supports returning one game.
        // If a hash is provided, resolve the hash to a game id.
        if ($this->gameHashMd5) {
            if ($this->gameId === 0) {
                $this->gameId = VirtualGameIdService::idFromHash($this->gameHashMd5);
            }

            $this->gameHashMd5 = null;
        }

        // Build the response
        $response = parent::process();

        // If an error occurred, return it unmodified
        if ($response['Success'] !== true) {
            return $response;
        }

        // We have to relookup the game record for the image_icon_asset_path
        [$resolvedGameId, $compatibility] = VirtualGameIdService::decodeVirtualGameId($response['GameId']);
        $game = Game::find($resolvedGameId);

        // Convert the response to the legacy format
        $legacyResponse = [
            'Success' => true,
            'PatchData' => [
                'ID' => $response['GameId'],
                'Title' => $response['Title'],
                'ConsoleID' => $response['ConsoleId'],
                'ImageIcon' => $game->image_icon_asset_path,
                'ImageIconURL' => $response['ImageIconUrl'],
                'Achievements' => [],
                'Leaderboards' => [],
            ],
        ];

        if (!empty($response['Sets'])) {
            foreach ($response['Sets'][0]['Achievements'] as $achievement) {
                $legacyResponse['PatchData']['Achievements'][] = [
                    'ID' => $achievement['ID'],
                    'Title' => $achievement['Title'],
                    'Description' => $achievement['Description'],
                    'MemAddr' => $achievement['MemAddr'],
                    'Points' => $achievement['Points'],
                    'Author' => $achievement['Author'],
                    'Modified' => $achievement['Modified'],
                    'Created' => $achievement['Created'],
                    'BadgeName' => $achievement['BadgeName'],
                    'Flags' => $achievement['Flags'],
                    'Type' => $achievement['Type'],
                    'Rarity' => $achievement['Rarity'],
                    'RarityHardcore' => $achievement['RarityHardcore'],
                    'BadgeURL' => $achievement['BadgeURL'],
                    'BadgeLockedURL' => $achievement['BadgeLockedURL'],
                ];
            }

            foreach ($response['Sets'][0]['Leaderboards'] as $leaderboard) {
                $legacyResponse['PatchData']['Leaderboards'][] = [
                    'ID' => $leaderboard['ID'],
                    'Mem' => $leaderboard['Mem'],
                    'Format' => $leaderboard['Format'],
                    'LowerIsBetter' => $leaderboard['LowerIsBetter'],
                    'Title' => $leaderboard['Title'],
                    'Description' => $leaderboard['Description'],
                    'Hidden' => $leaderboard['Hidden'],
                ];
            }
        }

        // The legacy API returns the GameID as ParentID for all supported hashes (multiset preparation before creating a new API).
        // RichPresencePatch may not be returned for unsupported hashes.
        // So if RichPresencePatch is not being returned, also don't return ParentID.
        if (array_key_exists('RichPresencePatch', $response)) {
            $legacyResponse['PatchData']['RichPresencePatch'] = $response['RichPresencePatch'];
            $legacyResponse['PatchData']['ParentID'] = $response['GameId'];
        }

        // Propagate any warning that may have been returned.
        if (array_key_exists('Warning', $response)) {
            $legacyResponse['Warning'] = $response['Warning'];
        }

        return $legacyResponse;
    }
}
