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

        $parentGameSet = GameSet::firstWhere('game_id', $parentGameId);
        if (!$parentGameSet) {
            if ($parentGame->ConsoleID === System::Hubs) {
                $parentGameSet = GameSet::updateOrCreate(
                    ['game_id' => $parentGame->id],
                    ['title' => $parentGame->title, 'type' => GameSetType::HUB]
                );
            } else {
                $parentGameSet = GameSet::updateOrCreate(
                    ['game_id' => $parentGame->id],
                    ['type' => GameSetType::GAME]
                );
            }
        }

        if ($parentGame->ConsoleID === System::Hubs || $childGame->ConsoleID === System::Hubs) {
            $childGameSet = GameSet::firstWhere('game_id', $childGameId);

            if ($isAttaching) {
                $parentGameSet->links()->attach($childGameSet->id);
            } else {
                $parentGameSet->links()->detach($childGameSet->id);
            }
        } else {
            if ($isAttaching) {
                $parentGameSet->games()->attach($childGameId);
            } else {
                $parentGameSet->games()->detach($childGameId);
            }
        }
    }
}
