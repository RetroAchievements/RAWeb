<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\GameHash;

class IdentifyGameHashAction
{
    public const IncompatibleIdBase = 1_000_000_000;
    public const UntestedIdBase = 1_100_000_000;
    public const PatchRequiredIdBase = 1_200_000_000;

    public function execute(string $md5): int
    {
        $gameHash = GameHash::firstWhere('md5', $md5);
        if (!$gameHash) {
            return 0;
        }

        switch ($gameHash->compatibility) {
            case GameHashCompatibility::Compatible:
                return $gameHash->game_id;
            case GameHashCompatibility::Incompatible:
                return $gameHash->game_id + IdentifyGameHashAction::IncompatibleIdBase;
            case GameHashCompatibility::Untested:
                return $gameHash->game_id + IdentifyGameHashAction::UntestedIdBase;
            case GameHashCompatibility::PatchRequired:
                return $gameHash->game_id + IdentifyGameHashAction::PatchRequiredIdBase;
            default:
                return 0;
        }
    }

    public static function makeVirtualGameHash(int $gameId): ?GameHash
    {
        $compatibility = GameHashCompatibility::Compatible;

        if ($gameId > IdentifyGameHashAction::PatchRequiredIdBase) {
            $gameId -= IdentifyGameHashAction::PatchRequiredIdBase;
            $compatibility = GameHashCompatibility::PatchRequired;
        } elseif ($gameId > IdentifyGameHashAction::UntestedIdBase) {
            $gameId -= IdentifyGameHashAction::UntestedIdBase;
            $compatibility = GameHashCompatibility::Untested;
        } elseif ($gameId > IdentifyGameHashAction::IncompatibleIdBase) {
            $gameId -= IdentifyGameHashAction::IncompatibleIdBase;
            $compatibility = GameHashCompatibility::Incompatible;
        }

        $game = Game::find($gameId);
        if (!$game) {
            return null;
        }

        return new GameHash([
            'game_id' => $gameId,
            'system_id' => $game->system_id,
            'compatibility' => $compatibility,
            'md5' => '',
            'name' => 'hash_' . $game->id,
            'description' => 'hash_' . $game->id,
        ]);
    }
}
