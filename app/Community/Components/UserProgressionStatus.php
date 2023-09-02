<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Enums\AwardType;
use App\Platform\Enums\UnlockMode;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserProgressionStatus extends Component
{
    private array $userCompletionProgress = [];
    private array $userRecentlyPlayed = [];
    private array $userSiteAwards = [];
    private int $userHardcorePoints = 0;
    private int $userSoftcorePoints = 0;

    public function __construct(
        array $userCompletionProgress = [],
        array $userSiteAwards = [],
        array $userRecentlyPlayed = [],
        int $userHardcorePoints = 0,
        int $userSoftcorePoints = 0,
    ) {
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
        $joinedData = [];

        // Populate joinedData with userCompletionProgress information.
        foreach ($userCompletionProgress as $progress) {
            $consoleId = $progress['ConsoleID'];
            $gameId = $progress['GameID'];

            if (!isset($joinedData[$consoleId])) {
                $joinedData[$consoleId] = [];
            }

            $joinedData[$consoleId][$gameId] = ['progress' => $progress, 'awards' => []];
        }

        // Add userSiteAwards information into joinedData.
        foreach ($userSiteAwards as $award) {
            $gameId = $award['AwardData'];
            $consoleId = $award['ConsoleID'];

            if (!isset($joinedData[$consoleId])) {
                $joinedData[$consoleId] = [];
            }

            if (!isset($joinedData[$consoleId][$gameId])) {
                $joinedData[$consoleId][$gameId] = ['progress' => null, 'awards' => []];
            }

            $joinedData[$consoleId][$gameId]['awards'][] = $award;
        }

        // Initialize the result array.
        $consoleProgress = [];

        // Loop through joinedData to calculate counts.
        foreach ($joinedData as $consoleId => $games) {
            if (!$consoleId || $consoleId == 101) { // Exclude the "Events" console.
                continue;
            }

            $unfinishedCount = 0;
            $beatenSoftcoreCount = 0;
            $beatenHardcoreCount = 0;
            $completedCount = 0;
            $masteredCount = 0;

            foreach ($games as $gameData) {
                $awards = $gameData['awards'];
                $awardFlag = false;

                foreach ($awards as $award) {
                    $awardFlag = true;

                    if ($award['AwardType'] === AwardType::Mastery && $award['AwardDataExtra'] === UnlockMode::Hardcore) {
                        $masteredCount++;
                    } elseif ($award['AwardType'] === AwardType::Mastery && $award['AwardDataExtra'] === UnlockMode::Softcore) {
                        $completedCount++;
                    } elseif ($award['AwardType'] === AwardType::GameBeaten && $award['AwardDataExtra'] === UnlockMode::Softcore) {
                        $beatenSoftcoreCount++;
                    } elseif ($award['AwardType'] === AwardType::GameBeaten && $award['AwardDataExtra'] === UnlockMode::Hardcore) {
                        $beatenHardcoreCount++;
                    }
                }

                if (!$awardFlag && $gameData['progress'] !== null) {
                    $unfinishedCount++;
                }
            }

            $consoleProgress[$consoleId] = [
                'unfinishedCount' => $unfinishedCount,
                'beatenSoftcoreCount' => $beatenSoftcoreCount,
                'beatenHardcoreCount' => $beatenHardcoreCount,
                'completedCount' => $completedCount,
                'masteredCount' => $masteredCount,
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
            ->reject(fn ($award) =>
                $award['ConsoleID'] == 101
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
