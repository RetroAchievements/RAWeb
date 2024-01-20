<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Platform\Services\PlayerProgressionService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserProgressionStatus extends Component
{
    public array $userCompletionProgress = [];
    public array $userJoinedGamesAndAwards = [];
    public array $userRecentlyPlayed = [];
    public array $userSiteAwards = [];
    public int $userHardcorePoints = 0;
    public int $userSoftcorePoints = 0;

    public function __construct(
        protected PlayerProgressionService $playerProgressionService,
        array $userCompletionProgress = [],
        array $userJoinedGamesAndAwards = [],
        array $userSiteAwards = [],
        array $userRecentlyPlayed = [],
        int $userHardcorePoints = 0,
        int $userSoftcorePoints = 0,
    ) {

        $this->userCompletionProgress = $userCompletionProgress;
        $this->userJoinedGamesAndAwards = $userJoinedGamesAndAwards;
        $this->userSiteAwards = $userSiteAwards;
        $this->userRecentlyPlayed = $userRecentlyPlayed;
        $this->userHardcorePoints = $userHardcorePoints;
        $this->userSoftcorePoints = $userSoftcorePoints;
    }

    public function render(): ?View
    {
        [$totalCountsMetrics, $consoleProgress] = $this->buildProgressMetrics(
            $this->userJoinedGamesAndAwards
        );

        [$consoleProgress, $topConsole] = $this->sortConsoleProgress(
            $consoleProgress,
            $this->userRecentlyPlayed,
            $this->userCompletionProgress,
            $this->userSiteAwards
        );

        $totalUnfinishedCount = $totalCountsMetrics['numUnfinished'];
        $totalBeatenSoftcoreCount = $totalCountsMetrics['numBeatenSoftcore'];
        $totalBeatenHardcoreCount = $totalCountsMetrics['numBeatenHardcore'];
        $totalCompletedCount = $totalCountsMetrics['numCompleted'];
        $totalMasteredCount = $totalCountsMetrics['numMastered'];

        return view('community.components.user.progression-status.root', [
            'userCompletionProgress' => $this->userCompletionProgress,
            'userSiteAwards' => $this->userSiteAwards,
            'userRecentlyPlayed' => $this->userRecentlyPlayed,
            'consoleProgress' => $consoleProgress,
            'topConsole' => $topConsole,
            'totalUnfinishedCount' => $totalUnfinishedCount,
            'totalBeatenSoftcoreCount' => $totalBeatenSoftcoreCount,
            'totalBeatenHardcoreCount' => $totalBeatenHardcoreCount,
            'totalCompletedCount' => $totalCompletedCount,
            'totalMasteredCount' => $totalMasteredCount,
            'userHardcorePoints' => $this->userHardcorePoints,
            'userSoftcorePoints' => $this->userSoftcorePoints,
        ]);
    }

    private function buildProgressMetrics(array $userJoinedGamesAndAwards): array
    {
        $allConsoleIds = array_unique(array_map(function ($game) {
            return $game['ConsoleID'] ?? 0;
        }, $userJoinedGamesAndAwards));

        // Initialize the result array.
        $consoleProgress = [];

        $totalCountsMetrics = $this->playerProgressionService->buildPrimaryCountsMetrics(
            $userJoinedGamesAndAwards
        );

        // Loop through joinedData to calculate counts for individual consoles.
        foreach ($allConsoleIds as $consoleId) {
            if ($consoleId !== -1 && (!$consoleId || $consoleId == 101 || !isValidConsoleId($consoleId))) {
                continue;
            }

            $consoleCountsMetrics = $this->playerProgressionService->buildPrimaryCountsMetrics(
                $userJoinedGamesAndAwards,
                $consoleId
            );

            $consoleProgress[$consoleId] = [
                'unfinishedCount' => $consoleCountsMetrics['numUnfinished'],
                'beatenSoftcoreCount' => $consoleCountsMetrics['numBeatenSoftcore'],
                'beatenHardcoreCount' => $consoleCountsMetrics['numBeatenHardcore'],
                'completedCount' => $consoleCountsMetrics['numCompleted'],
                'masteredCount' => $consoleCountsMetrics['numMastered'],
                'ConsoleID' => $consoleId,
            ];
        }

        return [$totalCountsMetrics, $consoleProgress];
    }

    private function sortConsoleProgress(
        array $consoleProgress,
        array $userRecentlyPlayed,
        array $userCompletionProgress,
        array $userSiteAwards
    ): array {
        $mostRecentlyPlayedConsole = collect($userRecentlyPlayed)
            ->reject(fn ($game) => (
                $game['ConsoleID'] == 101
                || !isValidConsoleId($game['ConsoleID'])
                || !$game['AchievementsTotal']
            ))
            ->groupBy('ConsoleID')
            ->map(fn ($games) => $games->max('LastPlayed'))
            ->sortDesc()
            ->keys()
            ->first();

        $gameToConsoleMap = collect($userCompletionProgress)->pluck('ConsoleID', 'GameID');
        $userSiteAwardsWithConsoleID = collect($userSiteAwards)->map(function ($award) use ($gameToConsoleMap) {
            $award['ConsoleID'] = $gameToConsoleMap->get($award['AwardData'], null);

            return $award;
        });

        $mostRecentlyAwardedConsole = collect($userSiteAwardsWithConsoleID)
            ->reject(fn ($award) => $award['ConsoleID'] == 101
                || (isset($award['ConsoleID']) && !isValidConsoleId($award['ConsoleID']))
            )
            ->groupBy('ConsoleID')
            ->map(fn ($awards) => $awards->max('AwardedAt'))
            ->sortDesc()
            ->keys()
            ->first();

        $topConsole = $mostRecentlyPlayedConsole ?? $mostRecentlyAwardedConsole;

        uasort($consoleProgress, function ($a, $b) use ($topConsole) {
            if ($a['ConsoleID'] === $topConsole) {
                return -1;
            }

            if ($b['ConsoleID'] === $topConsole) {
                return 1;
            }

            $sumA = $a['unfinishedCount'] + $a['beatenSoftcoreCount'] + $a['beatenHardcoreCount'] + $a['completedCount'] + $a['masteredCount'];
            $sumB = $b['unfinishedCount'] + $b['beatenSoftcoreCount'] + $b['beatenHardcoreCount'] + $b['completedCount'] + $b['masteredCount'];

            return $sumB <=> $sumA;
        });

        return [$consoleProgress, $topConsole];
    }
}
