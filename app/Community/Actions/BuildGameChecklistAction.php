<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\GameGroupData;
use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Data\GameData;
use App\Platform\Data\GameListEntryData;
use App\Platform\Data\PlayerGameData;

class BuildGameChecklistAction
{
    public function execute(
        ?string $encoded,
        User $user,
    ): array {
        $groups = [];
        foreach (explode('|', $encoded ?? '') as $group) {
            if (!empty($group)) {
                $groups[] = $this->parseGroup($group);
            }
        }

        return $this->fillData($groups, $user);
    }

    private function parseGroup(string $group): array
    {
        $index = strrpos($group, ':');
        if ($index === false) {
            $header = '';
            $ids = $group;
        } else {
            $header = substr($group, 0, $index);
            $ids = substr($group, $index + 1);
        }

        $gameIds = [];
        foreach (explode(',', $ids) as $id) {
            $gameIds[] = (int) $id;
        }

        return [
            'header' => $header,
            'gameIds' => $gameIds,
        ];
    }

    /**
     * @return GameGroupData[]
     */
    private function fillData(array $groups, User $user): array
    {
        $ids = [];
        foreach ($groups as $group) {
            $ids = array_merge($ids, $group['gameIds']);
        }
        $ids = array_unique($ids);

        $games = Game::whereIn('id', $ids)->with('system')->get();
        $playerGames = PlayerGame::where('user_id', $user->id)->whereIn('game_id', $ids)->get();
        $gameBadges = PlayerBadge::where('user_id', $user->id)
            ->whereIn('award_type', [AwardType::Mastery, AwardType::GameBeaten])
            ->whereIn('award_key', $ids)
            ->get();

        $result = [];
        foreach ($groups as $group) {
            $masteredCount = 0;
            $completedCount = 0;
            $beatenCount = 0;
            $beatenSoftcoreCount = 0;

            $gameList = [];
            foreach ($group['gameIds'] as $gameId) {
                $game = $games->filter(fn ($g) => $g->ID === $gameId)->first();
                if ($game) {
                    $playerGame = $playerGames->filter(fn ($pg) => $pg->game_id === $gameId)->first();
                    if ($playerGame) {
                        if ($playerGame->completed_hardcore_at) {
                            $masteredCount++;
                        } elseif ($playerGame->completed_at) {
                            $completedCount++;
                        } else {
                            $gameBadge = $gameBadges->filter(fn ($gb) => $gb->award_key === $gameId && $gb->award_type === AwardType::Mastery)->first();
                            if ($gameBadge) {
                                if ($gameBadge->award_tier) {
                                    $masteredCount++;
                                    $playerGame->completed_hardcore_at = $gameBadge->awarded_at;
                                } else {
                                    $completedCount++;
                                    $playerGame->completed_at = $gameBadge->awarded_at;
                                }
                            }
                        }

                        if ($playerGame->beaten_hardcore_at) {
                            $beatenCount++;
                        } elseif ($playerGame->beaten_at) {
                            $beatenSoftcoreCount++;
                        } else {
                            $gameBadge = $gameBadges->filter(fn ($gb) => $gb->award_key === $gameId && $gb->award_type === AwardType::GameBeaten)->first();
                            if ($gameBadge) {
                                if ($gameBadge->award_tier) {
                                    $beatenCount++;
                                    $playerGame->beaten_hardcore_at = $gameBadge->awarded_at;
                                } else {
                                    $beatenSoftcoreCount++;
                                    $playerGame->beaten_at = $gameBadge->awarded_at;
                                }
                            }
                        }
                    }

                    $gameList[] = new GameListEntryData(
                        GameData::fromGame($game)->include(
                            'achievementsPublished',
                            'badgeUrl',
                            'system.iconUrl',
                            'system.nameShort',
                        ),
                        $playerGame ? PlayerGameData::fromPlayerGame($playerGame) : null,
                        null,
                    );
                }
            }

            $result[] = new GameGroupData(
                $group['header'],
                $masteredCount,
                $completedCount,
                $beatenCount,
                $beatenSoftcoreCount,
                $gameList
            );
        }

        return $result;
    }
}
