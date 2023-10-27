<?php

declare(strict_types=1);

namespace App\Community\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DeveloperGameStatsTable extends Component
{
    private array $easiestGame = [];
    private array $hardestGame = [];
    private array $targetGameIds = [];
    private int $numGamesWithLeaderboards = 0;
    private int $numGamesWithRichPresence = 0;
    private int $numTotalLeaderboards = 0;
    private string $statsKind = 'any'; // 'any' | 'majority' | 'sole'
    private string $targetDeveloperUsername = '';

    public function __construct(
        array $easiestGame,
        array $hardestGame,
        array $targetGameIds,
        int $numGamesWithLeaderboards,
        int $numGamesWithRichPresence,
        int $numTotalLeaderboards,
        string $statsKind,
        string $targetDeveloperUsername,
    ) {
        $this->easiestGame = $easiestGame;
        $this->hardestGame = $hardestGame;
        $this->numGamesWithLeaderboards = $numGamesWithLeaderboards;
        $this->numGamesWithRichPresence = $numGamesWithRichPresence;
        $this->numTotalLeaderboards = $numTotalLeaderboards;
        $this->statsKind = $statsKind;
        $this->targetDeveloperUsername = $targetDeveloperUsername;
        $this->targetGameIds = $targetGameIds;
    }

    public function render(): View
    {
        $builtStats = $this->buildStats($this->targetDeveloperUsername, $this->targetGameIds);

        return view('community.components.developer.game-stats-table', array_merge(
            $builtStats, [
                'easiestGame' => $this->easiestGame,
                'hardestGame' => $this->hardestGame,
                'numGamesWithLeaderboards' => $this->numGamesWithLeaderboards,
                'numGamesWithRichPresence' => $this->numGamesWithRichPresence,
                'numTotalLeaderboards' => $this->numTotalLeaderboards,
                'statsKind' => $this->statsKind,
                'targetDeveloperUsername' => $this->targetDeveloperUsername,
                'targetGameIds' => $this->targetGameIds,
            ],
        ));
    }

    private function buildStats(string $targetDeveloperUsername, array $targetGameIds): array
    {
        $ownAwards = [];
        $mostBeatenSoftcoreGame = $mostBeatenHardcoreGame = $mostCompletedGame = $mostMasteredGame = [];
        $userMostBeatenSoftcore = $userMostBeatenHardcore = $userMostCompleted = $userMostMastered = [];
        $beatenSoftcoreAwards = $beatenHardcoreAwards = $completedAwards = $masteredAwards = 0;

        $mostAwardedGames = getMostAwardedGames($targetGameIds);
        foreach ($mostAwardedGames as $game) {
            $mostBeatenSoftcoreGame = $this->findMost($game, 'BeatenSoftcore', $mostBeatenSoftcoreGame);
            $mostBeatenHardcoreGame = $this->findMost($game, 'BeatenHardcore', $mostBeatenHardcoreGame);
            $mostCompletedGame = $this->findMost($game, 'Completed', $mostCompletedGame);
            $mostMasteredGame = $this->findMost($game, 'Mastered', $mostMasteredGame);
        }

        $mostAwardedUsers = getMostAwardedUsers($targetGameIds);
        foreach ($mostAwardedUsers as $userInfo) {
            $userMostBeatenSoftcore = $this->findMost($userInfo, 'BeatenSoftcore', $userMostBeatenSoftcore);
            $userMostBeatenHardcore = $this->findMost($userInfo, 'BeatenHardcore', $userMostBeatenHardcore);
            $userMostCompleted = $this->findMost($userInfo, 'Completed', $userMostCompleted);
            $userMostMastered = $this->findMost($userInfo, 'Mastered', $userMostMastered);

            if (strcmp($targetDeveloperUsername, $userInfo['User']) == 0) {
                $ownAwards = $userInfo;
            }

            $beatenSoftcoreAwards += $userInfo['BeatenSoftcore'];
            $beatenHardcoreAwards += $userInfo['BeatenHardcore'];
            $completedAwards += $userInfo['Completed'];
            $masteredAwards += $userInfo['Mastered'];
        }

        return compact(
            'mostBeatenSoftcoreGame',
            'mostBeatenHardcoreGame',
            'mostCompletedGame',
            'mostMasteredGame',
            'ownAwards',
            'beatenSoftcoreAwards',
            'beatenHardcoreAwards',
            'completedAwards',
            'masteredAwards',
            'userMostBeatenSoftcore',
            'userMostBeatenHardcore',
            'userMostCompleted',
            'userMostMastered',
        );
    }

    private function findMost(array $record, string $key, array $currentMost): array
    {
        if (empty($currentMost) && (int) $record[$key] > 0) {
            return $record;
        }

        return isset($currentMost[$key]) && ($currentMost[$key] < (int) $record[$key]) ? $record : $currentMost;
    }
}
