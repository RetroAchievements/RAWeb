<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Game;
use App\Platform\Actions\SyncGameParentGameIdAction;

class GameObserver
{
    public function created(Game $game): void
    {
        $this->syncTitleFallbackSubsetsOf($game); // match against existing subset-titled games if this is a new parent
    }

    public function updated(Game $game): void
    {
        if (!$game->wasChanged(['title', 'system_id'])) {
            return;
        }

        (new SyncGameParentGameIdAction())->execute($game);

        $previousSystemId = $game->getOriginal('system_id');
        $this->syncTitleFallbackSubsetsOf(
            $game,
            previousTitle: $game->getOriginal('title'),
            previousSystemId: $previousSystemId !== null ? (int) $previousSystemId : null,
        );
    }

    public function deleted(Game $game): void
    {
        $this->syncTitleFallbackSubsetsOf($game);
    }

    public function restored(Game $game): void
    {
        (new SyncGameParentGameIdAction())->execute($game);
        $this->syncTitleFallbackSubsetsOf($game);
    }

    /**
     * Re-sync any subset games whose parent_game_id was inferred from a "[Subset - X]"
     * title pointing at this game (current and/or previous title/system pairing).
     */
    private function syncTitleFallbackSubsetsOf(
        Game $parentGame,
        ?string $previousTitle = null,
        ?int $previousSystemId = null,
    ): void {
        $candidates = [];

        if (filled($parentGame->title) && $parentGame->system_id !== null) {
            $candidates[] = [$parentGame->title, $parentGame->system_id];
        }

        if (
            filled($previousTitle)
            && $previousSystemId !== null
            && ($previousTitle !== $parentGame->title || $previousSystemId !== $parentGame->system_id)
        ) {
            $candidates[] = [$previousTitle, $previousSystemId];
        }

        if ($candidates === []) {
            return;
        }

        $syncAction = new SyncGameParentGameIdAction();

        foreach ($candidates as [$title, $systemId]) {
            $escapedTitle = addcslashes((string) $title, '\\%_');

            Game::withTrashed()
                ->where('id', '!=', $parentGame->id)
                ->where('system_id', $systemId)
                ->where('title', 'like', $escapedTitle . ' [Subset - %')
                ->pluck('id')
                ->each(fn (int $gameId) => $syncAction->execute($gameId));
        }
    }
}
