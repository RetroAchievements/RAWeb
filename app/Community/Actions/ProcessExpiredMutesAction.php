<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\User;
use Illuminate\Support\Carbon;

class ProcessExpiredMutesAction
{
    public function execute(): void
    {
        $mutedRoleId = config('services.discord.muted');

        // Find users whose muted_until date has passed.
        $expiredMutedUsers = User::whereNotNull('muted_until')
            ->where('muted_until', '<', Carbon::now())
            ->get();

        foreach ($expiredMutedUsers as $user) {
            // Remove the Discord Muted role.
            if ($mutedRoleId) {
                (new RemoveUserDiscordRolesAction())->execute($user, [$mutedRoleId]);
            }

            // Set muted_until to null.
            $user->muted_until = null;
            $user->saveQuietly();
        }
    }
}
