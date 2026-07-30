<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Enums\GenreCapWarningSubject;
use App\Models\Game;
use App\Models\GameSet;
use App\Platform\Actions\AttachGamesToHubsAction;

class BuildGenreCapWarningAction
{
    /**
     * @param int[] $skippedIds
     */
    public function execute(array $skippedIds, GenreCapWarningSubject $subject): ?string
    {
        if (empty($skippedIds)) {
            return null;
        }

        $cap = AttachGamesToHubsAction::MAX_GENRE_HUBS;

        return match ($subject) {
            GenreCapWarningSubject::Hubs => "This game is already at the {$cap}-genre-hub cap, so these hubs were not added: "
                . GameSet::whereIn('id', $skippedIds)->orderBy('title')->pluck('title')->implode(', ')
                . '. Remove a genre hub before adding more.',

            GenreCapWarningSubject::Games => "These games are already at the {$cap}-genre-hub cap and were not added: "
                . Game::whereIn('id', $skippedIds)->orderBy('title')->pluck('title')->implode(', ')
                . '.',
        };
    }
}
