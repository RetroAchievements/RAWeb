<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\AwardType;
use App\Models\System;
use App\Platform\Actions\GetAwardTimeTakenAction;
use App\Platform\Enums\UnlockMode;

class PlayerProgressionService
{
    public function buildPrimaryCountsMetrics(array $filteredAndJoinedGamesList, ?int $consoleId = null): array
    {
        if ($consoleId) {
            $filteredAndJoinedGamesList = array_filter(
                $filteredAndJoinedGamesList,
                fn ($game) => isset($game['ConsoleID']) && $game['ConsoleID'] == $consoleId
            );
        }

        $metrics = [
            'numPlayed' => 0,
            'numUnfinished' => 0,
            'numBeatenSoftcore' => 0,
            'numBeatenHardcore' => 0,
            'numCompleted' => 0,
            'numMastered' => 0,
        ];

        $metrics['numPlayed'] = count($filteredAndJoinedGamesList);

        foreach ($filteredAndJoinedGamesList as $game) {
            if (!isset($game['HighestAwardKind'])) {
                $metrics['numUnfinished']++;
            } elseif ($game['HighestAwardKind'] === 'beaten-softcore') {
                $metrics['numBeatenSoftcore']++;
            } elseif ($game['HighestAwardKind'] === 'beaten-hardcore') {
                $metrics['numBeatenHardcore']++;
            } elseif ($game['HighestAwardKind'] === 'completed') {
                $metrics['numCompleted']++;
            } elseif ($game['HighestAwardKind'] === 'mastered') {
                $metrics['numMastered']++;
            }
        }

        return $metrics;
    }

    public function filterAndJoinGames(array $gamesList, array $siteAwards, int $userId, bool $allowEvents = false): array
    {
        /**
         * We need to append the most prestigious award kind+date to the game entities,
         * and we need to do it extremely fast. We'll [A] prepare some lookup tables,
         * then [B] iterate once while appending the entities with constant time O(1).
         * We also need to [C] add rows for games with awards but no progress (this edge
         * case can happen when sets are completely demoted).
         */
        $processedGameIds = [];
        $allAwardsByGameId = [];

        // [A] Prepare the lookup tables.
        $awardsLookup = [];
        $awardsDateLookup = [];
        $hasMasteryAwardLookup = [];

        foreach ($siteAwards as $award) {
            $key = $award['AwardData']; // Game ID

            $awardKinds = [
                AwardType::GameBeaten => [
                    UnlockMode::Softcore => 'beaten-softcore',
                    UnlockMode::Hardcore => 'beaten-hardcore',
                ],
                AwardType::Mastery => [
                    UnlockMode::Softcore => 'completed',
                    UnlockMode::Hardcore => 'mastered',
                ],
            ];
            $awardKind = $awardKinds[$award['AwardType']][$award['AwardDataExtra']] ?? '';

            if (in_array($awardKind, ['beaten-softcore', 'beaten-hardcore'])) {
                // Check if a higher-ranked award ('completed' or 'mastered') is already present.
                if (empty($awardsLookup[$key]) || !in_array($awardsLookup[$key], ['completed', 'mastered'])) {
                    $awardsLookup[$key] = $awardKind;
                    $awardsDateLookup[$key] = $award['AwardedAt'];
                }
            } elseif (in_array($awardKind, ['completed', 'mastered'])) {
                $awardsLookup[$key] = $awardKind;
                $awardsDateLookup[$key] = $award['AwardedAt'];
                $hasMasteryAwardLookup[$key] = true;
            }

            if ($awardKind !== '') {
                $allAwardsByGameId[$key][] = $awardKind;
            }
        }

        $validConsoleIds = getValidConsoleIds();
        $usedConsoleIds = [];

        // [B] Iterate once while appending the entities with constant time O(1).
        $awardGames = [];
        $filteredAndJoined = [];
        foreach ($gamesList as &$game) {
            $canUseGame = (
                $game['NumAwarded'] !== 0
                && ($allowEvents ? true : $game['ConsoleID'] !== System::Events)
                && in_array($game['ConsoleID'], $validConsoleIds)
            );

            if ($canUseGame) {
                if (!in_array($game['ConsoleID'], $usedConsoleIds)) {
                    $usedConsoleIds[] = $game['ConsoleID'];
                }

                if (isset($awardsLookup[$game['GameID']])) {
                    $game['HighestAwardKind'] = $awardsLookup[$game['GameID']];
                    $game['HighestAwardDate'] = $awardsDateLookup[$game['GameID']];
                    $game['AllAwardKinds'] = $allAwardsByGameId[$game['GameID']];

                    // Check if the game has been beaten but not mastered.
                    if (
                        $game['HighestAwardKind'] != 'mastered'
                        && $game['HighestAwardKind'] != 'completed'
                        && !isset($hasMasteryAwardLookup[$game['GameID']])
                    ) {
                        $game['HasNoAssociatedMasteryAward'] = true;
                    }

                    if (!array_key_exists($game['HighestAwardKind'], $awardGames)) {
                        $awardGames[$game['HighestAwardKind']] = [];
                    }
                    $awardGames[$game['HighestAwardKind']][] = $game['GameID'];
                }

                $filteredAndJoined[] = $game;
                $processedGameIds[$game['GameID']] = true;
            }
        }

        // [C] Add in console names
        $systems = System::whereIn('id', $usedConsoleIds)->get();
        foreach ($filteredAndJoined as &$game) {
            $system = $systems->where('id', $game['ConsoleID'])->first();
            if ($system) {
                $game['ConsoleName'] = $system->name;
                $game['ConsoleNameShort'] = $system->name_short;
            }
        }

        // [D] Add in times to earn awards
        foreach ($awardGames as $kind => $gameIds) {
            $times = (new GetAwardTimeTakenAction())->execute($userId, $gameIds, $kind);

            foreach ($filteredAndJoined as &$game) {
                if (array_key_exists('HighestAwardKind', $game) && $game['HighestAwardKind'] === $kind) {
                    $game['HighestAwardTimeTaken'] = $times[$game['GameID']] ?? null;
                }
            }
        }

        // [E] Add rows for games with awards but no progress.
        foreach ($awardsLookup as $gameId => $awardKind) {
            $alreadyProcessed = false;
            foreach ($gamesList as &$game) {
                if ($game['GameID'] == $gameId) {
                    $alreadyProcessed = true;
                    break;
                }
            }

            if (!$alreadyProcessed && !isset($processedGameIds[$gameId])) {
                $searchResults = array_filter($siteAwards, fn ($award) => $award['AwardData'] == $gameId);

                $award = null;
                if (!empty($searchResults)) {
                    $award = current($searchResults);
                }

                if (
                    $award
                    && ($allowEvents ? true : $award['ConsoleID'] !== System::Events)
                    && in_array($award['ConsoleID'], $validConsoleIds)
                ) {
                    $system = $systems->where('id', $award['ConsoleID'])->first();
                    if (!$system) {
                        $system = System::find($award['ConsoleID']);
                    }

                    $newGame = [
                        'GameID' => $gameId,
                        'ConsoleID' => $award['ConsoleID'],
                        'ConsoleName' => $system?->name,
                        'ConsoleNameShort' => $system?->name_short,
                        'Title' => $award['Title'],
                        'SortTitle' => $award['Title'],
                        'HighestAwardKind' => $awardKind,
                        'HighestAwardDate' => $awardsDateLookup[$gameId],
                        'AllAwardKinds' => $allAwardsByGameId[$gameId],
                        'MostRecentWonDate' => date('Y-m-d H:i:s', $award['AwardedAt']),
                        'ImageIcon' => $award['ImageIcon'],
                        'NumAwarded' => 0,
                        'MaxPossible' => 0,
                        'PctWonHC' => 0,
                        'PctWon' => 1,
                        'FirstWonDate' => null,
                    ];

                    $filteredAndJoined[] = $newGame;
                    $processedGameIds[$gameId] = true;
                }
            }
        }

        return $filteredAndJoined;
    }

    public function useSystemId(int $targetSystemId, array $userGamesList, array $userSiteAwards): array
    {
        if (!isValidConsoleId($targetSystemId)) {
            return [$userGamesList, $userSiteAwards];
        }

        $filteredGamesList = array_filter($userGamesList, fn ($game) => $game['ConsoleID'] == $targetSystemId);
        $filteredSiteAwards = array_filter($userSiteAwards, fn ($award) => $award['ConsoleID'] == $targetSystemId);

        return [$filteredGamesList, $filteredSiteAwards];
    }
}
