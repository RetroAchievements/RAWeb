<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameRelease;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Platform\Enums\GameSetType;
use App\Platform\Enums\ReleasedAtGranularity;

class SortGamesIntoHubsAction
{
    public function execute(array $gameIds, array $hubIds, array $sorts): array
    {
        $remainingGameIds = $gameIds;
        $sortedGameIds = [];

        $this->hubGameIds = GameSetGame::select('game_set_id', 'game_id')
            ->whereIn('game_id', $gameIds)
            ->whereHas('gameSet', function ($query) {
                $query->where('type', GameSetType::Hub);
            })
            ->toBase()
            ->get()
            ->mapToGroups(fn ($item) => [$item->game_set_id => $item->game_id])
            ->toArray();

        $this->seriesHubIds = GameSet::select('id')
            ->whereIn('id', array_keys($this->hubGameIds))
            ->where(function ($query) {
                $query->where('title', 'LIKE', '[Series - %')
                      ->orWhere('title', 'LIKE', '[Subseries - %');
            })
            ->orderBy('sort_title')
            ->toBase()
            ->pluck('id')
            ->toArray();

        $this->gameTitleOrder = Game::select('id')
            ->whereIn('id', $gameIds)
            ->orderBy('sort_title')
            ->toBase()
            ->pluck('id')
            ->toArray();

        $this->gameSeries = [];
        foreach ($this->seriesHubIds as $seriesHubId) {
            foreach ($this->hubGameIds[$seriesHubId] as $gameId) {
                $this->gameSeries[$gameId] = $seriesHubId;
            }
        }
        $this->seriesHubIds[] = 0; // games without a series always sort last

        $this->releaseDates = [];
        if (in_array('releasedAt', $sorts) || in_array('-releasedAt', $sorts)) {
            $releases = GameRelease::select('game_id', 'released_at', 'released_at_granularity')
                ->whereIn('game_id', $gameIds)
                ->whereNotNull('released_at')
                ->orderByDesc('released_at')
                ->toBase()
                ->get();
            foreach ($releases as $release) {
                if ($release->released_at_granularity === ReleasedAtGranularity::Year->value) {
                    $this->releaseDates[$release->game_id] = substr($release->released_at, 0, 4) . '-00-00';
                } elseif ($release->released_at_granularity === ReleasedAtGranularity::Month->value) {
                    $this->releaseDates[$release->game_id] = substr($release->released_at, 0, 7) . '-00';
                } else {
                    $this->releaseDates[$release->game_id] = substr($release->released_at, 0, 10);
                }
            }
        }

        // if a series hub is explicitly in the list, keep those items grouped together
        $seriesGameIds = [];
        foreach ($hubIds as $hubId) {
            if (in_array($hubId, $this->seriesHubIds)) {
                $hubGames = array_intersect($remainingGameIds, $this->hubGameIds[$hubId] ?? []);
                if (!empty($hubGames)) {
                    $seriesGameIds[$hubId] = $hubGames;
                    $remainingGameIds = array_diff($remainingGameIds, $this->hubGameIds[$hubId]);
                }
            }
        }

        foreach ($hubIds as $hubId) {
            $hubGames = $seriesGameIds[$hubId] ?? [];
            if (empty($hubGames)) {
                $hubGames = array_intersect($remainingGameIds, $this->hubGameIds[$hubId] ?? []);
                $additionalHubGames = [];
                foreach ($hubGames as $hubGame) {
                    // if the game is in a series, pull in the rest of the series
                    $seriesId = $this->gameSeries[$hubGame] ?? 0;
                    if ($seriesId > 0 && !in_array($seriesId, $seriesGameIds)) {
                        $additionalGames = array_intersect($remainingGameIds, $this->hubGameIds[$seriesId] ?? []);
                        $additionalHubGames = array_merge($additionalHubGames, $additionalGames);
                        $remainingGameIds = array_diff($remainingGameIds, $additionalGames);
                    }
                }
                if (!empty($additionalHubGames)) {
                    $hubGames = array_merge($hubGames, $additionalHubGames);
                }
            }

            if (!empty($hubGames)) {
                $sortedGameIds = array_merge($sortedGameIds, $this->sortGames($hubGames, $sorts));
                $remainingGameIds = array_diff($remainingGameIds, $hubGames);
            }
        }

        if (!empty($remainingGameIds)) {
            $sortedGameIds = array_merge($sortedGameIds, $this->sortGames($remainingGameIds, $sorts));
        }

        return $sortedGameIds;
    }

    private function sortGames(array $gameIds, array $sorts): array
    {
        usort($gameIds, function ($a, $b) use ($sorts) {
            foreach ($sorts as $sort) {
                switch ($sort) {
                    case 'series':
                        $seriesIndexA = array_search($this->gameSeries[$a] ?? 0, $this->seriesHubIds);
                        $seriesIndexB = array_search($this->gameSeries[$b] ?? 0, $this->seriesHubIds);

                        if ($seriesIndexA !== $seriesIndexB) {
                            return $seriesIndexA <=> $seriesIndexB;
                        }
                        break;

                    case 'title':
                        $sortTitleIndexA = array_search($a, $this->gameTitleOrder);
                        $sortTitleIndexB = array_search($b, $this->gameTitleOrder);

                        if ($sortTitleIndexA !== $sortTitleIndexB) {
                            return $sortTitleIndexA <=> $sortTitleIndexB;
                        }
                        break;

                    case '-title':
                        $sortTitleIndexA = array_search($a, $this->gameTitleOrder);
                        $sortTitleIndexB = array_search($b, $this->gameTitleOrder);

                        if ($sortTitleIndexA !== $sortTitleIndexB) {
                            return $sortTitleIndexB <=> $sortTitleIndexA;
                        }
                        break;

                    case 'releasedAt':
                        $releasedAtA = $this->releaseDates[$a] ?? '9999-99-99';
                        $releasedAtB = $this->releaseDates[$b] ?? '9999-99-99';

                        if ($releasedAtA !== $releasedAtB) {
                            return $releasedAtA <=> $releasedAtB;
                        }
                        break;

                    case '-releasedAt':
                        $releasedAtA = $this->releaseDates[$a] ?? '9999-99-99';
                        $releasedAtB = $this->releaseDates[$b] ?? '9999-99-99';

                        if ($releasedAtA !== $releasedAtB) {
                            return $releasedAtB <=> $releasedAtA;
                        }
                        break;
                }
            }

            $sortTitleIndexA = array_search($a, $this->gameTitleOrder);
            $sortTitleIndexB = array_search($b, $this->gameTitleOrder);

            return $sortTitleIndexA <=> $sortTitleIndexB;
        });

        return $gameIds;
    }
}
