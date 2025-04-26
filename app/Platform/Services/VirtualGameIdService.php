<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\GameHash;

class VirtualGameIdService
{
    public const IncompatibleIdBase = 1_000_000_000;
    public const UntestedIdBase = 1_100_000_000;
    public const PatchRequiredIdBase = 1_200_000_000;

    public static function idFromHash(string $md5): int
    {
        $gameHash = GameHash::firstWhere('md5', $md5);
        if (!$gameHash) {
            return 0;
        }

        return VirtualGameIdService::encodeVirtualGameId($gameHash->game_id, $gameHash->compatibility);
    }

    public static function encodeVirtualGameId(int $gameId, GameHashCompatibility $compatibility): int
    {
        switch ($compatibility) {
            case GameHashCompatibility::Compatible:
                return $gameId;
            case GameHashCompatibility::Incompatible:
                return $gameId + VirtualGameIdService::IncompatibleIdBase;
            case GameHashCompatibility::Untested:
                return $gameId + VirtualGameIdService::UntestedIdBase;
            case GameHashCompatibility::PatchRequired:
                return $gameId + VirtualGameIdService::PatchRequiredIdBase;
            default:
                return 0;
        }
    }

    public static function isVirtualGameId(int $virtualGameId): bool
    {
        return $virtualGameId > VirtualGameIdService::IncompatibleIdBase;
    }

    public static function decodeVirtualGameId(int $virtualGameId): array
    {
        if ($virtualGameId > VirtualGameIdService::PatchRequiredIdBase) {
            $virtualGameId -= VirtualGameIdService::PatchRequiredIdBase;

            return [$virtualGameId, GameHashCompatibility::PatchRequired];
        }

        if ($virtualGameId > VirtualGameIdService::UntestedIdBase) {
            $virtualGameId -= VirtualGameIdService::UntestedIdBase;

            return [$virtualGameId, GameHashCompatibility::Untested];
        }

        if ($virtualGameId > VirtualGameIdService::IncompatibleIdBase) {
            $virtualGameId -= VirtualGameIdService::IncompatibleIdBase;

            return [$virtualGameId, GameHashCompatibility::Incompatible];
        }

        return [$virtualGameId, GameHashCompatibility::Compatible];
    }

    public static function makeVirtualGameHash(int $virtualGameId): ?GameHash
    {
        [$gameId, $compatibility] = VirtualGameIdService::decodeVirtualGameId($virtualGameId);

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
