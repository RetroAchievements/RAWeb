<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Platform\Enums\GameSetType;
use App\Platform\Enums\ReleasedAtGranularity;

class SortGamesIntoHubsAction
{
    protected array $hubGameIds;
    protected array $seriesHubIds;
    protected array $gameSeries;
    protected array $gameTitleOrder;
    protected array $releaseDates;
    protected array $badgeDates;

    public function execute(array $gameIds, array $hubIds, array $sorts, ?User $user = null): array
    {
        $remainingGameIds = $gameIds;
        $sortedGameIds = [];

        // Identify all of the hubs associated to the game list. Create a mapping
        // of hub IDs -> list of games in hub.
        $this->hubGameIds = GameSetGame::select('game_set_id', 'game_id')
            ->whereIn('game_id', $gameIds)
            ->whereHas('gameSet', function ($query) {
                $query->where('type', GameSetType::Hub);
            })
            ->toBase()
            ->get()
            ->mapToGroups(fn ($item) => [$item->game_set_id => $item->game_id])
            ->toArray();

        // Determine which of the above hubs are series/subseries hubs.
        // Store the values in order by sort_title so we can sort by series.
        $this->seriesHubIds = GameSet::select('id')
            ->whereIn('id', array_keys($this->hubGameIds))
            ->where(function ($query) {
                $query->where('title', 'LIKE', '[Series - %')
                      ->orWhere('title', 'LIKE', '[Subseries - %');
            })
            ->orderByRaw('SUBSTRING(sort_title, LOCATE("-", sort_title) + 2)')
            ->toBase()
            ->pluck('id')
            ->toArray();

        // For any games in a series/subseries hub, create an inverse map from the game ID to the hub.
        $this->gameSeries = [];
        foreach ($this->seriesHubIds as $seriesHubId) {
            foreach ($this->hubGameIds[$seriesHubId] as $gameId) {
                $this->gameSeries[$gameId] ??= $seriesHubId;
            }
        }
        $this->seriesHubIds[] = 0; // games without a series always sort last

        // Get the alphabetical order of the requested games so we can do alphabetical sorting.
        $this->gameTitleOrder = Game::select('id')
            ->whereIn('id', $gameIds)
            ->orderBy('sort_title')
            ->toBase()
            ->pluck('id')
            ->toArray();

        // If sorting by release date, fetch the oldest release date for each game.
        // Items without a release date will default to '9999-99-99' when sorting.
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

        // If sorting by earn date, fetch the earn dates for each game.
        $this->badgeDates = [];
        if ($user && (in_array('earnedAt', $sorts) || in_array('-earnedAt', $sorts))) {
            $awards = PlayerBadge::select('award_key', 'awarded_at')
                ->where('user_id', $user->id)
                ->whereIn('award_key', $gameIds)
                ->where('award_type', AwardType::Mastery)
                ->orderByDesc('awarded_at')
                ->toBase()
                ->get();
            foreach ($awards as $award) {
                $this->badgeDates[$award->award_key] = $award->awarded_at;
            }
        }

        // If a series hub is explicitly in the hubs list, extract the games from that hub
        // so they don't get greedily grabbed by a genre hub first.
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

        // Process the hubs list. If the hub is a series, just use the games in the hub.
        // Otherwise, if any games in the hub belong to a series and series is a subsort,
        // pull the rest of the series in so they all get grouped together.
        $hasSeriesSort = in_array('series', $sorts);
        foreach ($hubIds as $hubId) {
            $hubGames = $seriesGameIds[$hubId] ?? [];
            if (empty($hubGames)) {
                $hubGames = array_intersect($remainingGameIds, $this->hubGameIds[$hubId] ?? []);
                if ($hasSeriesSort) {
                    $additionalHubGames = [];
                    foreach ($hubGames as $hubGame) {
                        // If the game is in a series, pull in the rest of the series.
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
            }

            if (!empty($hubGames)) {
                $sortedGameIds = array_merge($sortedGameIds, $this->sortGames($hubGames, $sorts));
                $remainingGameIds = array_diff($remainingGameIds, $hubGames);
            }
        }

        // Process anything that wasn't handled by the hubs list.
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
                        $releasedAtA = $this->releaseDates[$a] ?? '0000-00-00';
                        $releasedAtB = $this->releaseDates[$b] ?? '0000-00-00';

                        if ($releasedAtA !== $releasedAtB) {
                            return $releasedAtB <=> $releasedAtA;
                        }
                        break;

                    case 'earnedAt':
                        $earnedAtA = $this->badgeDates[$a] ?? '9999-99-99';
                        $earnedAtB = $this->badgeDates[$b] ?? '9999-99-99';

                        if ($earnedAtA !== $earnedAtB) {
                            return $earnedAtA <=> $earnedAtB;
                        }
                        break;

                    case '-earnedAt':
                        $earnedAtA = $this->badgeDates[$a] ?? '0000-00-00';
                        $earnedAtB = $this->badgeDates[$b] ?? '0000-00-00';

                        if ($earnedAtA !== $earnedAtB) {
                            return $earnedAtB <=> $earnedAtA;
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
