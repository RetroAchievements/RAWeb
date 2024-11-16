<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Data\CurrentlyOnlineData;
use App\Models\User;
use Carbon\Carbon;

class BuildCurrentlyOnlineDataAction
{
    private const LOG_PATH = 'logs/playersonline.log';
    private const LOG_INTERVAL_MINUTES = 30;

    public function execute(): CurrentlyOnlineData
    {
        $logFileLines = $this->readLogFile();

        $allTimeHigh = $this->getAllTimeHigh($logFileLines);

        return new CurrentlyOnlineData(
            logEntries: $this->getLogEntries($logFileLines),
            numCurrentPlayers: $this->getNumCurrentPlayers(),
            allTimeHighPlayers: $allTimeHigh[0],
            allTimeHighDate: $allTimeHigh[1],
        );
    }

    // Make sure we only read from the log file once per execution.
    private function readLogFile(): array
    {
        $path = storage_path(self::LOG_PATH);
        if (!file_exists($path)) {
            return [];
        }

        return file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    private function getNumCurrentPlayers(): int
    {
        return User::where('LastLogin', '>', Carbon::now()->subMinutes(10))->count();
    }

    private function getLogEntries(array $logFileLines): array
    {
        return array_values(
            collect($logFileLines)
                ->reverse()
                ->take(48)
                ->map(fn ($line) => (int) $line)
                ->values()
                ->reverse()
                ->pad(48, 0)
                ->all()
        );
    }

    private function getAllTimeHigh(array $logFileLines): array
    {
        if (empty($logFileLines)) {
            return [0, null];
        }

        $maxCount = collect($logFileLines)->max();
        $maxIndex = collect($logFileLines)->search($maxCount);

        $now = Carbon::now();
        $lastLogTime = $now->minute >= self::LOG_INTERVAL_MINUTES
            ? $now->copy()->startOfHour()->addMinutes(self::LOG_INTERVAL_MINUTES)
            : $now->copy()->startOfHour();

        $minutesAgo = (count($logFileLines) - 1 - $maxIndex) * self::LOG_INTERVAL_MINUTES;

        return [(int) $maxCount, $lastLogTime->copy()->subMinutes($minutesAgo)];
    }
}
