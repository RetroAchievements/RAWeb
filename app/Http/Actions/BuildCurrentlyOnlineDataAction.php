<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Data\CurrentlyOnlineData;
use App\Models\UsersOnlineCount;
use App\Platform\Services\UserLastActivityService;
use Carbon\Carbon;

class BuildCurrentlyOnlineDataAction
{
    public function execute(): CurrentlyOnlineData
    {
        $allTimeHighRecord = $this->getAllTimeHighRecord();
        $numCurrentPlayers = $this->getNumCurrentPlayers();

        return new CurrentlyOnlineData(
            logEntries: $this->getLogEntries($numCurrentPlayers),
            numCurrentPlayers: $numCurrentPlayers,
            allTimeHighPlayers: $allTimeHighRecord?->online_count ?? 0,
            allTimeHighDate: $allTimeHighRecord?->created_at,
        );
    }

    private function getNumCurrentPlayers(): int
    {
        return app(UserLastActivityService::class)->countOnline(withinMinutes: 10);
    }

    private function getLogEntries(int $numCurrentPlayers): array
    {
        $now = Carbon::now();

        $records = UsersOnlineCount::query()
            ->where('created_at', '>=', $now->copy()->subHours(24))
            ->orderBy('created_at')
            ->get();

        // Initialize 48 slots with zeroes (slot 0 = 24h ago, slot 47 = now).
        $slots = array_fill(0, 48, 0);

        // Place each record in its appropriate slot based on its timestamp.
        // Snap times to 30-minute boundaries to handle cron jitter.
        $snappedNow = $now->copy()
            ->minute($now->minute < 30 ? 0 : 30)
            ->second(0);

        $hasCurrentIntervalRecord = false;
        foreach ($records as $record) {
            $snappedTime = $record->created_at->copy()
                ->minute($record->created_at->minute < 30 ? 0 : 30)
                ->second(0);

            $minutesAgo = $snappedTime->diffInMinutes($snappedNow);
            $slotIndex = 47 - (int) ($minutesAgo / 30);

            if ($slotIndex >= 0 && $slotIndex < 48) {
                $slots[$slotIndex] = $record->online_count;

                if ($slotIndex === 47) {
                    $hasCurrentIntervalRecord = true;
                }
            }
        }

        // If no record exists for the current interval, use the real-time
        // player count to avoid showing a misleading 0. This resolves a race
        // condition where the front-end expects a record written at exact timestamps,
        // but the back-end may take a few moments to actually write it.
        if (!$hasCurrentIntervalRecord) {
            $slots[47] = $numCurrentPlayers;
        }

        return $slots;
    }

    private function getAllTimeHighRecord(): ?UsersOnlineCount
    {
        return UsersOnlineCount::query()
            ->orderByDesc('online_count')
            ->first();
    }
}
