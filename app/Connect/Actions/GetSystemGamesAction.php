<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;
use App\Support\Cache\CacheKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GetSystemGamesAction extends BaseApiAction
{
    protected int $systemId;

    public function execute(?int $systemId): array
    {
        $this->systemId = $systemId ?? 0;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!request()->has('s')) {
            return $this->missingParameters();
        }

        $this->systemId = request()->integer('s', 0);

        return null;
    }

    protected function process(): array
    {
        if (!System::where('id', $this->systemId)->exists()) {
            return $this->resourceNotFound('system');
        }

        // only refresh the data once every 15 minutes
        // allow it to fall out of the cache if it hasn't been used in 60 minutes
        $games = Cache::flexible(CacheKey::buildSystemGamesListCacheKey($this->systemId),
            [15 * 60, 60 * 60],
            fn () => $this->buildSystemGamesList());

        return [
            'Success' => true,
            'Response' => $games,
        ];
    }

    private function buildSystemGamesList(): array
    {
        $results = [];

        $games = Game::query()
            ->where('system_id', $this->systemId)
            ->orderBy('sort_title')
            ->with('hashes')
            ->get();

        $leaderboardCounts = Leaderboard::query()
            ->selectRaw('game_id, COUNT(*) as NumLBs')
            ->whereIn('game_id', $games->pluck('id'))
            ->groupBy('game_id')
            ->pluck('NumLBs', 'game_id') // return mapping of game_id => NumLBs
            ->toArray();

        foreach ($games as $game) {
            $result = [
                'ID' => $game->id,
                'Title' => $game->title,
                'ImageIcon' => $game->image_icon_asset_path,
                'ImageUrl' => $game->badge_url,
                'NumAchievements' => $game->achievements_published ?? 0,
                'NumLeaderboards' => $leaderboardCounts[$game->id] ?? 0,
                'Points' => $game->points_total ?? 0,
                'SupportedHashes' => [],
            ];

            $unsupportedHashes = [];
            foreach ($game->hashes as $hash) {
                if ($hash->compatibility === GameHashCompatibility::Compatible) {
                    $result['SupportedHashes'][] = strtolower($hash->md5);
                } else {
                    $unsupportedHashes[] = strtolower($hash->md5);
                }
            }

            if (!empty($unsupportedHashes)) {
                $result['UnsupportedHashes'] = $unsupportedHashes;
            }

            $results[] = $result;
        }

        return $results;
    }
}
