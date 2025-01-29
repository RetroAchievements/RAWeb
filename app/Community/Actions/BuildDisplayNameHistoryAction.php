<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\User;
use Carbon\Carbon;

class BuildDisplayNameHistoryAction
{
    public function execute(User $user): string
    {
        // Get all approved display_name changes in reverse chronological order.
        $allChanges = $user->usernameRequests()
            ->approved()
            ->orderBy('approved_at', 'desc')
            ->get();

        $entries = collect();

        // Add current display_name if it came from a change request.
        if ($user->display_name === $user->username) {
            $entries->push($this->formatEntry($user->display_name, $user->created_at));
        } elseif ($currentChange = $allChanges->firstWhere('username', $user->display_name)) {
            $entries->push($this->formatEntry($user->display_name, $currentChange->approved_at));
        }

        // Add all previous display_name changes.
        $allChanges->reject(fn ($change) => $change->username === $user->display_name)
            ->each(fn ($change) => $entries->push($this->formatEntry($change->username, $change->approved_at)));

        // Add their original username if it's different from their current display_name and not already included.
        if ($user->username !== $user->display_name && !$allChanges->contains('username', $user->username)) {
            $entries->push($this->formatEntry($user->username, $user->created_at));
        }

        return $entries->join("\n");
    }

    private function formatEntry(string $username, Carbon $date): string
    {
        return $username . ' (' . $date->format('F j, Y') . ')';
    }
}
