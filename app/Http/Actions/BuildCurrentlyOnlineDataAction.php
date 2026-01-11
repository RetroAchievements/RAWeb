<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Data\CurrentlyOnlineData;
use App\Models\User;
use App\Models\UsersOnlineCount;
use Carbon\Carbon;

class BuildCurrentlyOnlineDataAction
{
    public function execute(): CurrentlyOnlineData
    {
        $allTimeHighRecord = $this->getAllTimeHighRecord();

        return new CurrentlyOnlineData(
            logEntries: $this->getLogEntries(),
            numCurrentPlayers: $this->getNumCurrentPlayers(),
            allTimeHighPlayers: $allTimeHighRecord?->online_count ?? 0,
            allTimeHighDate: $allTimeHighRecord?->created_at,
        );
    }

    private function getNumCurrentPlayers(): int
    {
        return User::where('last_activity_at', '>', Carbon::now()->subMinutes(10))->count();
    }

    private function getLogEntries(): array
    {
        return UsersOnlineCount::query()
            ->orderByDesc('created_at')
            ->take(48)
            ->pluck('online_count')
            ->reverse()
            ->pad(48, 0)
            ->values()
            ->all();
    }

    private function getAllTimeHighRecord(): ?UsersOnlineCount
    {
        return UsersOnlineCount::query()
            ->orderByDesc('online_count')
            ->first();
    }
}
