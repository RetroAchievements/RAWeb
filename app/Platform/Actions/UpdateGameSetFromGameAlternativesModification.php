<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Platform\Enums\GameSetType;

class UpdateGameSetFromGameAlternativesModification
{
    public function execute(int $parentGameId, int $childGameId, bool $isAttaching = true): void
    {
        $parentGame = Game::find($parentGameId);
        $childGame = Game::find($childGameId);

        // Determine if a swap is needed to ensure the hub is always the parent or consistent ordering.
        if (
            ($parentGame->ConsoleID !== System::Hubs && $childGame->ConsoleID === System::Hubs)
            || ($parentGameId > $childGameId)
        ) {
            // Swap to ensure the hub is the parent, or ensure consistent ordering.
            $temp = $parentGame;
            $parentGame = $childGame;
            $childGame = $temp;
        }

        $parentGameSet = GameSet::firstWhere('game_id', $parentGameId);
        if (!$parentGameSet) {
            $parentGameSet = GameSet::updateOrCreate(
                ['game_id' => $parentGame->id],
                [
                    'title' => $parentGame->title,
                    'type' => $parentGame->ConsoleID === System::Hubs ? GameSetType::HUB : GameSetType::SIMILAR_GAMES,
                ]
            );
        }

        $childGameSet = GameSet::firstWhere('game_id', $childGameId);

        if ($isAttaching) {
            if ($parentGame->ConsoleID === System::Hubs && $childGame->ConsoleID === System::Hubs) {
                $parentGameSet->links()->attach($childGameSet->id);
            } else {
                // Ensure bi-directionality for non-hub games.
                $parentGameSet->games()->attach($childGame->id);
                if ($parentGame->ConsoleID !== System::Hubs && $childGame->ConsoleID !== System::Hubs) {
                    $childGameSet->games()->attach($parentGame->id);
                }
            }
        } else {
            if ($parentGame->ConsoleID === System::Hubs && $childGame->ConsoleID === System::Hubs) {
                $parentGameSet->links()->detach($childGameSet->id);
            } else {
                // Ensure bi-directionality for non-hub games.
                $parentGameSet->games()->detach($childGame->id);
                if ($parentGame->ConsoleID !== System::Hubs && $childGame->ConsoleID !== System::Hubs) {
                    $childGameSet->games()->detach($parentGame->id);
                }
            }
        }
    }
}
