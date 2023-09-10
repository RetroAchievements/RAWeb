<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Platform\Services\PlayerProgressionService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserProgressionStatus extends Component
{
    protected PlayerProgressionService $playerProgressionService;
    public array $userCompletionProgress = [];
    public array $userRecentlyPlayed = [];
    public array $userSiteAwards = [];
    public int $userHardcorePoints = 0;
    public int $userSoftcorePoints = 0;

    public function __construct(
        PlayerProgressionService $playerProgressionService,
        array $userCompletionProgress = [],
        array $userSiteAwards = [],
        array $userRecentlyPlayed = [],
        int $userHardcorePoints = 0,
        int $userSoftcorePoints = 0,
    ) {
        // DI
        $this->playerProgressionService = $playerProgressionService;

        // Props
        $this->userCompletionProgress = $userCompletionProgress;
        $this->userSiteAwards = $userSiteAwards;
        $this->userRecentlyPlayed = $userRecentlyPlayed;
        $this->userHardcorePoints = $userHardcorePoints;
        $this->userSoftcorePoints = $userSoftcorePoints;
    }

    public function render(): ?View
    {
        $consoleProgress = $this->buildConsoleProgress($this->userCompletionProgress, $this->userSiteAwards);
        [$consoleProgress, $topConsole] = $this->sortConsoleProgress(
            $consoleProgress,
            $this->userRecentlyPlayed,
            $this->userCompletionProgress,
            $this->userSiteAwards
        );

        $totalUnfinishedCount = array_sum(array_column($consoleProgress, 'unfinishedCount'));
        $totalBeatenSoftcoreCount = array_sum(array_column($consoleProgress, 'beatenSoftcoreCount'));
        $totalBeatenHardcoreCount = array_sum(array_column($consoleProgress, 'beatenHardcoreCount'));
        $totalCompletedCount = array_sum(array_column($consoleProgress, 'completedCount'));
        $totalMasteredCount = array_sum(array_column($consoleProgress, 'masteredCount'));

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

    private function buildConsoleProgress(array $userCompletionProgress, array $userSiteAwards): array
    {
        $filteredAndJoinedGamesList = $this->playerProgressionService->filterAndJoinGames(
            $userCompletionProgress,
            $userSiteAwards,
        );

        $allConsoleIds = array_unique(array_map(function ($game) {
            return $game['ConsoleID'] ?? 0;
        }, $filteredAndJoinedGamesList));

        // Initialize the result array.
        $consoleProgress = [];

        // Loop through joinedData to calculate counts.
        foreach ($allConsoleIds as $consoleId) {
            if ($consoleId !== -1 && (!$consoleId || $consoleId == 101 || !isValidConsoleId($consoleId))) {
                continue;
            }

            $countsMetrics = $this->playerProgressionService->buildPrimaryCountsMetrics(
                $filteredAndJoinedGamesList,
                $consoleId
            );

            $consoleProgress[$consoleId] = [
                'unfinishedCount' => $countsMetrics['numUnfinished'],
                'beatenSoftcoreCount' => $countsMetrics['numBeatenSoftcore'],
                'beatenHardcoreCount' => $countsMetrics['numBeatenHardcore'],
                'completedCount' => $countsMetrics['numCompleted'],
                'masteredCount' => $countsMetrics['numMastered'],
                'ConsoleID' => $consoleId,
            ];
        }

        return $consoleProgress;
    }

    private function sortConsoleProgress(
        array $consoleProgress,
        array $userRecentlyPlayed,
        array $userCompletionProgress,
        array $userSiteAwards
    ): array {
        $mostRecentlyPlayedConsole = collect($userRecentlyPlayed)
            ->reject(fn ($game) => $game['ConsoleID'] == 101 || !isValidConsoleId($game['ConsoleID']))
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
