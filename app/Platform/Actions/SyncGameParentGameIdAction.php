<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Support\Facades\DB;

class SyncGameParentGameIdAction
{
    public function execute(Game|int $game): ?int
    {
        if (!$game instanceof Game) {
            // The resolver only reads id/title/system_id; the UPDATE below uses
            // DB::table so we never save the loaded model.
            $game = Game::withTrashed()
                ->select(['id', 'title', 'system_id'])
                ->find($game);

            if ($game === null) {
                return null;
            }
        }

        $parentGameId = $this->resolveParentGameId($game);

        DB::table('games')
            ->where('id', $game->id)
            ->update(['parent_game_id' => $parentGameId]);

        $game->setAttribute('parent_game_id', $parentGameId);
        $game->syncOriginalAttribute('parent_game_id');

        return $parentGameId;
    }

    private function resolveParentGameId(Game $game): ?int
    {
        $coreAchievementSetIds = GameAchievementSet::query()
            ->where('game_id', $game->id)
            ->where('type', AchievementSetType::Core)
            ->pluck('achievement_set_id');

        if ($coreAchievementSetIds->isEmpty()) {
            return null;
        }

        $parentGameId = GameAchievementSet::query()
            ->whereIn('achievement_set_id', $coreAchievementSetIds)
            ->where('game_id', '!=', $game->id)
            ->where('type', '!=', AchievementSetType::Core)
            ->orderBy('created_at')
            ->orderBy('id')
            ->value('game_id');

        if ($parentGameId !== null) {
            return (int) $parentGameId;
        }

        $index = mb_strpos($game->title ?? '', '[Subset - ');
        if ($index === false) {
            return null;
        }

        $baseSetTitle = trim(mb_substr($game->title ?? '', 0, $index));
        if ($baseSetTitle === '') {
            return null;
        }

        $parentGameId = Game::query()
            ->where('title', $baseSetTitle)
            ->where('system_id', $game->system_id)
            ->value('id');

        return $parentGameId !== null ? (int) $parentGameId : null;
    }
}
