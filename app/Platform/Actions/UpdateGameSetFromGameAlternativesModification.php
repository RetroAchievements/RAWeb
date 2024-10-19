<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAlternative;
use App\Models\GameSet;
use App\Models\System;
use App\Platform\Enums\GameSetType;
use Illuminate\Support\Carbon;

class UpdateGameSetFromGameAlternativesModification
{
    public function execute(
        int $parentGameId,
        int $childGameId,
        bool $isAttaching = true,
        ?GameAlternative $existingGameAlt = null,
    ): void {
        $parentGame = Game::find($parentGameId);
        $childGame = Game::find($childGameId);

        // Prioritize hubs being the parent when one of the games is a hub.
        // Determine if a swap is needed to ensure the hub is always the parent or consistent ordering.
        if ($childGame->ConsoleID === System::Hubs) {
            $temp = $parentGame;
            $parentGame = $childGame;
            $childGame = $temp;
        } elseif ($parentGame->ConsoleID !== System::Hubs && $parentGame->id > $childGame->id) {
            $temp = $parentGame;
            $parentGame = $childGame;
            $childGame = $temp;
        }

        $parentGameSet = GameSet::firstWhere('game_id', $parentGame->id) ?? $this->instantiateGameSetFromGame($parentGame);
        $childGameSet = GameSet::firstWhere('game_id', $childGame->id) ?? $this->instantiateGameSetFromGame($childGame);

        $isHubLink = $parentGame->ConsoleID === System::Hubs && $childGame->ConsoleID === System::Hubs;
        $isSimilarGamesLink = $parentGame->ConsoleID !== System::Hubs && $childGame->ConsoleID !== System::Hubs;

        // Use provided timestamps from $existingGameAlt if they're available, otherwise default to now.
        $createdAt = $existingGameAlt?->Created ?? $existingGameAlt?->Updated ?? Carbon::now();
        $updatedAt = $existingGameAlt?->Updated ?? Carbon::now();

        if ($isAttaching) {
            if (
                !$parentGameSet->links()->where('child_game_set_id', $childGameSet->id)->exists()
                && $isHubLink
            ) {
                $parentGameSet->links()->attach($childGameSet->id, [
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ]);
            } else {
                // Ensure bi-directionality for non-hub games.
                if (!$parentGameSet->games()->where('game_id', $childGame->id)->exists()) {
                    $parentGameSet->games()->attach($childGame->id, [
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]);
                }

                if (
                    !$childGameSet->games()->where('game_id', $parentGame->id)->exists()
                    && $isSimilarGamesLink
                ) {
                    $childGameSet->games()->attach($parentGame->id, [
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]);
                }
            }
        } else {
            if ($isHubLink) {
                $parentGameSet->links()->detach($childGameSet->id);
            } else {
                // Ensure bi-directionality for non-hub games.
                $parentGameSet->games()->detach($childGame->id);
                if ($isSimilarGamesLink) {
                    $childGameSet->games()->detach($parentGame->id);
                }
            }
        }
    }

    private function instantiateGameSetFromGame(Game $game): GameSet
    {
        $isGameHub = $game->ConsoleID === System::Hubs;

        $gameSet = GameSet::updateOrCreate(
            ['game_id' => $game->id],
            [
                'title' => $isGameHub ? $game->title : 'Similar Games',
                'type' => $isGameHub ? GameSetType::Hub : GameSetType::SimilarGames,
                'image_asset_path' => $isGameHub ? $game->ImageIcon : null,
            ]
        );

        return $gameSet;
    }
}
